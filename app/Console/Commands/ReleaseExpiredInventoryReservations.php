<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Inventory\InventoryReservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredInventoryReservations extends Command
{
    protected $signature = 'inventory:release-expired-reservations';

    protected $description = 'Release expired checkout stock reservations while keeping late provider updates reconcilable.';

    public function handle(InventoryReservationService $inventory): int
    {
        Order::query()
            ->where('status', Order::STATUS_PENDING)
            ->whereNotNull('reservation_expires_at')
            ->where('reservation_expires_at', '<=', now())
            ->orderBy('id')
            ->eachById(function (Order $order) use ($inventory): void {
                DB::transaction(function () use ($order, $inventory): void {
                    /** @var Order|null $lockedOrder */
                    $lockedOrder = Order::query()->whereKey($order->getKey())->lockForUpdate()->first();

                    if ($lockedOrder === null
                        || $lockedOrder->status !== Order::STATUS_PENDING
                        || $lockedOrder->reservation_expires_at === null
                        || $lockedOrder->reservation_expires_at->isFuture()) {
                        return;
                    }

                    $inventory->release($lockedOrder);
                    $lockedOrder->forceFill([
                        'status' => Order::STATUS_PENDING,
                        'transaction_status' => 'reservation_expired',
                        'processed_at' => now(),
                        'reservation_expires_at' => null,
                    ])->save();
                }, 3);
            });

        return self::SUCCESS;
    }
}
