<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\City;
use App\Models\Jersey;
use App\Models\Order;
use App\Models\Province;
use App\Models\User;
use App\Services\Facades\Shipping;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

class PakistanDemoDataTest extends TestCase
{
    public function test_demo_data_uses_pakistan_regions_users_couriers_and_shipping_snapshots(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertEqualsCanonicalizing([
            'Punjab',
            'Sindh',
            'Islamabad Capital Territory',
            'Khyber Pakhtunkhwa',
            'Balochistan',
            'Gilgit Baltistan',
            'Azad Kashmir',
        ], Province::query()->pluck('name')->all());

        foreach (['Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Faisalabad', 'Multan', 'Peshawar', 'Quetta'] as $city) {
            $this->assertDatabaseHas('cities', ['name' => $city]);
        }

        $this->assertDatabaseHas('users', [
            'email' => 'admin@sports-avenue.test',
            'name' => 'Muhammad Ali Nawaz',
            'phone' => '03001234567',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'staff@sports-avenue.test',
            'name' => 'Ayesha Khan',
            'phone' => '03111234567',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'customer@sports-avenue.test',
            'name' => 'Hamza Ahmed',
            'phone' => '03221234567',
        ]);

        $this->assertSame([
            'Leopards Courier',
            'TCS',
            'M&P',
            'BlueEx',
            'Pakistan Post',
            'Trax',
        ], collect(Shipping::couriers())->pluck('name')->all());

        $this->assertDatabaseHas('orders', [
            'invoice_number' => 'SA-DEMO-PK-0001',
            'shipping_province' => 'Islamabad Capital Territory',
            'shipping_city' => 'Islamabad',
            'shipping_postal_code' => '44000',
            'payment_method' => 'cash_on_delivery',
            'status' => Order::STATUS_PENDING,
        ]);
    }

    public function test_reseeding_keeps_the_sample_order_single_and_does_not_reset_operational_inventory(): void
    {
        $this->seed(DatabaseSeeder::class);

        $order = Order::query()->where('invoice_number', 'SA-DEMO-PK-0001')->firstOrFail();
        $stockBefore = Jersey::query()->orderBy('id')->get()->mapWithKeys(
            fn (Jersey $jersey): array => [$jersey->id => ['stock' => $jersey->stock, 'sold' => $jersey->sold]],
        );

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Order::query()->where('invoice_number', 'SA-DEMO-PK-0001')->count());
        $this->assertSame($order->items()->count(), $order->fresh()->items()->count());

        Jersey::query()->orderBy('id')->get()->each(function (Jersey $jersey) use ($stockBefore): void {
            $this->assertSame($stockBefore[$jersey->id]['stock'], $jersey->stock);
            $this->assertSame($stockBefore[$jersey->id]['sold'], $jersey->sold);
        });

        $this->assertSame(7, Province::query()->count());
        $this->assertSame(19, City::query()->count());
        $this->assertSame(3, User::query()->whereIn('email', [
            'admin@sports-avenue.test',
            'staff@sports-avenue.test',
            'customer@sports-avenue.test',
        ])->count());
    }
}
