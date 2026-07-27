<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Jersey;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\Concerns\GuardsDemoSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleOrderSeeder extends Seeder
{
    use GuardsDemoSeeding;

    public function run(): void
    {
        $this->ensureDemoSeedingIsAllowed();

        $customer = User::query()->where('email', 'customer@sports-avenue.test')->first();

        if ($customer === null) {
            return;
        }

        DB::transaction(function () use ($customer): void {
            $order = Order::query()->firstOrNew(['invoice_number' => 'SA-DEMO-PK-0001']);

            if (! $order->exists) {
                $order->forceFill([
                    'user_id' => $customer->id,
                    'subtotal' => 0,
                    'shipping_amount' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'total_price' => 0,
                    'status' => Order::STATUS_IN_CART,
                ])->save();

                Jersey::query()->active()->orderByDesc('is_featured')->limit(2)->get()
                    ->each(function (Jersey $jersey) use ($order): void {
                        $stock = $jersey->stock;
                        $stock['M'] = max(0, (int) ($stock['M'] ?? 0) - 1);
                        $jersey->forceFill([
                            'stock' => $stock,
                            'sold' => (int) $jersey->sold + 1,
                        ])->save();

                        $order->items()->create([
                            'jersey_id' => $jersey->id,
                            'product_name' => $jersey->name,
                            'product_sku' => $jersey->sku,
                            'product_slug' => $jersey->slug,
                            'image_path' => $jersey->image,
                            'product_weight' => $jersey->weight,
                            'size' => 'M',
                            'quantity' => 1,
                            'unit_price' => $jersey->price,
                            'nameset_unit_price' => $jersey->price_nameset,
                            'line_total' => $jersey->price,
                            'nameset' => [],
                            'stock_reserved_at' => now(),
                            'stock_consumed_at' => now(),
                        ]);
                    });
            }

            $subtotal = (int) $order->items()->sum('line_total');
            $shipping = [
                'code' => 'tcs',
                'service' => 'Overnight',
                'description' => 'Domestic delivery across Pakistan',
                'value' => 320,
                'etd' => '1-2 days',
            ];

            $order->forceFill([
                'user_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_email' => $customer->email,
                'subtotal' => $subtotal,
                'shipping_amount' => 320,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_price' => $subtotal + 320,
                'courier_services' => $shipping,
                'shipping_phone' => $customer->phone,
                'shipping_address' => $customer->address,
                'shipping_province' => 'Islamabad Capital Territory',
                'shipping_city' => 'Islamabad',
                'shipping_postal_code' => '44000',
                'status' => Order::STATUS_PENDING,
                'fulfillment_status' => Order::FULFILLMENT_PROCESSING,
                'fulfillment_status_updated_at' => now(),
                'transaction_status' => 'order_placed',
                'payment_type' => 'cash_on_delivery',
                'payment_method' => 'cash_on_delivery',
                'processed_at' => now(),
                'placed_at' => now(),
            ])->save();

            $order->statusHistories()->firstOrCreate(
                ['to_status' => Order::FULFILLMENT_PROCESSING],
                [
                    'from_status' => Order::FULFILLMENT_CONFIRMED,
                    'changed_by' => User::query()->where('role', User::ROLE_ADMIN)->value('id'),
                    'note' => 'Sample order prepared for the local QA dashboard.',
                ],
            );
        });
    }
}
