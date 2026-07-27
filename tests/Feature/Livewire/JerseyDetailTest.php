<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\JerseyDetail;
use App\Models\Jersey;
use App\Models\User;
use Database\Seeders\JerseySeeder;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class JerseyDetailTest extends TestCase
{
    private Jersey $jersey;

    private array $data;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();

        $this->seed(JerseySeeder::class);
        $this->jersey = Jersey::bestSeller()
            ->inRandomOrder()
            ->firstOrFail();

        $this->data = [
            'size' => Jersey::$sizes[1],
            'quantity' => 1,
            'nameset' => [
                ['name' => null, 'number' => null],
            ],
        ];
    }

    #[Test]
    public function jersey_detail_page_can_be_rendered(): void
    {
        $this->get(route('jersey.detail', $this->jersey))
            ->assertOk()
            ->assertSeeText($this->jersey->name)
            ->assertSeeText(trans('Add to Cart'))
            ->assertSeeInOrder(array_values($this->jersey->stock));
    }

    #[Test]
    public function jersey_detail_page_contains_livewire_component(): void
    {
        $this->get(route('jersey.detail', $this->jersey))
            ->assertSeeLivewire(JerseyDetail::class);
    }

    #[Test]
    public function jersey_detail_page_can_fill_the_form(): void
    {
        $this->getLivewireTest()
            ->assertHasNoErrors();
    }

    #[Test]
    public function jersey_detail_page_size_validation_should_be_dispatched(): void
    {
        $this->data['size'] = 'XXL';

        $this->getLivewireTest($this->data)
            ->assertHasErrors(['size']);
    }

    #[Test]
    public function jersey_detail_page_quantity_validation_should_be_dispatched(): void
    {
        $this->data['quantity'] = 99;

        $this->getLivewireTest($this->data)
            ->assertHasErrors(['quantity']);
    }

    #[Test]
    public function jersey_detail_page_nameset_validation_should_be_dispatched(): void
    {
        $this->data['nameset'] = [['name' => 'john lennon', 'number' => null]];

        $this->getLivewireTest($this->data)
            ->assertHasErrors(['nameset.0.number']);
    }

    #[Test]
    public function not_authenticated_user_should_be_redirect_to_login_page_after_fill_the_form(): void
    {
        $this->getLivewireTest()
            ->assertHasNoErrors()
            ->assertRedirect(route('login'))
            ->assertSessionMissing('status');
    }

    #[Test]
    public function authenticated_user_should_be_redirect_to_cart_page_after_fill_the_form(): void
    {
        $this->authenticatedUser(['role' => User::$roles[2]]);
        $this->data['nameset'] = [
            ['name' => 'johnlennon', 'number' => 12],
            ['name' => null, 'number' => null],
        ];

        $this->getLivewireTest()
            ->assertHasNoErrors()
            ->assertDispatched('cart-count-updated', count: 1)
            ->assertSessionHas('status');
    }

    #[Test]
    public function quantity_defaults_to_one_and_cannot_go_below_one(): void
    {
        Livewire::test(JerseyDetail::class, [$this->jersey])
            ->assertSet('quantity', 1)
            ->call('decrementQuantity')
            ->assertSet('quantity', 1);
    }

    #[Test]
    public function quantity_increment_and_decrement_are_consistent(): void
    {
        $stock = $this->jersey->stock;
        $stock['M'] = 5;
        $this->jersey->forceFill(['stock' => $stock])->save();

        Livewire::test(JerseyDetail::class, [$this->jersey])
            ->set('size', 'M')
            ->assertSet('quantity', 1)
            ->call('incrementQuantity')
            ->assertSet('quantity', 2)
            ->call('incrementQuantity')
            ->assertSet('quantity', 3)
            ->call('decrementQuantity')
            ->assertSet('quantity', 2)
            ->call('decrementQuantity')
            ->assertSet('quantity', 1);
    }

    private function getLivewireTest(array $newData = []): Testable
    {
        $data = count($newData) > 0 ? $newData : $this->data;

        return Livewire::test(JerseyDetail::class, [$this->jersey])
            ->set('size', $data['size'])
            ->set('quantity', $data['quantity'])
            ->set('nameset', $data['nameset'])
            ->call('addToCart');
    }
}
