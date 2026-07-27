<div>
    <x-slot name="title">{{ config('app.name', 'Laravel') }} - {{ __('Leagues') }}</x-slot>

    <section class="catalog-page">
        <div class="store-container">
            <div class="catalog-page__heading">
                <p class="section-kicker">{{ __('Browse catalogue') }}</p>
                <h1>{{ __('Leagues') }}</h1>
                <span class="sr-only">{{ __('List of leagues') }}</span>
            </div>

            <div class="catalog-toolbar catalog-toolbar--single">
                <label class="catalog-search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path></svg>
                    <span class="sr-only">{{ __('Search leagues') }}</span>
                    <input type="search" placeholder="{{ __('Search leagues ...') }}" wire:model.live.debounce.500ms="name">
                </label>
            </div>

            <div class="league-grid">
                @forelse ($leagues as $league)
                    <a href="{{ route('jersey', ['name' => $league->slug]) }}" class="league-card">
                        <div class="league-card__image">
                            <x-store-league-image :league-name="$league->name" :country="$league->country" :fallback="$league->getImage()" :alt="$league->name" />
                        </div>
                        <div>
                            <h2>{{ $league->name }}</h2>
                            <p>{{ $league->country }}</p>
                        </div>
                    </a>
                @empty
                    <div class="empty-state"><h3>{{ __('League not available') }}</h3></div>
                @endforelse
            </div>

            <div class="store-pagination">{{ $leagues->onEachSide(3)->links() }}</div>
        </div>
    </section>
</div>
