@php
    $hasStoreNavigation = auth()->check() || request()->routeIs('home', 'jersey', 'jersey.detail', 'league');
@endphp
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
    <div class="min-h-screen bg-white">
        @auth
            <livewire:navbar-auth />
        @else
            @if ($hasStoreNavigation)
                @include('layouts.navbar-guest')
            @endif
        @endauth

        @if (isset($header))
            @if (request()->routeIs('jersey', 'jersey.detail', 'cart', 'league'))
                <header class="sr-only">{{ $header }}</header>
            @else
                <header class="store-legacy-header">
                    <div class="store-container">{{ $header }}</div>
                </header>
            @endif
        @endif

        <main @class(['store-main' => $hasStoreNavigation])>
            {{ $slot }}
        </main>

        @if ($hasStoreNavigation)
            @include('layouts.footer')
        @endif
    </div>

    @livewireScripts
</body>
</html>
