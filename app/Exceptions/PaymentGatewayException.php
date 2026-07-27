<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class PaymentGatewayException extends RuntimeException
{
    public function __construct(
        public readonly string $supportReference,
        public readonly bool $safeToRetry = false,
    ) {
        parent::__construct("The payment service is temporarily unavailable. Support reference: {$supportReference}");
    }
}
