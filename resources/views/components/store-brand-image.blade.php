@props([
    'brand',
    'alt' => '',
])

<img src="{{ $brand->getImage() }}" alt="{{ $alt ?: $brand->name }}" {{ $attributes }}>
