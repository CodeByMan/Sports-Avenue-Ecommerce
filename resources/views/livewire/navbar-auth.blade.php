@php
    $user = Auth::user();
    $canAccessDashboard = in_array($user->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_STAFF], true);
    $initials = collect(preg_split('/\s+/', trim((string) $user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp
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
            <a href="{{ route('cart') }}" class="store-icon-button store-cart-button" aria-label="{{ __('Shopping cart') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8V6a5 5 0 0 1 10 0v2"></path><path d="M5 8h14l1 13H4L5 8Z"></path></svg>
                @if ($cartCount > 0)
                    <span class="store-cart-count">{{ $cartCount }}</span>
                @endif
            </a>

            <details class="store-account-menu">
                <summary class="store-account-trigger" aria-label="{{ __('Open account menu') }}">
                    <span class="store-avatar" aria-hidden="true">{{ $initials ?: 'SA' }}</span>
                    <span class="store-account-trigger__name">{{ $user->username }}</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"></path></svg>
                </summary>
                <div class="store-account-menu__panel">
                    <div class="store-account-menu__identity">
                        <strong>{{ $user->name }}</strong>
                        <span>{{ $user->email }}</span>
                    </div>
                    @if ($canAccessDashboard)
                        <a href="{{ \App\Filament\Pages\Dashboard::getUrl(panel: 'admin') }}">{{ __('Dashboard') }}</a>
                    @endif
                    <a href="{{ route('profile.edit') }}">{{ __('Profile') }}</a>
                    <a href="{{ route('order-history') }}">{{ __('Orders') }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="store-signout-button">{{ __('Sign Out') }}</button>
                    </form>
                </div>
            </details>

            <details class="store-mobile-menu">
                <summary class="store-icon-button" aria-label="{{ __('Open navigation menu') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"></path></svg>
                </summary>
                <div class="store-mobile-menu__panel">
                    <div class="store-mobile-user">
                        <span class="store-avatar" aria-hidden="true">{{ $initials ?: 'SA' }}</span>
                        <span><strong>{{ $user->username }}</strong><small>{{ $user->email }}</small></span>
                    </div>
                    <a href="{{ route('home') }}">{{ __('Home') }}</a>
                    <a href="{{ route('league') }}">{{ __('Leagues') }}</a>
                    <a href="{{ route('jersey') }}">{{ __('Jerseys') }}</a>
                    <a href="{{ route('cart') }}">{{ __('Cart') }} ({{ $cartCount }})</a>
                    @if ($canAccessDashboard)
                        <a href="{{ \App\Filament\Pages\Dashboard::getUrl(panel: 'admin') }}">{{ __('Dashboard') }}</a>
                    @endif
                    <a href="{{ route('order-history') }}">{{ __('Orders') }}</a>
                    <a href="{{ route('profile.edit') }}">{{ __('Profile') }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="store-signout-button">{{ __('Sign Out') }}</button>
                    </form>
                </div>
            </details>
        </div>
    </div>
</nav>
