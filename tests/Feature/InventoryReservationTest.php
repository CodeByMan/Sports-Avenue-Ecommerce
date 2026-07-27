<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Jersey;
use App\Models\League;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\PaymentInitializationService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryReservationTest extends TestCase
{
    private User $customer;

    private Jersey $jersey;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('checkout.reservation_minutes', 30);
        $this->customer = User::factory()->create();
        $league = League::factory()->create();
        $this->jersey = Jersey::factory()->create([
            'league_id' => $league->id,
            'stock' => ['S' => 0, 'M' => 1, 'L' => 0, 'XL' => 0],
            'sold' => 0,
        ]);
    }

    public function test_payment_initialization_reserves_stock_atomically(): void
    {
        $order = $this->cartFor($this->customer);

        $reserved = app(PaymentInitializationService::class)->begin($order);

        $this->assertSame(Order::STATUS_PENDING, $reserved->status);
        $this->assertSame(0, (int) $this->jersey->fresh()->stock['M']);
        $this->assertNotNull($reserved->items()->firstOrFail()->stock_reserved_at);
        $this->assertNotNull($reserved->reservation_expires_at);
    }

    public function test_second_customer_cannot_reserve_the_last_unit(): void
    {
        $firstOrder = $this->cartFor($this->customer);
        $secondCustomer = User::factory()->create();
        $secondOrder = $this->cartFor($secondCustomer);

        app(PaymentInitializationService::class)->begin($firstOrder);

        try {
            app(PaymentInitializationService::class)->begin($secondOrder);
            $this->fail('The second reservation should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment', $exception->errors());
        }

        $this->assertSame(0, (int) $this->jersey->fresh()->stock['M']);
        $this->assertSame(Order::STATUS_IN_CART, $secondOrder->fresh()->status);
        $this->assertNull($secondOrder->items()->firstOrFail()->stock_reserved_at);
    }

    public function test_safe_gateway_failure_releases_reserved_stock(): void
    {
        $order = app(PaymentInitializationService::class)->begin($this->cartFor($this->customer));

        app(PaymentInitializationService::class)->release($order);

        $this->assertSame(1, (int) $this->jersey->fresh()->stock['M']);
        $this->assertSame(Order::STATUS_IN_CART, $order->fresh()->status);
        $this->assertNotNull($order->items()->firstOrFail()->stock_released_at);
    }

    public function test_expired_reservation_is_released_by_scheduled_command(): void
    {
        $order = app(PaymentInitializationService::class)->begin($this->cartFor($this->customer));
        $order->forceFill(['reservation_expires_at' => now()->subMinute()])->save();

        $this->artisan('inventory:release-expired-reservations')->assertSuccessful();

        $this->assertSame(1, (int) $this->jersey->fresh()->stock['M']);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PENDING,
            'transaction_status' => 'reservation_expired',
            'reservation_expires_at' => null,
        ]);
    }

    private function cartFor(User $user): Order
    {
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal' => $this->jersey->price,
            'total_price' => $this->jersey->price,
            'status' => Order::STATUS_IN_CART,
        ]);
        $this->createOrderItem($order, $this->jersey);

        return $order;
    }
}
