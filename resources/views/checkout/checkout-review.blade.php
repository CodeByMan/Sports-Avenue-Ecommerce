<x-app-layout>
    <x-slot name="title">
        {{ config('app.name') }} - {{ __('Payment Review') }}
    </x-slot>

    <div class="py-12">
        <div class="container flex px-2 pt-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="w-full md:w-[55%] m-auto">
                <div class="box-content p-6 text-center shadow-md shadow-stone-500">
                    <h1 class="text-xl font-semibold lg:text-3xl">{{ __('Payment requires review') }}</h1>
                    <p class="pt-3 font-mono text-sm lg:text-base">
                        {{ __('Your order requires manual payment or inventory review. Support has been notified and the verified status will appear in order history.') }}
                    </p>
                    <p class="pt-2 text-sm">
                        {{ __('Invoice: :invoice', ['invoice' => $order->invoice_number]) }}
                    </p>
                    <x-primary-link href="{{ route('order-history') }}" class="mt-4 text-sm text-white bg-gray-700 rounded-md">
                        {{ __('View order history') }}
                    </x-primary-link>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
