<x-filament-panels::page>
    @php
        $customerName = $order->customer_name ?: $order->user?->name;
        $customerEmail = $order->customer_email ?: $order->user?->email;
        $phone = $order->shipping_phone ?: $order->user?->phone;
        $address = $order->shipping_address ?: $order->user?->address;
        $city = $order->shipping_city ?: $order->user?->city?->name;
        $province = $order->shipping_province ?: $order->user?->city?->province?->name;
        $postalCode = $order->shipping_postal_code ?: $order->user?->city?->postal_code;
    @endphp

    <div class="grid gap-6 xl:grid-cols-3">
        <x-filament::section heading="{{ __('Order summary') }}">
            <dl class="grid gap-4 text-sm">
                <div><dt class="font-semibold">{{ __('Invoice Number') }}</dt><dd>{{ $order->invoice_number }}</dd></div>
                <div>
                    <dt class="font-semibold">{{ __('Order status') }}</dt>
                    <dd>
                        <x-filament::badge :color="\App\Models\Order::fulfillmentStatusColor($order->fulfillment_status)">
                            {{ $order->fulfillment_status_label }}
                        </x-filament::badge>
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold">{{ __('Payment status') }}</dt>
                    <dd><x-filament::badge :color="$order->payment_status_color">{{ $order->payment_status_label }}</x-filament::badge></dd>
                </div>
                <div><dt class="font-semibold">{{ __('Payment method') }}</dt><dd>{{ $order->payment_method_label }}</dd></div>
                <div><dt class="font-semibold">{{ __('Courier Services') }}</dt><dd>{{ courierServiceFormat($order->courier_services) }}</dd></div>
                <div><dt class="font-semibold">{{ __('Subtotal') }}</dt><dd>{{ currencyFormat((int) $order->subtotal) }}</dd></div>
                <div><dt class="font-semibold">{{ __('Shipping') }}</dt><dd>{{ currencyFormat((int) $order->shipping_amount) }}</dd></div>
                <div><dt class="font-semibold">{{ __('Total price') }}</dt><dd class="text-base font-bold">{{ currencyFormat((int) $order->total_price) }}</dd></div>
            </dl>
        </x-filament::section>

        <x-filament::section heading="{{ __('Customer information') }}">
            <dl class="grid gap-4 text-sm">
                <div><dt class="font-semibold">{{ __('Name') }}</dt><dd>{{ $customerName ?: '-' }}</dd></div>
                <div><dt class="font-semibold">{{ __('Email address') }}</dt><dd>{{ $customerEmail ?: '-' }}</dd></div>
                <div><dt class="font-semibold">{{ __('Phone') }}</dt><dd>{{ $phone ?: '-' }}</dd></div>
            </dl>
        </x-filament::section>

        <x-filament::section heading="{{ __('Shipping details') }}">
            <dl class="grid gap-4 text-sm">
                <div><dt class="font-semibold">{{ __('Address') }}</dt><dd>{{ $address ?: '-' }}</dd></div>
                <div><dt class="font-semibold">{{ __('City') }}</dt><dd>{{ $city ?: '-' }}</dd></div>
                <div><dt class="font-semibold">{{ __('Province') }}</dt><dd>{{ $province ?: '-' }}</dd></div>
                <div><dt class="font-semibold">{{ __('Postal code') }}</dt><dd>{{ $postalCode ?: '-' }}</dd></div>
            </dl>
        </x-filament::section>
    </div>

    <x-filament::section heading="{{ __('Order status timeline') }}">
        <ol class="sa-order-timeline">
            @forelse ($order->statusHistories as $history)
                <li class="sa-order-timeline__item">
                    <span class="sa-order-timeline__marker" aria-hidden="true"></span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <strong>{{ \App\Models\Order::fulfillmentStatusLabel($history->to_status) }}</strong>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $history->created_at?->format('M j, Y g:i A') }}</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $history->actor?->name ? __('Updated by :name', ['name' => $history->actor->name]) : __('System update') }}
                        </p>
                        @if ($history->note)
                            <p class="mt-1 text-sm">{{ $history->note }}</p>
                        @endif
                    </div>
                </li>
            @empty
                <li>{{ __('No status changes have been recorded yet.') }}</li>
            @endforelse
        </ol>
    </x-filament::section>

    {{ $this->table }}
</x-filament-panels::page>
