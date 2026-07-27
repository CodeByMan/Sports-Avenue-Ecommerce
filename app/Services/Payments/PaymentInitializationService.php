<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Inventory\InventoryReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentInitializationService
{
    public const TRANSACTION_STATUS = 'initiated';

    public function __construct(private readonly InventoryReservationService $inventory) {}

    public function begin(Order $order): Order
    {
        return DB::transaction(function () use ($order): Order {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->where('user_id', $order->user_id)
                ->lockForUpdate()
                ->first();

            if ($lockedOrder === null || $lockedOrder->status !== Order::STATUS_IN_CART) {
                throw ValidationException::withMessages([
                    'payment' => trans('This cart is no longer available for checkout.'),
                ]);
            }

            $lockedOrder = app(OrderTotalService::class)->recalculateCart($lockedOrder);

            if ($lockedOrder->items()->doesntExist() || (int) $lockedOrder->total_price <= 0) {
                throw ValidationException::withMessages([
                    'payment' => trans('Your cart is empty or has an invalid total.'),
                ]);
            }

            $this->inventory->reserve($lockedOrder);

            $lockedOrder->forceFill([
                'status' => Order::STATUS_PENDING,
                'transaction_status' => self::TRANSACTION_STATUS,
                'reservation_expires_at' => now()->addMinutes((int) config('checkout.reservation_minutes', 30)),
            ])->save();

            return $lockedOrder->fresh(['items', 'user']);
        }, 3);
    }

    public function release(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if ($lockedOrder === null
                || $lockedOrder->status !== Order::STATUS_PENDING
                || $lockedOrder->transaction_status !== self::TRANSACTION_STATUS
                || $lockedOrder->provider_transaction_id !== null) {
                return;
            }

            $this->inventory->release($lockedOrder);
            $lockedOrder->forceFill([
                'status' => Order::STATUS_IN_CART,
                'transaction_status' => null,
                'reservation_expires_at' => null,
            ])->save();
        }, 3);
    }

    public function markForReview(Order $order): ?Order
    {
        return DB::transaction(function () use ($order): ?Order {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

            if ($lockedOrder === null
                || $lockedOrder->status !== Order::STATUS_PENDING
                || $lockedOrder->transaction_status !== self::TRANSACTION_STATUS
                || $lockedOrder->provider_transaction_id !== null) {
                return null;
            }

            $lockedOrder->forceFill([
                'status' => Order::STATUS_REVIEW,
                'transaction_status' => 'initialization_uncertain',
                'processed_at' => now(),
            ])->save();

            return $lockedOrder->fresh(['user', 'items']);
        }, 3);
    }
}
