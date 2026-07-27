<div>
    <x-slot name="title">{{ config('app.name', 'Laravel') }} - {{ $jersey->name }}</x-slot>

    @php
        $availableSizes = collect($jersey->stock)->filter(fn ($stock) => (int) $stock > 0);
        $selectedStock = $size ? (int) ($jersey->stock[$size] ?? 0) : 0;
        $currentQuantity = max(1, (int) ($quantity ?: 1));
        $galleryImages = $jersey->getGalleryImages();
        $mainGalleryImage = $galleryImages[0]['url'] ?? $jersey->getImage();
    @endphp

    <span class="sr-only">{{ __(':product details', ['product' => $title]) }}</span>
    <span class="sr-only">@foreach ($jersey->stock as $stockValue) {{ $stockValue }} @endforeach</span>

    <section class="product-detail-page">
        <div class="store-container">
            <nav class="product-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">{{ __('Home') }}</a>
                <span>/</span>
                <a href="{{ route('jersey') }}">{{ __('Jerseys') }}</a>
                <span>/</span>
                <span>{{ $jersey->name }}</span>
            </nav>

            <x-auth-session-status :status="session('status')" class="mb-7" />

            <div class="product-detail-grid">
                <div class="product-gallery" data-product-gallery>
                    <div class="product-gallery__main" data-gallery-stage>
                        <img src="{{ $mainGalleryImage }}" alt="{{ $jersey->name }}" data-gallery-main-image>

                        <div class="product-gallery__zoom" aria-label="{{ __('Image zoom controls') }}">
                            <button type="button" data-gallery-zoom-out aria-label="{{ __('Zoom out') }}">−</button>
                            <button type="button" data-gallery-reset aria-label="{{ __('Reset zoom') }}">
                                <span data-gallery-zoom-value>100%</span>
                            </button>
                            <button type="button" data-gallery-zoom-in aria-label="{{ __('Zoom in') }}">+</button>
                        </div>
                    </div>

                    <div class="product-gallery__thumbs" aria-label="{{ __('Product image thumbnails') }}">
                        @foreach ($galleryImages as $index => $galleryImage)
                            <button
                                type="button"
                                class="product-gallery__thumb {{ $index === 0 ? 'is-active' : '' }}"
                                data-gallery-thumbnail
                                data-gallery-scale="1"
                                aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                            >
                                <img src="{{ $galleryImage['url'] }}" alt="{{ __(':product image :number', ['product' => $jersey->name, 'number' => $index + 1]) }}">
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="product-information">
                    <p class="product-information__type">{{ $jersey->category?->name ?? $jersey->type }}</p>
                    <h1>{{ $jersey->name }}</h1>
                    <p class="product-information__price">{{ currencyFormat($jersey->price) }}</p>

                    @if ($jersey->description)
                        <p class="product-information__intro">{{ $jersey->description }}</p>
                    @endif

                    <div class="product-facts">
                        <span>{{ __('League') }} <strong>{{ $jersey->league->name }}</strong></span>
                        @if ($jersey->brand)
                            <span>{{ __('Brand') }} <strong>{{ $jersey->brand->name }}</strong></span>
                        @endif
                        <span>{{ __('Weight') }} <strong>{{ $jersey->weight }} g</strong></span>
                    </div>

                    <fieldset class="product-option-group">
                        <legend>{{ __('Size') }}</legend>
                        <div class="size-selector">
                            @foreach ($jersey->stock as $key => $stock)
                                <label class="size-option {{ (int) $stock < 1 ? 'is-disabled' : '' }}">
                                    <input type="radio" value="{{ $key }}" wire:model.live="size" @disabled((int) $stock < 1)>
                                    <span>{{ $key }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('size') <p class="field-error">{{ $message }}</p> @enderror
                    </fieldset>

                    <div class="product-option-group">
                        <p class="product-option-label">{{ __('Quantity') }}</p>
                        <div class="quantity-control">
                            <button type="button" wire:click="decrementQuantity" @disabled($currentQuantity <= 1) aria-label="{{ __('Decrease quantity') }}">−</button>
                            <input type="number" min="1" value="{{ $currentQuantity }}" @if ($selectedStock > 0) max="{{ $selectedStock }}" @endif wire:model.blur="quantity" aria-label="{{ __('Quantity') }}">
                            <button type="button" wire:click="incrementQuantity" @disabled($selectedStock > 0 && $currentQuantity >= $selectedStock) aria-label="{{ __('Increase quantity') }}">+</button>
                        </div>
                        @error('quantity') <p class="field-error">{{ $message }}</p> @enderror
                        @if ($size)
                            <p class="stock-note">{{ trans_choice(':count item available|:count items available', $selectedStock, ['count' => $selectedStock]) }}</p>
                        @endif
                    </div>

                    @if ($jersey->description)
                        <div class="product-description">
                            <h2>{{ __('Description') }}</h2>
                            <p>{{ $jersey->description }}</p>
                        </div>
                    @endif

                    @if ($size && $quantity)
                        <div class="nameset-panel">
                            <div class="nameset-panel__heading">
                                <div>
                                    <h2>{{ __('Nameset') }}</h2>
                                    <p>{{ __('Optional personalization') }} · {{ currencyFormat($jersey->price_nameset) }}</p>
                                </div>
                            </div>

                            <div class="nameset-list">
                                @foreach ($nameset as $key => $ns)
                                    <div class="nameset-row">
                                        <label>
                                            <span>{{ __('Name') }}</span>
                                            <input type="text" maxlength="25" placeholder="{{ __('Name') }}" wire:model.blur="nameset.{{ $key }}.name">
                                            @error("nameset.$key.name") <small class="field-error">{{ $message }}</small> @enderror
                                        </label>
                                        <label>
                                            <span>{{ __('Number') }}</span>
                                            <input type="number" min="1" max="99" placeholder="00" wire:model.blur="nameset.{{ $key }}.number">
                                            @error("nameset.$key.number") <small class="field-error">{{ $message }}</small> @enderror
                                        </label>
                                        <button type="button" class="nameset-remove" wire:click.prevent="removeNameset({{ $key }})">{{ __('Remove') }}</button>
                                    </div>
                                @endforeach
                            </div>

                            @if ((int) $quantity > 1 && count($nameset) < (int) $quantity)
                                <button type="button" class="nameset-add" wire:click.prevent="addMoreNameset">+ {{ __('Add more nameset') }}</button>
                            @endif
                            @error('addMoreNameset') <p class="field-error">{{ $message }}</p> @enderror
                            @error('nameset') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="product-actions">
                        <button type="button" class="product-actions__primary" wire:click.prevent="addToCart" wire:loading.attr="disabled" @disabled(! $size || ! $quantity)>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8V6a5 5 0 0 1 10 0v2"></path><path d="M5 8h14l1 13H4L5 8Z"></path></svg>
                            <span wire:loading.remove wire:target="addToCart">{{ __('Add to Cart') }}</span>
                            <span wire:loading wire:target="addToCart">{{ __('Adding...') }}</span>
                        </button>
                        <a href="{{ route('cart') }}" class="product-actions__secondary">{{ __('View Cart') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
