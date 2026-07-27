@props([
    'leagueName' => null,
    'country' => null,
    'fallback' => null,
    'alt' => '',
])

<img src="{{ $fallback ?: asset('assets/images/no-image.png') }}" alt="{{ $alt }}" {{ $attributes }}>
