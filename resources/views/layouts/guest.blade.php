<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|oswald:600,700&display=swap" rel="stylesheet" />
    <style>[x-cloak] { display: none !important; }</style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('scripts')
</head>
<body class="store-body">
    @include('layouts.navbar-guest')

    <main class="store-main">
        @if (request()->routeIs('login', 'register'))
            <section class="auth-page">
                <div class="auth-shell">
                    <div class="auth-visual" style="background-image: url('{{ asset('images/hero_section.png') }}');">
                        <div class="auth-visual__overlay"></div>
                        <div class="auth-visual__content">
                            <p>{{ __('Sports Avenue') }}</p>
                            <h1>{{ request()->routeIs('register') ? __('Join the starting lineup.') : __('Welcome back to the game.') }}</h1>
                            <span>{{ __('Premium football apparel, secure checkout, and your match-day favourites in one place.') }}</span>
                        </div>
                    </div>

                    <div class="auth-panel">
                        {{ $slot }}
                    </div>
                </div>
            </section>
        @else
            <section class="auth-page auth-page--compact">
                <div class="auth-panel auth-panel--standalone">
                    {{ $slot }}
                </div>
            </section>
        @endif
    </main>

    @livewireScripts
</body>
</html>
