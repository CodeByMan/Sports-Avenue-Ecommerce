<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\OrderHistory;
use App\Models\Jersey;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\JerseySeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    private User $user;

    /** @var Collection<int, Order> */
    private $orders;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(JerseySeeder::class);
        $this->user = $this->authenticatedUser(['role' => User::ROLE_CUSTOMER]);
        $jerseys = Jersey::query()->take(2)->get();
        $statuses = Order::$fulfillmentStatuses;

        $this->orders = Order::factory()
            ->count(12)
            ->state(fn (): array => [
                'status' => Order::STATUS_PENDING,
                'fulfillment_status' => Arr::random($statuses),
                'payment_method' => 'cash_on_delivery',
                'placed_at' => now(),
            ])
            ->create(['user_id' => $this->user->id]);

        foreach ($this->orders as $order) {
            foreach ($jerseys as $jersey) {
                $this->createOrderItem($order, $jersey);
            }
        }
    }

    public function test_order_history_page_has_premium_order_information(): void
    {
        $order = $this->orders->sortByDesc('id')->first();

        $this->get(route('order-history'))
            ->assertSuccessful()
            ->assertSee('Order History')
            ->assertSee($order->invoice_number)
            ->assertSee('Cash on Delivery')
            ->assertSee('Payment Status')
            ->assertSee($order->items->first()->product_name);
    }

    public function test_order_history_can_filter_by_fulfillment_status(): void
    {
        $status = $this->orders->first()->fulfillment_status;
        $expected = $this->user->orders()
            ->where('fulfillment_status', $status)
            ->latest('placed_at')
            ->latest('id')
            ->pluck('invoice_number')
            ->all();

        Livewire::test(OrderHistory::class)
            ->set('selectedStatus', $status)
            ->assertSee($expected);
    }

    public function test_order_history_paginates_customer_orders(): void
    {
        Livewire::withQueryParams(['page' => 2])
            ->test(OrderHistory::class)
            ->assertSee($this->orders->sortByDesc('id')->skip(OrderHistory::$paginationCount)->first()->invoice_number);
    }

    public function test_order_detail_modal_displays_snapshot_items(): void
    {
        $order = $this->orders->first();

        Livewire::test(OrderHistory::class)
            ->call('orderDetail', true, $order->id)
            ->assertSet('modalButton', true)
            ->assertSee('Order Details')
            ->assertSee($order->items->first()->product_name);
    }

    public function test_customer_cannot_load_another_customers_order_items(): void
    {
        $otherCustomer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $otherOrder = Order::factory()->create([
            'user_id' => $otherCustomer->id,
            'status' => Order::STATUS_PENDING,
        ]);

        Livewire::test(OrderHistory::class)
            ->call('orderDetail', true, $otherOrder->id)
            ->assertSet(
                'items',
                fn (Collection $items): bool => $items->isEmpty(),
            );
    }
}
