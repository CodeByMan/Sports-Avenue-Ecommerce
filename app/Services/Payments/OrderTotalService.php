<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Jersey;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderTotalService
{
    public function recalculateCart(Order $order): Order
    {
        return $this->recalculate($order, null, Order::STATUS_IN_CART, null, true);
    }

    /** @param array<string, mixed> $shipping */
    public function applyCheckoutTotals(Order $order, array $shipping): Order
    {
        return $this->recalculate(
            $order,
            $shipping,
            Order::STATUS_PENDING,
            PaymentInitializationService::TRANSACTION_STATUS,
            false,
        );
    }

    /** @param array<string, mixed>|null $shipping */
    private function recalculate(
        Order $order,
        ?array $shipping,
        string $requiredStatus,
        ?string $requiredTransactionStatus,
        bool $refreshSnapshots,
    ): Order {
        return DB::transaction(function () use ($order, $shipping, $requiredStatus, $requiredTransactionStatus, $refreshSnapshots): Order {
            $query = Order::query()
                ->whereKey($order->getKey())
                ->where('user_id', $order->user_id)
                ->where('status', $requiredStatus);

            if ($requiredTransactionStatus !== null) {
                $query->where('transaction_status', $requiredTransactionStatus);
            }

            /** @var Order $lockedOrder */
            $lockedOrder = $query->lockForUpdate()->firstOrFail();
            $items = $lockedOrder->items()->lockForUpdate()->get();

            if ($items->isEmpty()) {
                throw new \DomainException('An empty cart cannot be recalculated.');
            }

            if ($refreshSnapshots) {
                $jerseys = Jersey::query()
                    ->whereIn('id', $items->pluck('jersey_id')->filter()->all())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    /** @var Jersey|null $jersey */
                    $jersey = $item->jersey_id === null ? null : $jerseys->get($item->jersey_id);
                    if ($jersey === null) {
                        throw new \DomainException('A cart product is no longer available.');
                    }

                    $namesets = is_array($item->nameset) ? $item->nameset : [];
                    $lineTotal = ((int) $jersey->price * $item->quantity)
                        + ((int) $jersey->price_nameset * count($namesets));

                    $item->forceFill([
                        'product_name' => $jersey->name,
                        'product_sku' => $jersey->sku,
                        'product_slug' => $jersey->slug,
                        'image_path' => $jersey->image,
                        'product_weight' => $jersey->weight,
                        'unit_price' => $jersey->price,
                        'nameset_unit_price' => $jersey->price_nameset,
                        'line_total' => $lineTotal,
                    ])->save();
                }
            }

            $subtotal = (int) $lockedOrder->items()->sum('line_total');
            $shippingAmount = $shipping === null ? 0 : max(0, (int) ($shipping['value'] ?? 0));
            $taxAmount = 0;
            $discountAmount = 0;
            $grandTotal = max(0, $subtotal + $shippingAmount + $taxAmount - $discountAmount);

            $lockedOrder->forceFill([
                'subtotal' => $subtotal,
                'shipping_amount' => $shippingAmount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_price' => $grandTotal,
                'courier_services' => $shipping,
            ])->save();

            return $lockedOrder->fresh(['items', 'user']);
        }, 3);
    }
}
