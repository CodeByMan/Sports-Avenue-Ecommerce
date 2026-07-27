<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class NavbarAuth extends Component
{
    public int $cartCount = 0;

    public function mount(): void
    {
        if (auth()->check() && ($order = getOrderCart()) !== null) {
            $this->cartCount = $order->items()->count();
        }
    }

    #[On('cart-count-updated')]
    public function updateCartCount(int $count): void
    {
        $this->cartCount = max(0, $count);
    }

    public function render(): View
    {
        return view('livewire.navbar-auth');
    }
}
