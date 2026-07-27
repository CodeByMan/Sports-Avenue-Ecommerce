@props(['jersey'])
@php
    $availableSizes = collect($jersey->stock ?? [])->filter(fn ($stock) => (int) $stock > 0)->keys()->implode(', ');
@endphp
<article class="product-card">
    <a href="{{ route('jersey.detail', $jersey) }}" class="product-card__image-wrap" aria-label="{{ __('View :product', ['product' => $jersey->name]) }}">
        <span class="product-card__heart" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"></path></svg>
        </span>
        <x-store-jersey-image :fallback="$jersey->getImage()" :alt="$jersey->name" class="product-card__image" />
    </a>
    <div class="product-card__body">
        <a href="{{ route('jersey.detail', $jersey) }}" class="product-card__title">{{ $jersey->name }}</a>
        <div class="product-card__meta">
            <span>{{ $jersey->brand?->name ?? __('Size') }} · {{ $availableSizes ?: __('Unavailable') }}</span>
            <strong>{{ currencyFormat($jersey->price) }}</strong>
        </div>
    </div>
</article>
