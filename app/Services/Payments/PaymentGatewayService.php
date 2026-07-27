<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Midtrans\Snap;
use Throwable;

class PaymentGatewayService
{
    public function __construct(
        private readonly MidtransConfiguration $configuration,
        private readonly Snap $snap,
    ) {}

    public function createRedirectUrl(Order $order): string
    {
        $reference = (string) Str::uuid();

        try {
            $this->configuration->apply();
            $order->loadMissing(['user.city.province', 'items']);

            $itemDetails = $order->items->map(fn ($item): array => [
                'id' => Str::limit($item->product_sku.'-'.$item->getKey(), 50, ''),
                'price' => $item->line_total,
                'quantity' => 1,
                'name' => Str::limit($item->product_name.' '.$item->size, 50, ''),
            ])->values()->all();

            if ($order->shipping_amount > 0) {
                $itemDetails[] = [
                    'id' => 'SHIPPING',
                    'price' => $order->shipping_amount,
                    'quantity' => 1,
                    'name' => 'Shipping',
                ];
            }

            $transaction = $this->snap->createTransaction([
                'transaction_details' => [
                    'order_id' => $order->invoice_number,
                    'gross_amount' => (int) $order->total_price,
                ],
                'item_details' => $itemDetails,
                'customer_details' => [
                    'first_name' => $order->user->name,
                    'email' => $order->user->email,
                    'phone' => $order->user->phone,
                    'billing_address' => [
                        'first_name' => $order->user->name,
                        'email' => $order->user->email,
                        'phone' => $order->user->phone,
                        'address' => $order->user->address,
                        'city' => $order->user->city?->name,
                        'postal_code' => $order->user->city?->postal_code,
                        'country_code' => 'PAK',
                    ],
                ],
                'expiry' => [
                    'start_time' => now('Asia/Karachi')->format('Y-m-d H:i:s O'),
                    'unit' => 'minutes',
                    'duration' => (int) config('checkout.reservation_minutes', 30),
                ],
                'enabled_payments' => config('midtrans.enabled_payments', []),
            ]);

            if (! isset($transaction->redirect_url) || ! is_string($transaction->redirect_url)) {
                throw new PaymentGatewayException($reference);
            }

            return $transaction->redirect_url;
        } catch (PaymentGatewayException $exception) {
            throw $exception;
        } catch (Throwable) {
            Log::error('Payment gateway request failed.', [
                'support_reference' => $reference,
                'order_id' => $order->getKey(),
                'invoice_number' => $order->invoice_number,
                'error_code' => 'PAYMENT_GATEWAY_REQUEST_FAILED',
            ]);

            throw new PaymentGatewayException($reference);
        }
    }
}
