<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Shipping;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(Shipping::class, fn (): Shipping => new Shipping);
    }

    /** @return array<int, class-string> */
    public function provides(): array
    {
        return [Shipping::class];
    }
}
