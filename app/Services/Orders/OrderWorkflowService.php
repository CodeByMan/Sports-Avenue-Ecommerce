<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdatedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

final class OrderWorkflowService
{
    /** @return array<int, string> */
    public function allowedTransitions(Order $order): array
    {
        if ($order->status === Order::STATUS_IN_CART) {
            return [];
        }

        $transitions = [
            Order::FULFILLMENT_PENDING => [
                Order::FULFILLMENT_CONFIRMED,
                Order::FULFILLMENT_CANCELLED,
            ],
            Order::FULFILLMENT_CONFIRMED => [
                Order::FULFILLMENT_PROCESSING,
                Order::FULFILLMENT_CANCELLED,
            ],
            Order::FULFILLMENT_PROCESSING => [
                Order::FULFILLMENT_PACKED,
                Order::FULFILLMENT_CANCELLED,
            ],
            Order::FULFILLMENT_PACKED => [
                Order::FULFILLMENT_DISPATCHED,
                Order::FULFILLMENT_CANCELLED,
            ],
            Order::FULFILLMENT_DISPATCHED => [
                Order::FULFILLMENT_OUT_FOR_DELIVERY,
            ],
            Order::FULFILLMENT_OUT_FOR_DELIVERY => [
                Order::FULFILLMENT_DELIVERED,
                Order::FULFILLMENT_CANCELLED,
            ],
            Order::FULFILLMENT_DELIVERED => [
                Order::FULFILLMENT_REFUNDED,
            ],
            Order::FULFILLMENT_CANCELLED => $order->status === Order::STATUS_SUCCESS
                ? [Order::FULFILLMENT_REFUNDED]
                : [],
            Order::FULFILLMENT_REFUNDED => [],
        ];

        $allowed = $transitions[$order->fulfillment_status] ?? [];

        if ($order->status === Order::STATUS_FAILED) {
            return array_values(array_intersect($allowed, [Order::FULFILLMENT_CANCELLED]));
        }

        if ($order->status === Order::STATUS_REVIEW) {
            return array_values(array_intersect($allowed, [Order::FULFILLMENT_CANCELLED]));
        }

        return $allowed;
    }

    public function transition(Order $order, string $nextStatus, User $actor, ?string $note = null): Order
    {
        if (! $actor->isActive() || ! checkRole(User::ROLE_STAFF, $actor->role)) {
            throw new AuthorizationException('You are not allowed to update order workflow status.');
        }

        if (! array_key_exists($nextStatus, Order::fulfillmentStatusOptions())) {
            throw ValidationException::withMessages([
                'fulfillment_status' => trans('The selected order status is invalid.'),
            ]);
        }

        $updatedOrder = DB::transaction(function () use ($order, $nextStatus, $actor, $note): Order {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->where('status', '!=', Order::STATUS_IN_CART)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $lockedOrder->fulfillment_status;

            if (! in_array($nextStatus, $this->allowedTransitions($lockedOrder), true)) {
                throw ValidationException::withMessages([
                    'fulfillment_status' => trans(
                        'The order cannot move from :from to :to.',
                        [
                            'from' => Order::fulfillmentStatusLabel($fromStatus),
                            'to' => Order::fulfillmentStatusLabel($nextStatus),
                        ],
                    ),
                ]);
            }

            $updates = [
                'fulfillment_status' => $nextStatus,
                'fulfillment_status_updated_at' => now(),
            ];

            if ($nextStatus === Order::FULFILLMENT_DELIVERED
                && $lockedOrder->payment_method === 'cash_on_delivery') {
                $updates = array_merge($updates, [
                    'status' => Order::STATUS_SUCCESS,
                    'transaction_status' => 'cash_collected',
                    'paid_at' => $lockedOrder->paid_at ?? now(),
                    'processed_at' => now(),
                ]);
            }

            if ($nextStatus === Order::FULFILLMENT_CANCELLED
                && $lockedOrder->status === Order::STATUS_PENDING) {
                $updates = array_merge($updates, [
                    'status' => Order::STATUS_FAILED,
                    'transaction_status' => 'order_cancelled',
                    'processed_at' => now(),
                ]);
            }

            $lockedOrder->forceFill($updates)->save();

            $lockedOrder->statusHistories()->create([
                'from_status' => $fromStatus,
                'to_status' => $nextStatus,
                'changed_by' => $actor->getKey(),
                'note' => filled($note) ? trim((string) $note) : null,
            ]);

            return $lockedOrder->fresh(['user', 'items', 'statusHistories.actor']);
        }, 3);

        $this->notifyCustomer($updatedOrder);

        return $updatedOrder;
    }

    private function notifyCustomer(Order $order): void
    {
        if ($order->user === null) {
            return;
        }

        try {
            $order->user->notify(new OrderStatusUpdatedNotification($order));
        } catch (Throwable $exception) {
            Log::warning('Customer order workflow notification failed.', [
                'order_id' => $order->getKey(),
                'invoice_number' => $order->invoice_number,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
