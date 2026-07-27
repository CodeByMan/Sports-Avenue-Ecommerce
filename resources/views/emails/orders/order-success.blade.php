<x-mail::message>

# Payment confirmed for invoice {{ $order->invoice_number }}

Your payment has been verified by the payment provider. The purchased product details below are immutable snapshots retained with the order.

<x-mail::table>
| Product | SKU | Size | Quantity | Unit price | Nameset price | Line total |
| - | :-: | :-: | :-: | :-: | :-: | :-: |
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->product_sku }} | {{ $item->size }} | {{ $item->quantity }} | {{ currencyFormat($item->unit_price) }} | {{ currencyFormat($item->nameset_unit_price) }} | {{ currencyFormat($item->line_total) }} |
@endforeach
</x-mail::table>

**Products subtotal:** {{ currencyFormat($order->subtotal) }}

## Courier service

@php($courier = $order->courier_services ?? [])

{{ strtoupper((string) ($courier['code'] ?? '')) }} {{ $courier['service'] ?? '' }} — {{ $courier['description'] ?? '' }}  
Estimated delivery: {{ $courier['etd'] ?? '-' }}  
Shipping: {{ currencyFormat((int) ($courier['value'] ?? 0)) }}

**Order total:** {{ currencyFormat($order->total_price) }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
