<div>
    <x-slot name="title">
        {{ config('app.name', 'Laravel') }} - {{ __('Order History') }}
    </x-slot>

    <section class="order-history-page" aria-labelledby="order-history-title">
        <div class="store-container">
            <div class="order-history-heading">
                <div>
                    <p class="section-kicker">{{ __('Your purchases') }}</p>
                    <h1 id="order-history-title">{{ __('Order History') }}</h1>
                    <p>{{ __('Track delivery progress, payment information, and purchased products.') }}</p>
                </div>

                <label class="order-history-filter">
                    <span>{{ __('Filter by status') }}</span>
                    <select wire:model.live="selectedStatus">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="order-history-list">
                @forelse ($orders as $order)
                    <article class="order-history-card">
                        <header class="order-history-card__header">
                            <div>
                                <span class="order-history-card__label">{{ __('Order Number') }}</span>
                                <h2>{{ $order->invoice_number }}</h2>
                                <time datetime="{{ $order->placed_at?->toAtomString() ?? $order->created_at?->toAtomString() }}">
                                    {{ ($order->placed_at ?? $order->created_at)?->format('M j, Y · g:i A') }}
                                </time>
                            </div>

                            <span class="order-status-badge {{ $order->fulfillment_badge_class }}">
                                {{ $order->fulfillment_status_label }}
                            </span>
                        </header>

                        <div class="order-history-card__items">
                            @foreach ($order->items->take(3) as $item)
                                <div class="order-history-item">
                                    <span class="order-history-item__image">
                                        <x-store-jersey-image
                                            :image-name="$item->image_path"
                                            :product-name="$item->product_name"
                                            :fallback="$item->image_url"
                                            :alt="$item->product_name"
                                        />
                                    </span>
                                    <div>
                                        <strong>{{ $item->product_name }}</strong>
                                        <span>{{ __('Size') }} {{ $item->size }} · {{ __('Quantity') }} {{ $item->quantity }}</span>
                                    </div>
                                    <b>{{ currencyFormat((int) $item->line_total) }}</b>
                                </div>
                            @endforeach

                            @if ($order->items->count() > 3)
                                <button type="button" class="order-history-more" wire:click="orderDetail(true, {{ $order->id }})">
                                    {{ __('View :count more items', ['count' => $order->items->count() - 3]) }}
                                </button>
                            @endif
                        </div>

                        <footer class="order-history-card__footer">
                            <dl class="order-history-meta">
                                <div>
                                    <dt>{{ __('Payment Method') }}</dt>
                                    <dd>{{ $order->payment_method_label }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('Payment Status') }}</dt>
                                    <dd>
                                        <span class="payment-status payment-status--{{ $order->payment_status_color }}">
                                            {{ $order->payment_status_label }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt>{{ __('Items') }}</dt>
                                    <dd>{{ $order->items->sum('quantity') }}</dd>
                                </div>
                                <div class="order-history-meta__total">
                                    <dt>{{ __('Total') }}</dt>
                                    <dd>{{ currencyFormat((int) $order->total_price) }}</dd>
                                </div>
                            </dl>

                            <button type="button" class="order-history-detail-button" wire:click="orderDetail(true, {{ $order->id }})">
                                {{ __('View Details') }}
                            </button>
                        </footer>
                    </article>
                @empty
                    <div class="order-history-empty">
                        <h2>{{ __('No orders found') }}</h2>
                        <p>{{ __('Orders matching the selected status will appear here.') }}</p>
                        <a href="{{ route('jersey') }}">{{ __('Continue shopping') }}</a>
                    </div>
                @endforelse
            </div>

            @if ($orders->hasPages())
                <div class="order-history-pagination">
                    {{ $orders->onEachSide(1)->links() }}
                </div>
            @endif
        </div>

        @if ($modalButton)
            <div class="order-detail-modal" role="dialog" aria-modal="true" aria-labelledby="order-detail-title">
                <button type="button" class="order-detail-modal__backdrop" wire:click="orderDetail" aria-label="{{ __('Close order details') }}"></button>
                <div class="order-detail-modal__panel">
                    <header>
                        <h2 id="order-detail-title">{{ __('Order Details') }}</h2>
                        <button type="button" wire:click="orderDetail" aria-label="{{ __('Close order details') }}">×</button>
                    </header>

                    <div class="order-detail-modal__items">
                        @foreach ($items as $item)
                            <article class="order-detail-modal__item">
                                <x-store-jersey-image
                                    :image-name="$item->image_path"
                                    :product-name="$item->product_name"
                                    :fallback="$item->image_url"
                                    :alt="$item->product_name"
                                />
                                <div>
                                    <h3>{{ $item->product_name }}</h3>
                                    <p>{{ __('Size') }}: {{ $item->size }}</p>
                                    <p>{{ __('Quantity') }}: {{ $item->quantity }}</p>
                                    @if (($item->nameset ?? []) !== [])
                                        <p>{{ __('Nameset') }}: {{ namesetFormat($item->nameset) }}</p>
                                    @endif
                                </div>
                                <strong>{{ currencyFormat((int) $item->line_total) }}</strong>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </section>
</div>
