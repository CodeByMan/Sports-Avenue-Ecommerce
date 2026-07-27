<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->words(4, true);

        return [
            'order_id' => Order::factory(),
            'jersey_id' => null,
            'product_name' => Str::title($name),
            'product_sku' => 'SA-TEST-'.Str::upper(Str::random(10)),
            'product_slug' => Str::slug($name),
            'image_path' => null,
            'product_weight' => 500,
            'size' => 'M',
            'quantity' => 1,
            'unit_price' => 5000,
            'nameset_unit_price' => 750,
            'line_total' => 5000,
            'nameset' => [],
            'stock_reserved_at' => null,
            'stock_released_at' => null,
            'stock_consumed_at' => null,
        ];
    }
}
