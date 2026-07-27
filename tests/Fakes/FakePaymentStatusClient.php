<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Contracts\Payments\PaymentStatusClient;
use App\Data\PaymentWebhookData;
use LogicException;

class FakePaymentStatusClient implements PaymentStatusClient
{
    private ?PaymentWebhookData $response = null;

    public int $fetchCount = 0;

    public function respondWith(PaymentWebhookData $response): void
    {
        $this->response = $response;
    }

    public function fetch(string $orderId): PaymentWebhookData
    {
        $this->fetchCount++;

        if ($this->response === null) {
            throw new LogicException('No fake Midtrans status response was configured.');
        }

        if ($this->response->orderId !== $orderId) {
            throw new LogicException('The fake Midtrans status response belongs to another order.');
        }

        return $this->response;
    }
}
