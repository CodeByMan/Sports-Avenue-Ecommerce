<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Data\PaymentWebhookData;
use App\Exceptions\InvalidPaymentNotification;
use App\Models\Order;
use App\Services\Inventory\InventoryReservationService;
use Illuminate\Support\Facades\DB;

class PaymentWebhookService
{
    public function __construct(
        private readonly PaymentVerificationService $verification,
        private readonly OrderStateService $states,
        private readonly PaymentNotificationService $notifications,
        private readonly InventoryReservationService $inventory,
    ) {}

    /** @return array{order_id:int, status:string, changed:bool, duplicate:bool} */
    public function handle(PaymentWebhookData $callback): array
    {
        $result = DB::transaction(function () use ($callback): array {
            /** @var Order|null $order */
            $order = Order::query()
                ->where('invoice_number', $callback->orderId)
                ->lockForUpdate()
                ->first();

            if ($order === null) {
                throw new InvalidPaymentNotification(
                    'Payment order was not found.',
                    InvalidPaymentNotification::ORDER_NOT_FOUND,
                );
            }

            if ($order->payment_method === 'cash_on_delivery') {
                throw new InvalidPaymentNotification(
                    'Cash-on-delivery orders are not eligible for payment-provider callbacks.',
                    InvalidPaymentNotification::PAYMENT_METHOD_INELIGIBLE,
                );
            }

            $data = $this->verification->verify($callback, $order);
            $targetStatus = $this->states->targetStatus($data);

            if ($targetStatus === null) {
                return $this->result($order, false, false);
            }

            $exactDuplicate = $order->provider_transaction_id === $data->transactionId
                && $order->transaction_status === $data->transactionStatus
                && $order->status_code === $data->statusCode
                && $order->fraud_status === $data->fraudStatus
                && $order->payment_type === $data->paymentType;

            if ($exactDuplicate) {
                return $this->result($order, false, true);
            }

            $isPaymentReversal = $order->status === Order::STATUS_SUCCESS
                && $targetStatus === Order::STATUS_REVIEW
                && in_array($data->transactionStatus, [
                    'refund',
                    'partial_refund',
                    'chargeback',
                    'partial_chargeback',
                ], true);

            if (! $isPaymentReversal
                && in_array($order->status, [Order::STATUS_SUCCESS, Order::STATUS_FAILED, Order::STATUS_REVIEW], true)) {
                $this->states->assertTransitionAllowed($order->status, $targetStatus);

                return $this->result($order, false, true);
            }

            $this->states->assertTransitionAllowed($order->status, $targetStatus);

            $providerConfirmedPayment = $targetStatus === Order::STATUS_SUCCESS;

            if ($providerConfirmedPayment && ! $this->inventory->consume($order)) {
                $targetStatus = Order::STATUS_REVIEW;
            } elseif ($targetStatus === Order::STATUS_FAILED) {
                $this->inventory->release($order);
            }

            $previousFulfillmentStatus = $order->fulfillment_status;
            $fulfillmentStatus = match (true) {
                $isPaymentReversal => Order::FULFILLMENT_REFUNDED,
                $targetStatus === Order::STATUS_SUCCESS
                    && $previousFulfillmentStatus === Order::FULFILLMENT_PENDING => Order::FULFILLMENT_CONFIRMED,
                $targetStatus === Order::STATUS_FAILED
                    && ! in_array($previousFulfillmentStatus, [
                        Order::FULFILLMENT_DELIVERED,
                        Order::FULFILLMENT_REFUNDED,
                    ], true) => Order::FULFILLMENT_CANCELLED,
                default => $previousFulfillmentStatus,
            };

            $order->forceFill([
                'status' => $targetStatus,
                'fulfillment_status' => $fulfillmentStatus,
                'fulfillment_status_updated_at' => $fulfillmentStatus !== $previousFulfillmentStatus
                    ? now()
                    : $order->fulfillment_status_updated_at,
                'provider_transaction_id' => $data->transactionId,
                'transaction_status' => $data->transactionStatus,
                'payment_type' => $data->paymentType,
                'status_code' => $data->statusCode,
                'fraud_status' => $data->fraudStatus,
                'reservation_expires_at' => in_array($targetStatus, [Order::STATUS_SUCCESS, Order::STATUS_FAILED], true)
                    ? null
                    : $order->reservation_expires_at,
                'processed_at' => now(),
                'paid_at' => $providerConfirmedPayment ? ($order->paid_at ?? now()) : $order->paid_at,
            ])->save();

            if ($fulfillmentStatus !== $previousFulfillmentStatus) {
                $order->statusHistories()->create([
                    'from_status' => $previousFulfillmentStatus,
                    'to_status' => $fulfillmentStatus,
                    'changed_by' => null,
                    'note' => 'Workflow updated from verified payment-provider status.',
                ]);
            }

            return $this->result($order, true, false);
        }, 3);

        if ($result['changed']) {
            $order = Order::query()->with(['user', 'items'])->findOrFail($result['order_id']);
            $this->notifications->send($order);
        }

        return $result;
    }

    /** @return array{order_id:int, status:string, changed:bool, duplicate:bool} */
    private function result(Order $order, bool $changed, bool $duplicate): array
    {
        return [
            'order_id' => (int) $order->getKey(),
            'status' => $order->status,
            'changed' => $changed,
            'duplicate' => $duplicate,
        ];
    }
}
