<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentStatusClient;
use App\Data\PaymentWebhookData;
use App\Exceptions\InvalidPaymentNotification;
use App\Exceptions\PaymentGatewayException;
use App\Models\Order;

class PaymentVerificationService
{
    private const TERMINAL_STATUSES = [
        'capture',
        'settlement',
        'deny',
        'expire',
        'cancel',
        'failure',
        'refund',
        'partial_refund',
        'chargeback',
        'partial_chargeback',
    ];

    public function __construct(
        private readonly MidtransConfiguration $configuration,
        private readonly PaymentStatusClient $statusClient,
    ) {}

    public function verify(PaymentWebhookData $callback, Order $order): PaymentWebhookData
    {
        try {
            $this->configuration->apply();
        } catch (PaymentGatewayException) {
            throw new InvalidPaymentNotification(
                'Payment gateway configuration is unavailable.',
                InvalidPaymentNotification::CONFIGURATION_UNAVAILABLE,
            );
        }

        $this->verifySignature($callback);
        $this->verifyOrderIdentity($callback, $order);
        $this->assertConsistentCombination($callback);

        $verifyTerminalStatus = app()->environment('production')
            || (bool) config('midtrans.verify_terminal_status', true);

        if (! $verifyTerminalStatus
            || ! in_array($callback->transactionStatus, self::TERMINAL_STATUSES, true)) {
            return $callback;
        }

        $provider = $this->statusClient->fetch($callback->orderId);
        $this->verifyOrderIdentity($provider, $order);
        $this->assertConsistentCombination($provider);

        if ($provider->transactionId !== $callback->transactionId) {
            throw new InvalidPaymentNotification(
                'Provider transaction identifier does not match the callback.',
                InvalidPaymentNotification::PROVIDER_TRANSACTION_MISMATCH,
            );
        }

        if ($callback->paymentType !== null
            && $provider->paymentType !== null
            && $callback->paymentType !== $provider->paymentType) {
            throw new InvalidPaymentNotification(
                'Provider payment type does not match the callback.',
                InvalidPaymentNotification::PAYMENT_TYPE_MISMATCH,
            );
        }

        return $provider;
    }

    private function verifySignature(PaymentWebhookData $data): void
    {
        $serverKey = (string) config('midtrans.server_key');
        $expectedSignature = hash('sha512', $data->orderId.$data->statusCode.$data->grossAmount.$serverKey);

        if (! hash_equals($expectedSignature, $data->signatureKey)) {
            throw new InvalidPaymentNotification(
                'Invalid payment signature.',
                InvalidPaymentNotification::SIGNATURE_INVALID,
            );
        }
    }

    private function verifyOrderIdentity(PaymentWebhookData $data, Order $order): void
    {
        if ($order->invoice_number !== $data->orderId) {
            throw new InvalidPaymentNotification(
                'Payment order mismatch.',
                InvalidPaymentNotification::ORDER_MISMATCH,
            );
        }

        if ($this->normalizeAmount($data->grossAmount) !== $this->normalizeAmount((string) $order->total_price)) {
            throw new InvalidPaymentNotification(
                'Payment amount mismatch.',
                InvalidPaymentNotification::AMOUNT_MISMATCH,
            );
        }

        if ($order->provider_transaction_id !== null && $order->provider_transaction_id !== $data->transactionId) {
            throw new InvalidPaymentNotification(
                'Payment transaction mismatch.',
                InvalidPaymentNotification::TRANSACTION_MISMATCH,
            );
        }
    }

    private function assertConsistentCombination(PaymentWebhookData $data): void
    {
        $allowedCodes = match ($data->transactionStatus) {
            'capture' => match ($data->fraudStatus) {
                'accept' => ['200'],
                'challenge' => ['201'],
                'deny' => ['202'],
                default => ['200', '201', '202'],
            },
            'settlement', 'refund', 'partial_refund', 'chargeback', 'partial_chargeback' => ['200'],
            'pending' => ['200', '201'],
            'deny', 'expire', 'cancel', 'failure' => ['202'],
            default => [],
        };

        if ($allowedCodes === [] || ! in_array($data->statusCode, $allowedCodes, true)) {
            throw new InvalidPaymentNotification(
                'Payment status code is inconsistent with the transaction state.',
                InvalidPaymentNotification::STATUS_INCONSISTENT,
            );
        }

        if ($data->transactionStatus === 'capture' && $data->paymentType !== 'credit_card') {
            throw new InvalidPaymentNotification(
                'Capture status is only valid for a credit-card payment.',
                InvalidPaymentNotification::CAPTURE_TYPE_INVALID,
            );
        }

        if ($data->transactionStatus === 'settlement'
            && ! in_array($data->fraudStatus, [null, 'accept'], true)) {
            throw new InvalidPaymentNotification(
                'A settled payment has an inconsistent fraud decision.',
                InvalidPaymentNotification::FRAUD_STATUS_INCONSISTENT,
            );
        }

        if ($data->transactionStatus === 'pending' && $data->fraudStatus !== null) {
            throw new InvalidPaymentNotification(
                'A pending payment cannot contain a terminal fraud decision.',
                InvalidPaymentNotification::FRAUD_STATUS_INCONSISTENT,
            );
        }

        if (in_array($data->transactionStatus, ['expire', 'cancel', 'failure'], true)
            && $data->fraudStatus !== null) {
            throw new InvalidPaymentNotification(
                'The failed payment state has an inconsistent fraud decision.',
                InvalidPaymentNotification::FRAUD_STATUS_INCONSISTENT,
            );
        }
    }

    private function normalizeAmount(string $amount): string
    {
        $amount = trim($amount);

        if (! preg_match('/^(?<whole>\d+)(?:\.(?<fraction>\d{1,2}))?$/', $amount, $matches)) {
            throw new InvalidPaymentNotification(
                'Payment amount format is invalid.',
                InvalidPaymentNotification::AMOUNT_FORMAT_INVALID,
            );
        }

        $whole = ltrim($matches['whole'], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches['fraction'] ?? '', 2, '0');

        return $whole.'.'.$fraction;
    }
}
