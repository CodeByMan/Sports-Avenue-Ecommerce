<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class InvalidPaymentNotification extends RuntimeException
{
    public const DEFAULT_ERROR_CODE = 'PAYMENT_NOTIFICATION_REJECTED';

    public const PAYLOAD_FIELD_MISSING = 'PAYMENT_PAYLOAD_FIELD_MISSING';

    public const SIGNATURE_MISSING = 'PAYMENT_SIGNATURE_MISSING';

    public const ORDER_NOT_FOUND = 'PAYMENT_ORDER_NOT_FOUND';

    public const CONFIGURATION_UNAVAILABLE = 'PAYMENT_CONFIGURATION_UNAVAILABLE';

    public const PROVIDER_TRANSACTION_MISMATCH = 'PAYMENT_PROVIDER_TRANSACTION_MISMATCH';

    public const PAYMENT_TYPE_MISMATCH = 'PAYMENT_TYPE_MISMATCH';

    public const PAYMENT_METHOD_INELIGIBLE = 'PAYMENT_METHOD_INELIGIBLE';

    public const SIGNATURE_INVALID = 'PAYMENT_SIGNATURE_INVALID';

    public const ORDER_MISMATCH = 'PAYMENT_ORDER_MISMATCH';

    public const AMOUNT_MISMATCH = 'PAYMENT_AMOUNT_MISMATCH';

    public const TRANSACTION_MISMATCH = 'PAYMENT_TRANSACTION_MISMATCH';

    public const STATUS_INCONSISTENT = 'PAYMENT_STATUS_INCONSISTENT';

    public const CAPTURE_TYPE_INVALID = 'PAYMENT_CAPTURE_TYPE_INVALID';

    public const FRAUD_STATUS_INCONSISTENT = 'PAYMENT_FRAUD_STATUS_INCONSISTENT';

    public const AMOUNT_FORMAT_INVALID = 'PAYMENT_AMOUNT_FORMAT_INVALID';

    public const STATE_TRANSITION_INVALID = 'PAYMENT_STATE_TRANSITION_INVALID';

    public const PROVIDER_STATUS_UNAVAILABLE = 'PAYMENT_PROVIDER_STATUS_UNAVAILABLE';

    public function __construct(
        string $message = 'Payment notification rejected.',
        private readonly string $errorCode = self::DEFAULT_ERROR_CODE,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }
}
