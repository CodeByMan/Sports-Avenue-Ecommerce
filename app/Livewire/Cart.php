<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Exceptions\ShippingServiceException;
use App\Models\City;
use App\Models\Order;
use App\Models\Province;
use App\Models\User;
use App\Services\Checkout\CheckoutService;
use App\Services\Facades\Shipping;
use App\Services\Payments\OrderTotalService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

class Cart extends Component
{
    public ?Order $order = null;

    public ?Collection $provinces = null;

    public ?Collection $cities = null;

    /** @var array<int, array<string, mixed>> */
    public array $couriers = [];

    /** @var array<string, mixed> */
    public array $courierCosts = [];

    /** @var array<string, string> */
    public array $validationAttributes = [];

    public mixed $selectedProvince = null;

    public mixed $selectedCity = null;

    public ?string $customerName = null;

    public ?string $customerEmail = null;

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $selectedCourier = null;

    public ?string $selectedCourierCost = null;

    public function mount(): void
    {
        $this->order = getOrderCart();
        $this->customerName = auth()->user()->name;
        $this->customerEmail = auth()->user()->email;
        $this->phone = auth()->user()->phone;
        $this->address = auth()->user()->address;
        $this->provinces = Province::query()->orderBy('name')->get();

        if (auth()->user()->city_id !== null) {
            $city = City::query()->find(auth()->user()->city_id);
            $this->selectedCity = $city?->id;
            $this->selectedProvince = $city?->province_id;
            $this->cities = $city === null
                ? null
                : City::query()->where('province_id', $city->province_id)->orderBy('name')->get();
        }

        $this->couriers = Shipping::couriers();
        $this->validationAttributes = $this->fieldName();
        $this->checkJerseyStock();
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        $courierIds = collect($this->couriers)
            ->pluck('id')
            ->filter()
            ->values()
            ->all();

        return [
            'customerName' => ['required', 'string', 'max:120'],
            'customerEmail' => ['required', 'email:rfc', 'max:190'],
            'phone' => [
                'required',
                'string',
                'regex:/^(?:\+92|0092|0)?3\d{9}$/',
                Rule::unique(User::class, 'phone')->ignore(auth()->id()),
            ],
            'address' => ['required', 'string', 'max:500'],
            'selectedProvince' => ['required', 'integer', Rule::exists(Province::class, 'id')],
            'selectedCity' => [
                'required',
                'integer',
                Rule::exists(City::class, 'id')
                    ->where(fn ($query) => $query->where('province_id', $this->selectedProvince)),
            ],
            'selectedCourier' => ['required', 'string', Rule::in($courierIds)],
        ];
    }

    public function updated(string $propertyName, mixed $value): void
    {
        if ($propertyName === 'selectedProvince') {
            $this->cities = City::query()
                ->where('province_id', $value)
                ->orderBy('name')
                ->get();
            $this->selectedCity = null;
            $this->selectedCourier = null;
            $this->selectedCourierCost = null;
            $this->courierCosts = [];
        }

        if (array_key_exists($propertyName, $this->rules())) {
            $this->validateOnly($propertyName);
        }
    }

    public function updatedSelectedCourier(string $value): void
    {
        $this->selectedCourierCost = null;
        $this->courierCosts = [];
        $this->validate();
        $this->courierCosts = $this->loadCourierServicesCosts($value);
    }

    public function updatedSelectedCourierCost(?string $value): void
    {
        $this->validateCourierServiceCosts($value);
    }

    public function removeJersey(int $itemId): Redirector
    {

        [$order, $remainingItems] = DB::transaction(function () use ($itemId): array {
            /** @var Order|null $lockedOrder */
            $lockedOrder = Order::query()
                ->whereKey($this->order?->getKey())
                ->where('user_id', auth()->id())
                ->where('status', Order::STATUS_IN_CART)
                ->lockForUpdate()
                ->first();

            abort_if($lockedOrder === null, 403);

            $item = $lockedOrder->items()->whereKey($itemId)->first();

            if ($item === null) {
                return [$lockedOrder, $lockedOrder->items()->count()];
            }

            $item->delete();
            $remainingItems = $lockedOrder->items()->count();

            if ($remainingItems === 0) {
                $lockedOrder->delete();

                return [null, 0];
            }

            return [app(OrderTotalService::class)->recalculateCart($lockedOrder), $remainingItems];
        }, 3);

        $this->order = $order;
        $this->dispatch('cart-count-updated', count: $remainingItems);

        return to_route('cart')->with('status', trans('Jersey deleted successfully'));
    }

    public function checkout(): Redirector|RedirectResponse
    {
        $this->assertCartOwnership();
        $this->phone = $this->normalizePakistanPhone((string) $this->phone);
        $validated = $this->validate();

        $city = City::query()
            ->with('province')
            ->whereKey($validated['selectedCity'])
            ->where('province_id', $validated['selectedProvince'])
            ->firstOrFail();

        $shipping = $this->validateCourierServiceCosts(
            $this->selectedCourierCost,
            $this->loadCourierServicesCosts((string) $validated['selectedCourier']),
        );

        $order = app(CheckoutService::class)->place(
            $this->order,
            auth()->user(),
            $city,
            [
                'customer_name' => trim((string) $validated['customerName']),
                'customer_email' => trim((string) $validated['customerEmail']),
                'phone' => $this->normalizePakistanPhone((string) $validated['phone']),
                'address' => trim((string) $validated['address']),
            ],
            $shipping,
        );

        $this->order = null;
        $this->dispatch('cart-count-updated', count: 0);

        return to_route('checkout.success', ['order' => $order])
            ->with('status', trans('Your order has been placed successfully.'));
    }

