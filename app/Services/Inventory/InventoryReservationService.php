<?php

declare(strict_types=1);

namespace App\Services\Inventory;

use App\Models\Jersey;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class InventoryReservationService
{
    public function reserve(Order $order): void
    {
        /** @var Collection<int, OrderItem> $items */
        $items = $order->items()->lockForUpdate()->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'payment' => trans('Your cart is empty.'),
            ]);
        }

        $jerseyIds = $items->pluck('jersey_id')->filter()->unique()->values()->all();
        /** @var Collection<int, Jersey> $jerseys */
        $jerseys = Jersey::query()->whereIn('id', $jerseyIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            $jersey = $item->jersey_id === null ? null : $jerseys->get($item->jersey_id);
            $stock = $jersey?->stock ?? [];

            if ($jersey === null || (int) ($stock[$item->size] ?? 0) < $item->quantity) {
                throw ValidationException::withMessages([
                    'payment' => trans('One or more cart items no longer have enough stock.'),
                ]);
            }
        }

        foreach ($items as $item) {
            /** @var Jersey $jersey */
            $jersey = $jerseys->get($item->jersey_id);
            $stock = $jersey->stock;
            $stock[$item->size] = (int) $stock[$item->size] - $item->quantity;
            $jersey->forceFill(['stock' => $stock])->save();
            $item->forceFill([
                'stock_reserved_at' => now(),
                'stock_released_at' => null,
                'stock_consumed_at' => null,
            ])->saveQuietly();
        }
    }

    public function consume(Order $order): bool
    {
        /** @var Collection<int, OrderItem> $items */
        $items = $order->items()->lockForUpdate()->get();

        if ($items->isEmpty()) {
            return false;
        }

        $jerseyIds = $items->pluck('jersey_id')->filter()->unique()->values()->all();
        /** @var Collection<int, Jersey> $jerseys */
        $jerseys = Jersey::query()->whereIn('id', $jerseyIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            if ($item->stock_consumed_at !== null) {
                continue;
            }

            $jersey = $item->jersey_id === null ? null : $jerseys->get($item->jersey_id);
            if ($jersey === null) {
                return false;
            }

            if ($item->stock_reserved_at === null || $item->stock_released_at !== null) {
                $stock = $jersey->stock;
                if ((int) ($stock[$item->size] ?? 0) < $item->quantity) {
                    return false;
                }
                $stock[$item->size] = (int) $stock[$item->size] - $item->quantity;
                $jersey->forceFill(['stock' => $stock])->save();
            }
        }

        foreach ($items as $item) {
            if ($item->stock_consumed_at !== null) {
                continue;
            }

            /** @var Jersey $jersey */
            $jersey = $jerseys->get($item->jersey_id);
            $jersey->forceFill(['sold' => (int) $jersey->sold + $item->quantity])->save();
            $item->forceFill(['stock_consumed_at' => now()])->saveQuietly();
        }

        return true;
    }

    public function release(Order $order): void
    {
        /** @var Collection<int, OrderItem> $items */
        $items = $order->items()
            ->whereNotNull('stock_reserved_at')
            ->whereNull('stock_released_at')
            ->whereNull('stock_consumed_at')
            ->lockForUpdate()
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $jerseyIds = $items->pluck('jersey_id')->filter()->unique()->values()->all();
        /** @var Collection<int, Jersey> $jerseys */
        $jerseys = Jersey::query()->whereIn('id', $jerseyIds)->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            $jersey = $item->jersey_id === null ? null : $jerseys->get($item->jersey_id);
            if ($jersey !== null) {
                $stock = $jersey->stock;
                $stock[$item->size] = (int) ($stock[$item->size] ?? 0) + $item->quantity;
                $jersey->forceFill(['stock' => $stock])->save();
            }

            $item->forceFill(['stock_released_at' => now()])->saveQuietly();
        }
    }
}
