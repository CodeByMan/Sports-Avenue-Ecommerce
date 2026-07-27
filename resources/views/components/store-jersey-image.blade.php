@props([
    'imageName' => null,
    'productName' => null,
    'fallback' => null,
    'alt' => '',
    'variant' => null,
])

<img src="{{ $fallback ?: asset('assets/images/no-image.png') }}" alt="{{ $alt }}" {{ $attributes }}>