    public function render(): View
    {
        return view('livewire.cart');
    }

    private function checkJerseyStock(): void
    {
        if ($this->order === null) {
            return;
        }

        $this->assertCartOwnership();
        $this->order->loadMissing('items.jersey');
        $unavailable = [];

        foreach ($this->order->items as $item) {
            $jersey = $item->jersey;
            $availableStock = (int) ($jersey?->stock[$item->size] ?? 0);

            if ($jersey === null || $availableStock < $item->quantity) {
                $unavailable[] = "{$item->product_name} size {$item->size}";
                $item->delete();
            }
        }

        if ($unavailable === []) {
            return;
        }

        $remainingItems = $this->order->items()->count();

        if ($remainingItems === 0) {
            $this->order->delete();
            $this->order = null;
        } else {
            $this->order = app(OrderTotalService::class)->recalculateCart($this->order);
        }

        session()->flash('status', [
            'title' => trans('Some cart items are no longer available and were removed.'),
            'cart' => $unavailable,
        ]);
    }

    /** @return array<string, mixed> */
    private function loadCourierServicesCosts(string $courier): array
    {
        $destinationProviderId = City::query()
            ->whereKey($this->selectedCity)
            ->where('province_id', $this->selectedProvince)
            ->value('provider_id');

        if ($destinationProviderId === null) {
            throw ValidationException::withMessages([
                'selectedCity' => trans('The selected destination is not mapped to the shipping provider.'),
            ]);
        }

        try {
            $availableCosts = Shipping::cost(
                (int) config('shipping.origin_provider_id'),
                (int) $destinationProviderId,
                $this->getTotalWeight(),
                $courier,
            )->get();
        } catch (ShippingServiceException $exception) {
            throw ValidationException::withMessages([
                'selectedCourier' => $exception->getMessage(),
            ]);
        }

        if (! isset($availableCosts[0]['costs']) || $availableCosts[0]['costs'] === []) {
            throw ValidationException::withMessages([
                'selectedCourier' => trans(
                    'The selected :attribute :value cost is not available, please choose another',
                    ['attribute' => trans('Courier Services'), 'value' => strtoupper($courier)],
                ),
            ]);
        }

        return $this->setCosts($availableCosts);
    }

    /**
     * @param  array<int, array<string, mixed>>  $availableCosts
     * @return array<string, mixed>
     */
    private function setCosts(array $availableCosts): array
    {
        $result = ['code' => (string) $availableCosts[0]['code'], 'costs' => []];

        foreach ($availableCosts[0]['costs'] as $cost) {
            $detail = $cost['cost'][0] ?? null;

            if (! is_array($detail) || ! isset($cost['service'], $detail['value'])) {
                continue;
            }

            $result['costs'][] = [
                'service' => (string) $cost['service'],
                'description' => (string) ($cost['description'] ?? ''),
                'value' => (int) $detail['value'],
                'etd' => trim((string) ($detail['etd'] ?? '')).' days',
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>|null  $availableCosts
     * @return array<string, mixed>
     */
    private function validateCourierServiceCosts(?string $value, ?array $availableCosts = null): array
    {
        if ($value === null) {
            throw ValidationException::withMessages([
                'selectedCourierCost' => trans('validation.required', ['attribute' => trans('Courier services cost')]),
            ]);
        }

        $parts = array_map('trim', explode(',', $value));

        if (count($parts) !== 3 || ! ctype_digit($parts[2])) {
            throw ValidationException::withMessages([
                'selectedCourierCost' => trans('validation.in', ['attribute' => trans('Courier services cost')]),
            ]);
        }

        $availableCosts ??= $this->courierCosts;
        [$code, $service, $amount] = $parts;

        foreach (($availableCosts['costs'] ?? []) as $cost) {
            if ((string) ($availableCosts['code'] ?? '') === $code
                && (string) ($cost['service'] ?? '') === $service
                && (int) ($cost['value'] ?? -1) === (int) $amount) {
                $this->courierCosts = $availableCosts;

                return array_merge(['code' => $code], $cost);
            }
        }

        throw ValidationException::withMessages([
            'selectedCourierCost' => trans('validation.in', ['attribute' => trans('Courier services cost')]),
        ]);
    }

    /** @return array<string, string> */
    private function fieldName(): array
    {
        return [
            'customerName' => trans('Customer name'),
            'customerEmail' => trans('Email address'),
            'phone' => trans('Phone'),
            'address' => trans('Address'),
            'selectedProvince' => trans('Province'),
            'selectedCity' => trans('City'),
            'selectedCourier' => trans('Courier Services'),
            'selectedCourierCost' => trans('Courier services cost'),
        ];
    }

    private function getTotalWeight(): int
    {
        if ($this->order === null) {
            return 0;
        }

        return (int) $this->order->items()
            ->selectRaw('COALESCE(SUM(product_weight * quantity), 0) AS aggregate_weight')
            ->value('aggregate_weight');
    }

    private function normalizePakistanPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0092')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '92')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '0'.$digits;
    }

    private function assertCartOwnership(): void
    {
        abort_unless(
            $this->order !== null
            && Order::query()
                ->whereKey($this->order->getKey())
                ->where('user_id', auth()->id())
                ->where('status', Order::STATUS_IN_CART)
                ->exists(),
            403,
        );
    }
}
