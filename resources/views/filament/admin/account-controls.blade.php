@php
    $user = auth()->user();
    $unreadNotifications = $user?->unreadNotifications()->count() ?? 0;
    $initials = collect(preg_split('/\s+/', trim((string) $user?->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="sa-admin-account-controls">
    <a
        href="{{ \App\Filament\Pages\Notifications::getUrl() }}"
        class="sa-admin-notification-link"
        aria-label="{{ trans_choice(':count unread notification|:count unread notifications', $unreadNotifications, ['count' => $unreadNotifications]) }}"
        title="{{ __('Notifications') }}"
    >
        <x-filament::icon icon="heroicon-m-bell" class="h-5 w-5" />
        @if ($unreadNotifications > 0)
            <span>{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
        @endif
    </a>

    <div class="sa-theme-toggle" x-data>
        <button
            type="button"
            class="sa-theme-toggle__button"
            x-on:click="$dispatch('theme-changed', $store.theme === 'dark' ? 'light' : 'dark')"
            x-bind:aria-label="$store.theme === 'dark' ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))"
            x-bind:title="$store.theme === 'dark' ? @js(__('Switch to light mode')) : @js(__('Switch to dark mode'))"
        >
            <x-filament::icon icon="heroicon-m-moon" class="h-5 w-5" x-show="$store.theme !== 'dark'" x-cloak />
            <x-filament::icon icon="heroicon-m-sun" class="h-5 w-5" x-show="$store.theme === 'dark'" x-cloak />
        </button>
    </div>

    <details
        class="sa-admin-account-menu"
        x-on:click.outside="$el.removeAttribute('open')"
        x-on:keydown.escape.window="$el.removeAttribute('open'); $refs.accountTrigger?.focus()"
    >
        <summary x-ref="accountTrigger" class="sa-admin-account-menu__trigger" aria-label="{{ __('Open account menu') }}">
            @if ($user?->getFilamentAvatarUrl())
                <img src="{{ $user->getFilamentAvatarUrl() }}" alt="" class="sa-admin-account-menu__avatar">
            @else
                <span class="sa-admin-account-menu__initials" aria-hidden="true">{{ $initials ?: 'U' }}</span>
            @endif

            <span class="sa-admin-account-menu__identity">
                <strong>{{ $user?->name }}</strong>
                <small>{{ $user?->email }}</small>
            </span>

            <x-filament::icon icon="heroicon-m-chevron-down" class="h-4 w-4 sa-admin-account-menu__chevron" />
        </summary>

        <nav class="sa-admin-account-menu__panel" aria-label="{{ __('Account navigation') }}">
            <a href="{{ \App\Filament\Resources\ProfileResource::getUrl() }}">
                <x-filament::icon icon="heroicon-o-user-circle" class="h-5 w-5" />
                <span>{{ __('Profile') }}</span>
            </a>

            <a href="{{ \App\Filament\Resources\OrderResource::getUrl() }}">
                <x-filament::icon icon="heroicon-o-shopping-cart" class="h-5 w-5" />
                <span>{{ __('Orders') }}</span>
            </a>
            <a href="{{ route('home') }}" target="_blank" rel="noopener">
                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-5 w-5" />
                <span>{{ __('View Website') }}</span>
            </a>

            <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                @csrf
                <button type="submit">
                    <x-filament::icon icon="heroicon-o-arrow-left-on-rectangle" class="h-5 w-5" />
                    <span>{{ __('Sign Out') }}</span>
                </button>
            </form>
        </nav>
    </details>
</div>
