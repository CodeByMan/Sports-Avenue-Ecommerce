<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'invoice_number' => generateInvoiceNumber(),
            'subtotal' => 5500,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_price' => 5500,
            'courier_services' => null,
            'status' => Order::STATUS_IN_CART,
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
            'fulfillment_status_updated_at' => null,
            'customer_name' => null,
            'customer_email' => null,
            'shipping_phone' => null,
            'shipping_address' => null,
            'shipping_province' => null,
            'shipping_city' => null,
            'shipping_postal_code' => null,
            'payment_method' => null,
            'placed_at' => null,
        ];
    }
}
