<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Models\Jersey;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\JerseySeeder;
use Database\Seeders\LeagueSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelOrderFeatureTest extends TestCase
{
    private User $user;

    private Collection $orders;

    private Order $order;

    private Collection $jerseys;

    private int $paginationCount = 10;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
        $this->seed(LeagueSeeder::class);
        $this->seed(JerseySeeder::class);
        $this->user = $this->authenticatedUser(['role' => User::ROLE_ADMIN]);
        $this->jerseys = Jersey::bestSeller()->inRandomOrder()->take(12)->get();
        $total = (int) $this->jerseys->sum('price');
        $this->orders = Order::factory()
            ->count(19)
            ->state(fn (): array => ['status' => Arr::random([Order::STATUS_PENDING, Order::STATUS_SUCCESS, Order::STATUS_FAILED, Order::STATUS_REVIEW])])
            ->create([
                'user_id' => $this->user->id,
                'subtotal' => $total,
                'total_price' => $total,
            ]);

        foreach ($this->orders as $order) {
            foreach ($this->jerseys as $jersey) {
                $this->createOrderItem($order, $jersey);
            }
        }

        /** @var Order $order */
        $order = $this->orders->random();
        $this->order = $order;
    }

    public function test_order_menu_list_can_be_rendered(): void
    {
        $this->get(OrderResource::getUrl())
            ->assertSuccessful()
            ->assertSee(trans('List of orders'));
    }

    public function test_order_menu_list_can_show_table_records(): void
    {
        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords($this->getOrdersPaginationData());
    }

    public function test_order_menu_list_pagination_page_two_can_be_rendered(): void
    {
        Livewire::withQueryParams(['page' => 2])
            ->test(ListOrders::class)
            ->assertCanSeeTableRecords($this->getOrdersPaginationData(true));
    }

    public function test_order_menu_list_can_search_order_by_invoice_number(): void
    {
        Livewire::test(ListOrders::class)
            ->searchTable($this->order->invoice_number)
            ->assertCanSeeTableRecords($this->getOrdersPaginationData(invoiceNumber: $this->order->invoice_number));
    }

    public function test_order_menu_list_can_filter_order_by_status(): void
    {
        $status = Arr::random([Order::STATUS_PENDING, Order::STATUS_SUCCESS, Order::STATUS_FAILED, Order::STATUS_REVIEW]);

        Livewire::test(ListOrders::class)
            ->filterTable('status', $status)
            ->assertCanSeeTableRecords($this->getOrdersPaginationData(status: $status));
    }

    public function test_order_menu_list_combines_search_and_status_filter(): void
    {
        Livewire::test(ListOrders::class)
            ->searchTable($this->order->invoice_number)
            ->filterTable('status', $this->order->status)
            ->assertCanSeeTableRecords($this->getOrdersPaginationData(
                invoiceNumber: $this->order->invoice_number,
                status: $this->order->status,
            ));
    }

    public function test_order_menu_view_can_be_rendered(): void
    {
        $this->get(OrderResource::getUrl('view', ['record' => $this->order->invoice_number]))
            ->assertSuccessful()
            ->assertSee(trans('Order details'));
    }

    public function test_order_menu_view_shows_snapshot_records(): void
    {
        Livewire::withQueryParams(['record' => $this->order->invoice_number])
            ->test(ViewOrder::class)
            ->assertCanSeeTableRecords($this->getItemsPaginationData($this->order->invoice_number));
    }

    public function test_order_menu_view_pagination_page_two_can_be_rendered(): void
    {
        Livewire::withQueryParams(['record' => $this->order->invoice_number, 'page' => 2])
            ->test(ViewOrder::class)
            ->assertCanSeeTableRecords($this->getItemsPaginationData($this->order->invoice_number, true));
    }

    public function test_order_menu_view_shows_customer_and_shipping_details(): void
    {
        $this->order->forceFill([
            'customer_name' => 'Hamza Ahmed',
            'customer_email' => 'hamza@example.test',
            'shipping_phone' => '03221234567',
            'shipping_address' => 'F-10 Markaz',
            'shipping_province' => 'Islamabad Capital Territory',
            'shipping_city' => 'Islamabad',
            'shipping_postal_code' => '44000',
            'payment_method' => 'cash_on_delivery',
        ])->save();

        $this->get(OrderResource::getUrl('view', ['record' => $this->order->invoice_number]))
            ->assertSuccessful()
            ->assertSee('Hamza Ahmed')
            ->assertSee('hamza@example.test')
            ->assertSee('Islamabad Capital Territory')
            ->assertSee('Cash on Delivery');
    }

    public function test_admin_can_update_order_fulfillment_status_from_the_orders_table(): void
    {
        $this->order->forceFill([
            'status' => Order::STATUS_PENDING,
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
        ])->save();

        Livewire::test(ListOrders::class)
            ->callTableAction('updateWorkflowStatus', $this->order, data: [
                'fulfillment_status' => Order::FULFILLMENT_CONFIRMED,
                'note' => 'Payment and stock confirmed.',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'fulfillment_status' => Order::FULFILLMENT_CONFIRMED,
        ]);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $this->order->id,
            'from_status' => Order::FULFILLMENT_PENDING,
            'to_status' => Order::FULFILLMENT_CONFIRMED,
        ]);
    }

    public function test_staff_can_view_orders_and_update_status(): void
    {
        $staff = $this->authenticatedUser(['role' => User::ROLE_STAFF]);
        $this->order->forceFill([
            'status' => Order::STATUS_PENDING,
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
        ])->save();

        $this->get(OrderResource::getUrl())->assertSuccessful();

        Livewire::test(ListOrders::class)
            ->callTableAction('updateWorkflowStatus', $this->order, data: [
                'fulfillment_status' => Order::FULFILLMENT_CONFIRMED,
            ]);

        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $this->order->id,
            'changed_by' => $staff->id,
            'to_status' => Order::FULFILLMENT_CONFIRMED,
        ]);
    }

    private function getOrdersPaginationData(
        bool $secondPage = false,
        ?string $invoiceNumber = null,
        ?string $status = null,
    ): Collection {
        $orders = $this->user->orders()->latest('id')->take($this->paginationCount);

        if ($invoiceNumber !== null) {
            $orders->where('invoice_number', 'LIKE', "%{$invoiceNumber}%");
        }
        if ($status !== null) {
            $orders->where('status', $status);
        }
        if ($secondPage) {
            $orders->skip($this->paginationCount);
        }

        return $orders->get();
    }

    private function getItemsPaginationData(string $invoiceNumber, bool $secondPage = false): Collection
    {
        $order = $this->user->orders()->where('invoice_number', $invoiceNumber)->firstOrFail();
        $items = $order->items()->take($this->paginationCount);

        if ($secondPage) {
            $items->skip($this->paginationCount);
        }

        return $items->orderBy('id')->get();
    }
}
