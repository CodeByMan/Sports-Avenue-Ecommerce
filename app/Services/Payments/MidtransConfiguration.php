<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Exceptions\PaymentGatewayException;
use Illuminate\Support\Str;
use Midtrans\Config;

class MidtransConfiguration
{
    public function apply(): void
    {
        $serverKey = (string) config('midtrans.server_key');

        if ($serverKey === '') {
            throw new PaymentGatewayException((string) Str::uuid(), safeToRetry: true);
        }

        Config::$serverKey = $serverKey;
        Config::$isProduction = (bool) config('midtrans.is_production', false);
        Config::$isSanitized = (bool) config('midtrans.is_sanitized', true);
        Config::$is3ds = (bool) config('midtrans.is_3ds', true);
    }
}
