<nav class="store-nav" aria-label="Primary navigation">
    <div class="store-nav__inner">
        <a href="{{ route('home') }}" class="store-brand" aria-label="{{ __('Sports Avenue home') }}">
            Sports Avenue
        </a>

        <div class="store-nav__links" aria-label="Store links">
            <a href="{{ route('home') }}" class="store-nav__link">{{ __('Home') }}</a>
            <a href="{{ route('league') }}" class="store-nav__link">{{ __('Leagues') }}</a>
            <a href="{{ route('jersey') }}" class="store-nav__link">{{ __('Jerseys') }}</a>
        </div>

        <div class="store-nav__actions">
            <a href="{{ route('cart') }}" class="store-icon-button" aria-label="{{ __('Shopping cart') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8V6a5 5 0 0 1 10 0v2"></path><path d="M5 8h14l1 13H4L5 8Z"></path></svg>
            </a>

            <a href="{{ route('login') }}" class="store-auth-button">
                {{ __('Login / Register') }}
            </a>

            <details class="store-mobile-menu">
                <summary class="store-icon-button" aria-label="{{ __('Open navigation menu') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
                </summary>
                <div class="store-mobile-menu__panel">
                    <a href="{{ route('home') }}">{{ __('Home') }}</a>
                    <a href="{{ route('league') }}">{{ __('Leagues') }}</a>
                    <a href="{{ route('jersey') }}">{{ __('Jerseys') }}</a>
                    <a href="{{ route('cart') }}">{{ __('Cart') }}</a>
                    <a href="{{ route('login') }}" class="store-mobile-menu__auth">{{ __('Login') }}</a>
                    <a href="{{ route('register') }}" class="store-mobile-menu__auth">{{ __('Register') }}</a>
                </div>
            </details>
        </div>
    </div>
</nav>
