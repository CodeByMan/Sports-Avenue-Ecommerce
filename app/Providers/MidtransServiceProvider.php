<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Payments\PaymentStatusClient;
use App\Services\Payments\MidtransPaymentStatusClient;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Midtrans\Snap;

class MidtransServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->singleton(Snap::class, fn (): Snap => new Snap);
        $this->app->bind(PaymentStatusClient::class, MidtransPaymentStatusClient::class);
    }

    /** @return array<int, class-string> */
    public function provides(): array
    {
        return [Snap::class, PaymentStatusClient::class];
    }
}
