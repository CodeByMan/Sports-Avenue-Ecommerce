<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentStatusClient;
use App\Data\PaymentWebhookData;
use App\Exceptions\InvalidPaymentNotification;
use Midtrans\Transaction;
use Throwable;

class MidtransPaymentStatusClient implements PaymentStatusClient
{
    public function __construct(private readonly MidtransConfiguration $configuration) {}

    public function fetch(string $orderId): PaymentWebhookData
    {
        try {
            $this->configuration->apply();
            $response = Transaction::status($orderId);
        } catch (Throwable $exception) {
            throw new InvalidPaymentNotification(
                'The provider transaction status could not be retrieved.',
                InvalidPaymentNotification::PROVIDER_STATUS_UNAVAILABLE,
                previous: $exception,
            );
        }

        return PaymentWebhookData::fromProviderResponse((array) $response);
    }
}
