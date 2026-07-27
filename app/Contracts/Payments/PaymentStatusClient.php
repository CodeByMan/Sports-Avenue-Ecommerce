<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\Data\PaymentWebhookData;

interface PaymentStatusClient
{
    public function fetch(string $orderId): PaymentWebhookData;
}
