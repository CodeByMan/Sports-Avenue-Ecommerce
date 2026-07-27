<?php

declare(strict_types=1);

namespace App\Data;

use App\Exceptions\InvalidPaymentNotification;

final class PaymentWebhookData
{
    public function __construct(
        public string $orderId,
        public string $transactionId,
        public string $transactionStatus,
        public string $statusCode,
        public string $grossAmount,
        public string $signatureKey,
        public ?string $paymentType = null,
        public ?string $fraudStatus = null,
    ) {}

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return self::create($payload, true);
    }

    /** @param array<string, mixed> $payload */
    public static function fromProviderResponse(array $payload): self
    {
        return self::create($payload, false);
    }

    /** @param array<string, mixed> $payload */
    private static function create(array $payload, bool $signatureRequired): self
    {
        foreach (['order_id', 'transaction_id', 'transaction_status', 'status_code', 'gross_amount'] as $field) {
            if (! isset($payload[$field]) || trim((string) $payload[$field]) === '') {
                throw new InvalidPaymentNotification(
                    "Provider payment status is missing {$field}.",
                    InvalidPaymentNotification::PAYLOAD_FIELD_MISSING,
                );
            }
        }

        if ($signatureRequired && (! isset($payload['signature_key']) || trim((string) $payload['signature_key']) === '')) {
            throw new InvalidPaymentNotification(
                'Payment signature is missing.',
                InvalidPaymentNotification::SIGNATURE_MISSING,
            );
        }

        return new self(
            orderId: trim((string) $payload['order_id']),
            transactionId: trim((string) $payload['transaction_id']),
            transactionStatus: strtolower(trim((string) $payload['transaction_status'])),
            statusCode: trim((string) $payload['status_code']),
            grossAmount: trim((string) $payload['gross_amount']),
            signatureKey: strtolower(trim((string) ($payload['signature_key'] ?? ''))),
            paymentType: isset($payload['payment_type']) && trim((string) $payload['payment_type']) !== ''
                ? strtolower(trim((string) $payload['payment_type']))
                : null,
            fraudStatus: isset($payload['fraud_status']) && trim((string) $payload['fraud_status']) !== ''
                ? strtolower(trim((string) $payload['fraud_status']))
                : null,
        );
    }
}
