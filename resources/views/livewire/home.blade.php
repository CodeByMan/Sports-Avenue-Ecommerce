<div>
    <x-slot name="title">{{ config('app.name', 'Laravel') }}</x-slot>

    <section
        class="hero-storefront {{ $storefrontSetting?->hero_image ? 'hero-storefront--with-media' : 'hero-storefront--without-media' }}"
        aria-labelledby="hero-title"
        @if ($storefrontSetting?->hero_image)
            style="--hero-background-image: url('{{ $storefrontSetting->getHeroImage() }}');"
        @endif
    >
        <div class="store-container hero-storefront__inner">
            <div class="hero-storefront__content">
                <x-auth-session-status :status="session('status')" class="mb-5" />

                @if ($storefrontSetting?->hero_eyebrow)
                    <p class="hero-storefront__eyebrow">{{ $storefrontSetting->hero_eyebrow }}</p>
                @endif

                <h1 id="hero-title">{{ $storefrontSetting?->hero_title ?? config('app.name') }}</h1>

                @if ($storefrontSetting?->hero_subtitle)
                    <p>{{ $storefrontSetting->hero_subtitle }}</p>
                @endif
                <div class="hero-storefront__actions">
                    <a href="{{ route('jersey') }}" class="hero-storefront__cta">{{ __('Shop Jerseys') }}</a>
                </div>
            </div>
        </div>
    </section>

    <section class="league-section" aria-labelledby="league-heading">
        <div class="store-container">
            <div class="brand-section__header">
                <div>
                    <p class="section-kicker">{{ __('Explore competitions') }}</p>
                    <h2 id="league-heading">{{ __('Leagues') }}</h2>
                </div>

                @if ($topLeagues->count() > 1)
                    <div class="brand-carousel__controls" aria-label="{{ __('League carousel controls') }}">
                        <button type="button" class="brand-carousel__arrow" data-carousel-previous aria-label="{{ __('Previous leagues') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                        </button>
                        <button type="button" class="brand-carousel__arrow" data-carousel-next aria-label="{{ __('Next leagues') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
                        </button>
                    </div>
                @endif
            </div>

            <div class="brand-carousel league-carousel" data-store-carousel>
                <div class="brand-carousel__viewport">
                    <div class="brand-carousel__track" data-carousel-track>
                        @forelse ($topLeagues as $league)
                            <a
                                href="{{ route('jersey', ['name' => $league->slug]) }}"
                                class="league-logo-card"
                                data-carousel-item
                                aria-label="{{ __('View :league products', ['league' => $league->name]) }}"
                                title="{{ $league->name }}"
                            >
                                <x-store-league-image :league-name="$league->name" :country="$league->country" :fallback="$league->getImage()" :alt="$league->name" />
                                <span class="sr-only">{{ $league->name }}</span>
                            </a>
                        @empty
                            <div class="empty-state">
                                <h3>{{ __('No leagues available') }}</h3>
                                <p>{{ __('Create leagues from the admin panel.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="featured" class="featured-products" aria-labelledby="featured-heading">
        <div class="store-container">
            <div class="section-heading-row">
                <div>
                    <p class="section-kicker">{{ __('New season') }}</p>
                    <h2 id="featured-heading">{{ __('Best sellers') }}</h2>
                </div>
                <a href="{{ route('jersey') }}" class="text-link">{{ __('See all products') }}</a>
            </div>

            <div class="product-grid product-grid--featured">
                @forelse ($topJerseys as $topJersey)
                    <x-store-product-card :jersey="$topJersey" />
                @empty
                    <div class="empty-state">
                        <h3>{{ __('No active products') }}</h3>
                        <p>{{ __('Create products from the admin panel to populate this section.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="brand-section" aria-labelledby="brand-heading">
        <div class="store-container">
            <div class="brand-section__header">
                <div>
                    <p class="section-kicker">{{ __('Explore the clubs') }}</p>
                    <h2 id="brand-heading">{{ __('Shop by Brand') }}</h2>
                </div>

                @if ($topBrands->count() > 1)
                    <div class="brand-carousel__controls" aria-label="{{ __('Brand carousel controls') }}">
                        <button type="button" class="brand-carousel__arrow" data-carousel-previous aria-label="{{ __('Previous brands') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                        </button>
                        <button type="button" class="brand-carousel__arrow" data-carousel-next aria-label="{{ __('Next brands') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
                        </button>
                    </div>
                @endif
            </div>

            <div class="brand-carousel" data-store-carousel>
                <div class="brand-carousel__viewport">
                    <div class="brand-carousel__track" data-carousel-track>
                        @forelse ($topBrands as $brand)
                            <a href="{{ route('jersey', ['brand' => $brand->slug]) }}" class="brand-card" data-carousel-item>
                                <span class="brand-card__image">
                                    <x-store-brand-image :brand="$brand" />
                                </span>
                                <span class="brand-card__content">
                                    <strong>{{ $brand->name }}</strong>
                                    <small>{{ trans_choice(':count product|:count products', $brand->jerseys_count, ['count' => $brand->jerseys_count]) }}</small>
                                </span>
                            </a>
                        @empty
                            <div class="empty-state">
                                <h3>{{ __('No active brands') }}</h3>
                                <p>{{ __('Create and activate brands from the admin panel.') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
