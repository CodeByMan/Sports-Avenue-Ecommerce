<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Jersey;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\OrderTotalService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class JerseyDetail extends Component
{
    private const MAX_NAMESETS = 5;

    public ?string $title = null;

    public ?string $size = null;

    public mixed $quantity = 1;

    public Jersey $jersey;

    /** @var array<int, array{name:?string, number:mixed}> */
    public array $nameset = [
        ['name' => null, 'number' => null],
    ];

    /** @var array<string, string> */
    protected array $validationAttributes = [];

    public function mount(Jersey $jersey): void
    {
        $jersey->loadMissing(['league', 'brand', 'category']);
        $this->title = $jersey->name;
        $this->jersey = $jersey;

        if (! auth()->check() || ! session()->has('cartData')) {
            return;
        }

        $cartData = session()->pull('cartData');

        if (is_array($cartData) && ($cartData['ip'] ?? null) === request()->ip()) {
            $this->size = isset($cartData['size']) ? (string) $cartData['size'] : null;
            $this->quantity = max(1, (int) ($cartData['quantity'] ?? 1));
            $this->nameset = is_array($cartData['nameset'] ?? null)
                ? $cartData['nameset']
                : $this->nameset;
        }
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $this->validationAttributes = ['quantity' => trans('Quantity')];

        return [
            'quantity' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($this->size !== null && (int) $value > (int) ($this->jersey->stock[$this->size] ?? 0)) {
                        $fail(trans('The selected quantity exceeds available stock.'));
                    }
                },
            ],
            'size' => ['required', 'string', Rule::in(Jersey::$sizes)],
        ];
    }

    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, ['quantity', 'size'], true)) {
            return;
        }

        $this->validateOnly($propertyName);
    }

    public function incrementQuantity(): void
    {
        $stock = $this->selectedStock();
        $current = max(1, (int) $this->quantity);

        $this->quantity = $stock > 0
            ? min($stock, $current + 1)
            : $current + 1;

        $this->validateOnly('quantity');
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max(1, (int) $this->quantity - 1);
        $this->validateOnly('quantity');
    }

    public function updatedSize(): void
    {
        $stock = $this->selectedStock();
        $this->quantity = max(1, (int) $this->quantity);

        if ($stock > 0) {
            $this->quantity = min($stock, (int) $this->quantity);
        }

        $this->validateOnly('size');
        $this->validateOnly('quantity');
    }

    public function updatedQuantity(): void
    {
        $this->quantity = max(1, (int) $this->quantity);

        $this->validateOnly('quantity');
    }

    public function addMoreNameset(): void
    {
        $this->normalizeNamesets(false);

        if (count($this->nameset) >= self::MAX_NAMESETS) {
            throw ValidationException::withMessages([
                'addMoreNameset' => trans('A maximum of :count namesets can be added.', ['count' => self::MAX_NAMESETS]),
            ]);
        }

        $this->nameset[] = ['name' => null, 'number' => null];
    }

    public function removeNameset(int $key): void
    {
        if (! array_key_exists($key, $this->nameset)) {
            return;
        }

        unset($this->nameset[$key]);
        $this->nameset = array_values($this->nameset);

        if ($this->nameset === []) {
            $this->nameset = [['name' => null, 'number' => null]];
        }
    }

    public function addToCart(): Redirector|RedirectResponse
    {
        $this->normalizeNamesets(true);
        $this->validate();
        $sessionData = $this->sessionData();

        if (! auth()->check()) {
            session()->put('cartData', $sessionData);
            session()->put('cartRoute', route('jersey.detail', $this->jersey));

            return to_route('login');
        }

        /** @var User $authenticatedUser */
        $authenticatedUser = auth()->user();

        if (! $authenticatedUser->isActive()) {
            throw ValidationException::withMessages([
                'quantity' => $authenticatedUser->accountRestrictionMessage(),
            ]);
        }

        DB::transaction(function (): void {
            /** @var Jersey $jersey */
            $jersey = Jersey::query()
                ->whereKey($this->jersey->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int) $this->quantity;
            $size = (string) $this->size;
            $availableStock = (int) ($jersey->stock[$size] ?? 0);

            if ($availableStock < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => trans('The selected quantity exceeds available stock.'),
                ]);
            }

            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail(auth()->id());
            $order = $user->orders()
                ->where('status', Order::STATUS_IN_CART)
                ->lockForUpdate()
                ->first() ?? $this->createOrder($user);

            $this->addOrderItem($order, $jersey, $this->lineTotal($jersey));
            $order = app(OrderTotalService::class)->recalculateCart($order);
            $this->dispatch('cart-count-updated', count: $order->items()->count());
        }, 3);

        return to_route('jersey.detail', $this->jersey)->with('status', [
            'title' => trans('The order has been added to your cart'),
            'icon' => '🛒',
            'paragraph' => trans('Please check your cart to complete checkout'),
        ]);
    }

    public function render(): View
    {
        return view('livewire.jersey-detail');
    }

    private function selectedStock(): int
    {
        if ($this->size === null) {
            return 0;
        }

        return max(0, (int) ($this->jersey->stock[$this->size] ?? 0));
    }

    private function normalizeNamesets(bool $forCart): void
    {
        $this->validate([
            'nameset' => ['array', 'max:'.self::MAX_NAMESETS],
            'nameset.*.name' => ['required_with:nameset.*.number', 'nullable', 'string', 'max:25'],
            'nameset.*.number' => ['required_with:nameset.*.name', 'nullable', 'integer', 'between:1,99'],
        ], attributes: [
            'nameset.*.name' => trans('Name'),
            'nameset.*.number' => trans('Number'),
        ]);

        $normalized = [];

        foreach ($this->nameset as $nameset) {
            $name = ucwords(trim((string) ($nameset['name'] ?? '')));
            $number = trim((string) ($nameset['number'] ?? ''));

            if ($name === '' && $number === '') {
                if (! $forCart) {
                    throw ValidationException::withMessages([
                        'addMoreNameset' => trans('Fill the current name and number before adding another nameset.'),
                    ]);
                }

                continue;
            }

            $normalized[] = ['name' => $name, 'number' => $number];
        }

        if ((int) $this->quantity > 0 && count($normalized) > (int) $this->quantity) {
            throw ValidationException::withMessages([
                'nameset' => trans('The number of namesets cannot exceed the jersey quantity.'),
            ]);
        }

        $this->nameset = $normalized;
    }

    /** @return array<string, mixed> */
    private function sessionData(): array
    {
        return [
            'ip' => request()->ip(),
            'size' => $this->size,
            'quantity' => (int) $this->quantity,
            'nameset' => $this->nameset,
        ];
    }

    private function lineTotal(Jersey $jersey): int
    {
        return ((int) $jersey->price * (int) $this->quantity)
            + ((int) $jersey->price_nameset * count($this->nameset));
    }

    private function createOrder(User $user): Order
    {
        return $user->orders()->create([
            'invoice_number' => generateInvoiceNumber(),
            'subtotal' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_price' => 0,
            'status' => Order::STATUS_IN_CART,
        ]);
    }

    private function addOrderItem(Order $order, Jersey $jersey, int $totalPrice): void
    {
        $order->items()->create([
            'jersey_id' => $jersey->getKey(),
            'product_name' => $jersey->name,
            'product_sku' => $jersey->sku,
            'product_slug' => $jersey->slug,
            'image_path' => $jersey->image,
            'product_weight' => $jersey->weight,
            'size' => (string) $this->size,
            'quantity' => (int) $this->quantity,
            'unit_price' => $jersey->price,
            'nameset_unit_price' => $jersey->price_nameset,
            'line_total' => $totalPrice,
            'nameset' => $this->nameset,
        ]);
    }
}
