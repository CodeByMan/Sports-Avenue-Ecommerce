<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class OrderHistory extends Component
{
    use WithPagination;

    public ?string $selectedStatus = null;

    /** @var array<string, string> */
    public array $statuses = [];

    public bool $modalButton = false;

    /** @var Collection<int, OrderItem> */
    public Collection $items;

    public static int $paginationCount = 10;

    public function mount(): void
    {
        $this->statuses = Order::fulfillmentStatusOptions();
        $this->items = new Collection;
    }

    public function updatingSelectedStatus(): void
    {
        $this->resetPage();
    }

    public function orderDetail(bool $isActive = false, ?int $orderId = null): void
    {
        $this->modalButton = $isActive;
        $this->items = $isActive && $orderId !== null
            ? $this->getItemsByOrderId($orderId)
            : new Collection;
    }

    public function render(): View
    {
        return view('livewire.order-history', ['orders' => $this->getOrders()]);
    }

    private function getOrders(): LengthAwarePaginator
    {
        return auth()->user()
            ->orders()
            ->with('items')
            ->where('status', '!=', Order::STATUS_IN_CART)
            ->when(
                $this->selectedStatus,
                fn ($query, string $status) => $query->where('fulfillment_status', $status),
            )
            ->latest('placed_at')
            ->latest('id')
            ->paginate(self::$paginationCount);
    }

    /** @return Collection<int, OrderItem> */
    private function getItemsByOrderId(int $orderId): Collection
    {
        $order = auth()->user()
            ->orders()
            ->with('items')
            ->where('status', '!=', Order::STATUS_IN_CART)
            ->find($orderId);

        return $order?->items ?? new Collection;
    }
}
