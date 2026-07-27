<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => trans('Order status updated'),
            'message' => trans(
                'Order :invoice is now :status.',
                [
                    'invoice' => $this->order->invoice_number,
                    'status' => $this->order->fulfillment_status_label,
                ],
            ),
            'invoice_number' => $this->order->invoice_number,
            'fulfillment_status' => $this->order->fulfillment_status,
            'url' => route('order-history'),
        ];
    }
}
