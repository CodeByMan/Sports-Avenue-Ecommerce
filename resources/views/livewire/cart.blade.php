<div>
    <x-slot name="title">{{ config('app.name', 'Laravel') }} - {{ __('Shopping Cart') }}</x-slot>

    @php
        $itemCount = $order?->items->count() ?? 0;
        $subtotal = (int) ($order?->subtotal ?: $order?->total_price ?: 0);
        $shippingAmount = $selectedCourierCost ? (int) last(explode(',', $selectedCourierCost)) : 0;
        $grandTotal = $subtotal + $shippingAmount;
    @endphp

    <span class="sr-only">{{ __('Shopping Cart') }}</span>

    <section class="cart-page">
        <div class="store-container">
            <x-auth-session-status :status="session('status')" class="mb-7" />

            <div class="cart-page__heading">
                <h1>{{ __('Your cart') }}</h1>
                <p>{{ trans_choice(':count product in your cart|:count products in your cart', $itemCount, ['count' => $itemCount]) }}</p>
            </div>

            <div class="cart-layout">
                <div class="cart-main">
                    <div class="cart-items-panel">
                        @if ($order && $itemCount > 0)
                            @foreach ($order->items as $item)
                                <article class="cart-item">
                                    <div class="cart-item__image">
                                        <x-store-jersey-image :image-name="$item->image_path" :product-name="$item->product_name" :fallback="$item->image_url" :alt="$item->product_name" />
                                    </div>
                                    <div class="cart-item__details">
                                        <h2>{{ $item->product_name }}</h2>
                                        <p>{{ __('Size') }}: {{ $item->size }}</p>
                                        <p>{{ __('Quantity') }}: {{ $item->quantity }}</p>
                                        @if (count($item->nameset ?? []) > 0)
                                            <p>{{ __('Nameset') }}:
                                                @foreach (($item->nameset ?? []) as $nameset)
                                                    {{ $nameset['name'] }} {{ $nameset['number'] }}{{ ! $loop->last ? ', ' : '' }}
                                                @endforeach
                                            </p>
                                        @endif
                                        <p>{{ __('Price') }}: {{ currencyFormat($item->unit_price) }} / {{ __('per item') }}</p>
                                    </div>
                                    <div class="cart-item__price">{{ currencyFormat($item->line_total) }}</div>
                                    <div class="cart-item__controls">
                                        <span class="cart-item__quantity" aria-label="{{ __('Quantity') }}">{{ $item->quantity }}</span>
                                        <button type="button" class="cart-item__remove" wire:click.prevent="removeJersey({{ $item->id }})" aria-label="{{ __('Remove :product', ['product' => $item->product_name]) }}">×</button>
                                    </div>
                                </article>
                            @endforeach
                        @else
                            <div class="empty-state empty-state--compact">
                                <h2>{{ __('Your cart is empty') }}</h2>
                                <p>{{ __('Browse the catalogue and add an available jersey.') }}</p>
                                <a href="{{ route('jersey') }}" class="product-actions__primary">{{ __('Shop products') }}</a>
                            </div>
                        @endif
                    </div>

                    @if ($order && $itemCount > 0)
                        <section class="shipping-panel" aria-labelledby="shipping-title">
                            <div class="shipping-panel__heading">
                                <p class="section-kicker">{{ __('Delivery') }}</p>
                                <h2 id="shipping-title">{{ __('Shipping address') }}</h2>
                            </div>

                            <div class="shipping-grid">
                                <label class="form-field">
                                    <span>{{ __('Customer name') }}</span>
                                    <input type="text" autocomplete="name" wire:model.blur="customerName">
                                    @error('customerName') <small class="field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="form-field">
                                    <span>{{ __('Email address') }}</span>
                                    <input type="email" autocomplete="email" wire:model.blur="customerEmail">
                                    @error('customerEmail') <small class="field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="form-field">
                                    <span>{{ __('Phone') }}</span>
                                    <input type="tel" inputmode="tel" autocomplete="tel" placeholder="03XXXXXXXXX" wire:model.blur="phone">
                                    @error('phone') <small class="field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="form-field">
                                    <span>{{ __('Province') }}</span>
                                    <select wire:model.live="selectedProvince">
                                        <option value="">{{ __('Choose province') }}</option>
                                        @foreach ($provinces as $province)
                                            <option value="{{ $province->id }}">{{ $province->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedProvince') <small class="field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="form-field">
                                    <span>{{ __('City') }}</span>
                                    <select wire:model.live="selectedCity">
                                        <option value="">{{ __('Choose city') }}</option>
                                        @foreach (($cities ?? []) as $city)
                                            <option value="{{ $city->id }}">{{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedCity') <small class="field-error">{{ $message }}</small> @enderror
                                </label>

                                <label class="form-field">
                                    <span>{{ __('Courier service') }}</span>
                                    <select wire:model.live="selectedCourier">
                                        <option value="">{{ __('Choose courier services') }}</option>
                                        @foreach ($couriers as $courier)
                                            <option value="{{ $courier['id'] }}">{{ $courier['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('selectedCourier') <small class="field-error">{{ $message }}</small> @enderror
                                </label>

                                @if ($courierCosts)
                                    <label class="form-field shipping-grid__full">
                                        <span>{{ __('Delivery option') }}</span>
                                        <select wire:model.live="selectedCourierCost">
                                            <option value="">{{ __('Choose courier service costs') }}</option>
                                            @foreach ($courierCosts['costs'] as $cost)
                                                <option value="{{ $courierCosts['code'] }},{{ $cost['service'] }},{{ $cost['value'] }}">
                                                    {{ strtoupper($courierCosts['code']) }} · {{ $cost['service'] }} · {{ currencyFormat($cost['value']) }} · {{ $cost['etd'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('selectedCourierCost') <small class="field-error">{{ $message }}</small> @enderror
                                    </label>
                                @endif

                                <label class="form-field shipping-grid__full">
                                    <span>{{ __('Address') }}</span>
                                    <textarea rows="4" autocomplete="street-address" wire:model.blur="address"></textarea>
                                    @error('address') <small class="field-error">{{ $message }}</small> @enderror
                                </label>
                            </div>
                        </section>
                    @endif
                </div>

                @if ($order && $itemCount > 0)
                    <aside class="order-summary" aria-labelledby="summary-title">
                        <h2 id="summary-title">{{ __('Order summary') }}</h2>
                        <dl>
                            <div><dt>{{ trans_choice(':count item|:count items', $itemCount, ['count' => $itemCount]) }}</dt><dd>{{ currencyFormat($subtotal) }}</dd></div>
                            <div><dt>{{ __('Delivery cost') }}</dt><dd>{{ $selectedCourierCost ? currencyFormat($shippingAmount) : __('Select delivery') }}</dd></div>
                            <div><dt>{{ __('Tax') }}</dt><dd>{{ currencyFormat(0) }}</dd></div>
                            <div><dt>{{ __('Discount') }}</dt><dd>{{ currencyFormat(0) }}</dd></div>
                        </dl>
                        <div class="order-summary__total">
                            <span>{{ __('Total') }}</span>
                            <strong>{{ currencyFormat($grandTotal) }}</strong>
                        </div>

                        @error('payment') <p class="field-error">{{ $message }}</p> @enderror

                        <button type="button" class="order-summary__checkout" wire:click.prevent="checkout" wire:loading.attr="disabled" @disabled(! $selectedCourierCost)>
                            <span wire:loading.remove wire:target="checkout">{{ __('Checkout') }}</span>
                            <span wire:loading wire:target="checkout">{{ __('Processing...') }}</span>
                        </button>
                        @if (! $selectedCourierCost)
                            <p class="order-summary__hint">{{ __('Complete the delivery form to continue.') }}</p>
                        @endif
                    </aside>
                @endif
            </div>
        </div>
    </section>
</div>
