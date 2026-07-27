<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Cart;
use App\Models\City;
use App\Models\Jersey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\JerseySeeder;
use Database\Seeders\RegionSeeder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CartTest extends TestCase
{
    private User $user;

    private Order $order;

    private Collection $jerseys;

    /** @var array<string, mixed> */
    private array $data;

    private string $invoiceNumber;

    private string $selectedCourierCost = 'leopards,Leopards Overnight,240';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
        $this->seed([RegionSeeder::class, JerseySeeder::class]);

        $province = Province::query()->where('provider_id', 101)->firstOrFail();
        $city = City::query()->where('provider_id', 1001)->firstOrFail();
        $this->user = $this->authenticatedUser([
            'role' => User::ROLE_CUSTOMER,
            'name' => 'Hamza Ahmed',
            'email' => 'hamza@example.test',
            'phone' => '03221234567',
            'address' => 'F-10 Markaz, Islamabad',
            'city_id' => $city->id,
        ]);
        $this->jerseys = Jersey::bestSeller()->orderBy('id')->take(2)->get();
        $subtotal = (int) $this->jerseys->sum('price');

        $this->order = Order::factory()->create([
            'user_id' => $this->user->id,
            'subtotal' => $subtotal,
            'total_price' => $subtotal,
            'status' => Order::STATUS_IN_CART,
        ]);

        foreach ($this->jerseys as $jersey) {
            $this->createOrderItem($this->order, $jersey);
        }

        $this->invoiceNumber = $this->order->invoice_number;
        $this->data = [
            'customerName' => 'Hamza Ahmed',
            'customerEmail' => 'hamza@example.test',
            'phone' => '03221234567',
            'selectedProvince' => $province->id,
            'selectedCity' => $city->id,
            'selectedCourier' => 'leopards',
            'address' => 'Block 7, Gulshan-e-Iqbal, Karachi',
            'selectedCourierCost' => $this->selectedCourierCost,
        ];
    }

    public function test_cart_page_can_be_rendered(): void
    {
        $response = $this->get(route('cart'))
            ->assertSee(trans('Shopping Cart'))
            ->assertSee((string) $this->jerseys->count());

        foreach ($this->order->items as $item) {
            $response->assertSee($item->product_name);
        }
    }

    public function test_cart_page_contains_livewire_component(): void
    {
        $this->get(route('cart'))->assertSeeLivewire(Cart::class);
    }

    public function test_cart_page_can_remove_selected_jersey(): void
    {
        /** @var OrderItem $item */
        $item = $this->order->items()->firstOrFail();
        $remainingTotal = (int) $this->order->items()->where('id', '!=', $item->id)->sum('line_total');

        Livewire::test(Cart::class)
            ->call('removeJersey', $item->id)
            ->assertRedirect(route('cart'))
            ->assertSessionHas('status');

        $this->assertSame(1, $this->order->items()->count());
        $this->assertDatabaseHas('orders', [
            'invoice_number' => $this->invoiceNumber,
            'subtotal' => $remainingTotal,
            'total_price' => $remainingTotal,
        ]);
    }

    public function test_cart_page_can_fill_the_form(): void
    {
        $this->getLivewireTest()
            ->assertSet('customerName', $this->data['customerName'])
            ->assertSet('customerEmail', $this->data['customerEmail'])
            ->assertSet('phone', $this->data['phone'])
            ->assertSet('selectedProvince', $this->data['selectedProvince'])
            ->assertSet('selectedCity', $this->data['selectedCity'])
            ->assertSet('address', $this->data['address'])
            ->assertSet('selectedCourier', $this->data['selectedCourier'])
            ->assertSet('selectedCourierCost', $this->data['selectedCourierCost']);
    }

    public function test_cart_page_validation_should_be_dispatched(): void
    {
        $this->withExceptionHandling();

        $this->getLivewireTest([
            'customerName' => '',
            'customerEmail' => 'invalid-email',
            'phone' => '123',
            'selectedProvince' => 999,
            'selectedCity' => 999,
            'address' => '',
            'selectedCourier' => 'unsupported',
        ])
            ->call('checkout')
            ->assertHasErrors([
                'customerName',
                'customerEmail',
                'phone',
                'selectedProvince',
                'selectedCity',
                'address',
                'selectedCourier',
            ]);
    }

    public function test_checkout_rejects_a_tampered_shipping_price(): void
    {
        $this->withExceptionHandling();

        $this->getLivewireTest([
            'selectedCourierCost' => 'leopards,Leopards Overnight,1',
        ])
            ->call('checkout')
            ->assertHasErrors(['selectedCourierCost']);

        $this->assertDatabaseHas('orders', [
            'invoice_number' => $this->invoiceNumber,
            'status' => Order::STATUS_IN_CART,
        ]);
    }

    public function test_another_customer_cannot_view_the_checkout_success_page(): void
    {
        $this->withExceptionHandling();

        $otherCustomer = $this->createUser(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($otherCustomer)
            ->get(route('checkout.success', ['order' => $this->order]))
            ->assertNotFound();
    }

    public function test_blocked_customer_cannot_checkout(): void
    {
        $this->withExceptionHandling();
        $this->user->forceFill([
            'status' => User::STATUS_BLOCKED,
            'blocked_at' => now(),
            'status_changed_at' => now(),
            'status_reason' => 'Fraud review.',
        ])->save();

        $this->getLivewireTest()
            ->call('checkout')
            ->assertHasErrors(['payment']);

        $this->assertDatabaseHas('orders', [
            'invoice_number' => $this->invoiceNumber,
            'status' => Order::STATUS_IN_CART,
        ]);
    }

    public function test_successful_checkout_places_order_saves_shipping_consumes_stock_and_clears_cart(): void
    {
        $cost = 240;
        $subtotal = (int) $this->order->subtotal;
        $expectedTotal = $subtotal + $cost;
        $stockBefore = $this->jerseys->mapWithKeys(
            fn (Jersey $jersey): array => [$jersey->id => (int) $jersey->stock['M']],
        );
        $soldBefore = $this->jerseys->mapWithKeys(
            fn (Jersey $jersey): array => [$jersey->id => (int) $jersey->sold],
        );

        $this->getLivewireTest()
            ->call('checkout')
            ->assertHasNoErrors()
            ->assertRedirect(route('checkout.success', ['order' => $this->invoiceNumber]));

        $this->assertDatabaseHas('orders', [
            'invoice_number' => $this->invoiceNumber,
            'customer_name' => 'Hamza Ahmed',
            'customer_email' => 'hamza@example.test',
            'shipping_phone' => '03221234567',
            'shipping_address' => 'Block 7, Gulshan-e-Iqbal, Karachi',
            'shipping_province' => 'Sindh',
            'shipping_city' => 'Karachi',
            'shipping_postal_code' => '74000',
            'subtotal' => $subtotal,
            'shipping_amount' => $cost,
            'total_price' => $expectedTotal,
            'status' => Order::STATUS_PENDING,
            'transaction_status' => 'order_placed',
            'payment_type' => 'cash_on_delivery',
            'payment_method' => 'cash_on_delivery',
        ]);

        $placedOrder = $this->order->fresh();
        $this->assertNotNull($placedOrder->placed_at);
        $this->assertNull($placedOrder->reservation_expires_at);
        $this->assertNull(getOrderCart());
        $this->assertSame(2, $placedOrder->items()->whereNotNull('stock_consumed_at')->count());

        foreach ($this->jerseys as $jersey) {
            $fresh = $jersey->fresh();
            $this->assertSame($stockBefore[$jersey->id] - 1, (int) $fresh->stock['M']);
            $this->assertSame($soldBefore[$jersey->id] + 1, (int) $fresh->sold);
        }

        $this->get(route('checkout.success', ['order' => $placedOrder]))
            ->assertOk()
            ->assertSee($this->invoiceNumber)
            ->assertSee('Order placed successfully');
    }

    /** @param array<string, mixed> $newData */
    private function getLivewireTest(array $newData = []): Testable
    {
        $data = $newData === [] ? $this->data : array_merge($this->data, $newData);

        $test = Livewire::test(Cart::class)
            ->set('customerName', $data['customerName'])
            ->set('customerEmail', $data['customerEmail'])
            ->set('phone', $data['phone'])
            ->set('selectedProvince', $data['selectedProvince'])
            ->set('selectedCity', $data['selectedCity'])
            ->set('address', $data['address'])
            ->set('selectedCourier', $data['selectedCourier']);

        return array_key_exists('selectedCourierCost', $data)
            ? $test->set('selectedCourierCost', $data['selectedCourierCost'])
            : $test;
    }
}
