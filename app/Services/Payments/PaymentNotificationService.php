<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Mail\OrderSuccess;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderNotification;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentNotificationService
{
    public function send(Order $order): void
    {
        $order->loadMissing('user');

        if ($order->status === Order::STATUS_SUCCESS) {
            try {
                Mail::to($order->user)->queue(new OrderSuccess($order));
            } catch (Throwable) {
                $this->logFailure('customer mail', $order);
            }
        }

        User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
            ->where('status', User::STATUS_ACTIVE)
            ->eachById(function (User $employee) use ($order): void {
                try {
                    $employee->notify(new OrderNotification($order));
                    event(new DatabaseNotificationsSent($employee));
                } catch (Throwable) {
                    $this->logFailure('staff notification', $order, $employee->getKey());
                }
            });
    }

    private function logFailure(string $channel, Order $order, ?int $recipientId = null): void
    {
        Log::error('Payment follow-up notification failed.', [
            'channel' => $channel,
            'order_id' => $order->getKey(),
            'invoice_number' => $order->invoice_number,
            'recipient_id' => $recipientId,
            'error_code' => 'PAYMENT_NOTIFICATION_DELIVERY_FAILED',
        ]);
    }
}
