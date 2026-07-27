<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Models\City;
use App\Models\Order;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Payments\OrderTotalService;
use App\Services\Payments\PaymentInitializationService;
use App\Services\Payments\PaymentNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly PaymentInitializationService $initialization,
        private readonly OrderTotalService $totals,
        private readonly InventoryReservationService $inventory,
        private readonly PaymentNotificationService $notifications,
    ) {}

    /**
     * @param  array{customer_name:string,customer_email:string,phone:string,address:string}  $customer
     * @param  array<string, mixed>  $shipping
     */
    public function place(Order $cart, User $user, City $city, array $customer, array $shipping): Order
    {
        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'payment' => $user->accountRestrictionMessage(),
            ]);
        }

        if ((int) $cart->user_id !== (int) $user->getKey()) {
            throw ValidationException::withMessages([
                'payment' => trans('This cart does not belong to the authenticated customer.'),
            ]);
        }

        $city->loadMissing('province');

        $order = DB::transaction(function () use ($cart, $user, $city, $customer, $shipping): Order {
            $pendingOrder = $this->initialization->begin($cart);
            $pendingOrder = $this->totals->applyCheckoutTotals($pendingOrder, $shipping);

            $pendingOrder->forceFill([
                'customer_name' => $customer['customer_name'],
                'customer_email' => $customer['customer_email'],
                'shipping_phone' => $customer['phone'],
                'shipping_address' => $customer['address'],
                'shipping_province' => $city->province->name,
                'shipping_city' => $city->name,
                'shipping_postal_code' => $city->postal_code,
                'payment_type' => 'cash_on_delivery',
                'payment_method' => 'cash_on_delivery',
                'transaction_status' => 'order_placed',
                'fulfillment_status' => Order::FULFILLMENT_PENDING,
                'fulfillment_status_updated_at' => now(),
                'reservation_expires_at' => null,
                'processed_at' => now(),
                'placed_at' => now(),
            ])->save();

            if (! $pendingOrder->statusHistories()->exists()) {
                $pendingOrder->statusHistories()->create([
                    'from_status' => null,
                    'to_status' => Order::FULFILLMENT_PENDING,
                    'changed_by' => null,
                    'note' => 'Order placed by customer.',
                ]);
            }

            if (! $this->inventory->consume($pendingOrder)) {
                throw ValidationException::withMessages([
                    'payment' => trans('The order could not be completed because stock changed. Please review your cart.'),
                ]);
            }

            $user->forceFill([
                'city_id' => $city->getKey(),
                'phone' => $customer['phone'],
                'address' => $customer['address'],
            ])->save();

            return $pendingOrder->fresh(['items', 'user']);
        }, 3);

        $this->notifications->send($order);

        return $order;
    }
}
