<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Data\PaymentWebhookData;
use App\Exceptions\InvalidPaymentNotification;
use App\Models\Order;

class OrderStateService
{
    public function targetStatus(PaymentWebhookData $data): ?string
    {
        return match ($data->transactionStatus) {
            'settlement' => Order::STATUS_SUCCESS,
            'capture' => match ($data->fraudStatus) {
                'accept' => Order::STATUS_SUCCESS,
                'challenge' => Order::STATUS_PENDING,
                'deny' => Order::STATUS_FAILED,
                default => Order::STATUS_REVIEW,
            },
            'pending' => Order::STATUS_PENDING,
            'deny', 'expire', 'cancel', 'failure' => Order::STATUS_FAILED,
            'refund', 'partial_refund', 'chargeback', 'partial_chargeback' => Order::STATUS_REVIEW,
            default => null,
        };
    }

    public function assertTransitionAllowed(string $currentStatus, string $targetStatus): void
    {
        $allowed = match ($currentStatus) {
            Order::STATUS_IN_CART => [
                Order::STATUS_PENDING,
                Order::STATUS_SUCCESS,
                Order::STATUS_FAILED,
                Order::STATUS_REVIEW,
            ],
            Order::STATUS_PENDING => [
                Order::STATUS_PENDING,
                Order::STATUS_SUCCESS,
                Order::STATUS_FAILED,
                Order::STATUS_REVIEW,
            ],
            Order::STATUS_SUCCESS => [Order::STATUS_SUCCESS, Order::STATUS_REVIEW],
            Order::STATUS_FAILED => [Order::STATUS_FAILED],
            Order::STATUS_REVIEW => [Order::STATUS_REVIEW],
            default => [],
        };

        if (! in_array($targetStatus, $allowed, true)) {
            throw new InvalidPaymentNotification(
                "Illegal order state transition from {$currentStatus} to {$targetStatus}.",
                InvalidPaymentNotification::STATE_TRANSITION_INVALID,
            );
        }
    }
}
