<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ShippingServiceException extends RuntimeException
{
    public function __construct(public readonly string $supportReference)
    {
        parent::__construct("Shipping rates are temporarily unavailable. Support reference: {$supportReference}");
    }
}
