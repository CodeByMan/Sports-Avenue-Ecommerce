<x-app-layout>
    <x-slot name="title">
        {{ config('app.name') }} - {{ __('Order placed') }}
    </x-slot>

    <div class="py-12">
        <div class="container flex px-2 pt-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="w-full md:w-[55%] m-auto">
                <div class="box-content p-6 shadow-md shadow-stone-500 text-center">
                    <img src="{{ asset('assets/images/checkout/mailbox.jpg') }}" alt="{{ __('Order placed') }}"
                        class="object-cover mx-auto w-[75%] md:w-[75%] lg:w-[45%]">
                    <div class="leading-7 pt-4">
                        <h1 class="font-semibold text-lg md:text-xl lg:text-3xl">{{ __('Order placed successfully') }}</h1>
                        <p class="font-mono text-sm lg:text-lg">{{ __('Thank you for shopping with Sports Avenue.') }}</p>
                        <p class="font-mono text-sm lg:text-base">
                            {{ __('Invoice') }}: <strong>{{ $order->invoice_number }}</strong>
                        </p>
                        <p class="font-mono text-sm lg:text-base">
                            {{ __('Total') }}: <strong>{{ currencyFormat((int) $order->total_price) }}</strong>
                        </p>
                        <p class="font-mono text-sm lg:text-base">
                            {{ __('Payment') }}: <strong>{{ __('Cash on Delivery') }}</strong>
                        </p>
                    </div>
                    <x-primary-link href="{{ route('order-history') }}" class="mt-4 text-sm text-white bg-gray-700 rounded-md">
                        {{ __('View order history') }}
                    </x-primary-link>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
