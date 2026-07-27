<div>
    <x-slot name="title">{{ config('app.name', 'Laravel') }} - {{ __('Products') }}</x-slot>

    <section class="catalog-page">
        <div class="store-container">
            <div class="catalog-page__heading">
                <p class="section-kicker">{{ $title ?: __('Complete catalogue') }}</p>
                <h1>{{ __('Products') }}</h1>
            </div>

            <div class="catalog-toolbar catalog-toolbar--dynamic">
                <label class="catalog-filter">
                    <span class="sr-only">{{ __('Choose league') }}</span>
                    <select wire:model.live="leagueSelected">
                        <option value="">{{ __('All leagues') }}</option>
                        @foreach ($allLeagues as $slug => $name)
                            <option value="{{ $slug }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="catalog-filter">
                    <span class="sr-only">{{ __('Choose brand') }}</span>
                    <select wire:model.live="brandSelected">
                        <option value="">{{ __('All brands') }}</option>
                        @foreach ($allBrands as $slug => $name)
                            <option value="{{ $slug }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="catalog-filter">
                    <span class="sr-only">{{ __('Choose category') }}</span>
                    <select wire:model.live="categorySelected">
                        <option value="">{{ __('All categories') }}</option>
                        @foreach ($allCategories as $slug => $name)
                            <option value="{{ $slug }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="catalog-search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                    <span class="sr-only">{{ __('Search products') }}</span>
                    <input type="search" placeholder="{{ __('Search ...') }}" wire:model.live.debounce.500ms="name">
                </label>
            </div>

            <div class="catalog-section-heading">
                <h2>{{ $title ?: __('All products') }}</h2>
                <p>{{ __('Products are loaded directly from the managed MySQL catalogue.') }}</p>
            </div>

            <div class="product-grid">
                @forelse ($jerseys as $jersey)
                    <x-store-product-card :jersey="$jersey" />
                @empty
                    <div class="empty-state">
                        <h3>{{ __('Product not available') }}</h3>
                        <p>{{ __('Try a different league, brand, category, or search phrase.') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="store-pagination">
                {{ $jerseys->onEachSide(2)->links() }}
            </div>
        </div>
    </section>
</div>
