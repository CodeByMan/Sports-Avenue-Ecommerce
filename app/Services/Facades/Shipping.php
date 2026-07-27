<?php

declare(strict_types=1);

namespace App\Services\Facades;

use App\Services\Shipping as ShippingService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ShippingService province(?int $id = null)
 * @method static ShippingService city(?int $id = null)
 * @method static ShippingService fromProvince(int $id)
 * @method static ShippingService cost(?int $origin, ?int $destination, ?int $weight, ?string $courier)
 * @method static array<int, array{id:string, name:string}> couriers()
 * @method static array<int, array<string, mixed>> get()
 *
 * @see ShippingService
 */
class Shipping extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShippingService::class;
    }
}
