<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderNotification extends Notification
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public Order $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        $title = trans('New order notification');
        $body = trans(
            'There is a new order with invoice number :invoiceNumber',
            ['invoiceNumber' => $this->order->invoice_number],
        );
        $url = OrderResource::getUrl();

        if ($this->order->status !== Order::STATUS_PENDING) {
            $title = trans('Order status changed');
            $body = trans(
                'The status of order :invoiceNumber is now :status',
                [
                    'invoiceNumber' => $this->order->invoice_number,
                    'status' => $this->order->status,
                ],
            );
            $url = OrderResource::getUrl('view', ['record' => $this->order->invoice_number]);
        }

        return FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->actions([
                Action::make('view')
                    ->label(trans('View'))
                    ->url($url),
            ])
            ->getDatabaseMessage();
    }
}
