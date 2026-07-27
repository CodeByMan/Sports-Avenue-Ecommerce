<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Services\Orders\OrderWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    public function test_staff_can_move_an_order_through_the_valid_workflow(): void
    {
        Notification::fake();

        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $staff = $this->createUser(['role' => User::ROLE_STAFF]);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
            'payment_method' => 'cash_on_delivery',
        ]);

        $workflow = app(OrderWorkflowService::class);

        foreach ([
            Order::FULFILLMENT_CONFIRMED,
            Order::FULFILLMENT_PROCESSING,
            Order::FULFILLMENT_PACKED,
            Order::FULFILLMENT_DISPATCHED,
            Order::FULFILLMENT_OUT_FOR_DELIVERY,
            Order::FULFILLMENT_DELIVERED,
        ] as $nextStatus) {
            $order = $workflow->transition($order, $nextStatus, $staff, "Moved to {$nextStatus}.");
        }

        $this->assertSame(Order::FULFILLMENT_DELIVERED, $order->fulfillment_status);
        $this->assertSame(Order::STATUS_SUCCESS, $order->status);
        $this->assertSame('cash_collected', $order->transaction_status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(6, $order->statusHistories()->count());

        Notification::assertSentTo($customer, OrderStatusUpdatedNotification::class);
    }

    public function test_invalid_workflow_transition_is_rejected_without_changing_the_order(): void
    {
        $admin = $this->createUser(['role' => User::ROLE_ADMIN]);
        $order = Order::factory()->create([
            'status' => Order::STATUS_PENDING,
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
        ]);

        try {
            app(OrderWorkflowService::class)->transition(
                $order,
                Order::FULFILLMENT_DELIVERED,
                $admin,
            );

            $this->fail('Invalid transition was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('fulfillment_status', $exception->errors());
        }

        $this->assertSame(Order::FULFILLMENT_PENDING, $order->fresh()->fulfillment_status);
        $this->assertDatabaseCount('order_status_histories', 0);
    }

    public function test_customer_cannot_update_order_workflow_status(): void
    {
        $customer = $this->createUser(['role' => User::ROLE_CUSTOMER]);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
        ]);

        $this->expectException(AuthorizationException::class);

        app(OrderWorkflowService::class)->transition(
            $order,
            Order::FULFILLMENT_CONFIRMED,
            $customer,
        );
    }

    public function test_delivered_order_can_be_refunded_but_not_cancelled(): void
    {
        $admin = $this->createUser(['role' => User::ROLE_ADMIN]);
        $order = Order::factory()->create([
            'status' => Order::STATUS_SUCCESS,
            'fulfillment_status' => Order::FULFILLMENT_DELIVERED,
        ]);

        $this->assertSame(
            [Order::FULFILLMENT_REFUNDED],
            app(OrderWorkflowService::class)->allowedTransitions($order),
        );

        $order = app(OrderWorkflowService::class)->transition(
            $order,
            Order::FULFILLMENT_REFUNDED,
            $admin,
            'Refund approved.',
        );

        $this->assertSame(Order::FULFILLMENT_REFUNDED, $order->fulfillment_status);
    }

    public function test_unpaid_cancelled_order_cannot_be_refunded(): void
    {
        $admin = $this->createUser(['role' => User::ROLE_ADMIN]);
        $order = Order::factory()->create([
            'status' => Order::STATUS_PENDING,
            'fulfillment_status' => Order::FULFILLMENT_PENDING,
        ]);

        $order = app(OrderWorkflowService::class)->transition(
            $order,
            Order::FULFILLMENT_CANCELLED,
            $admin,
            'Customer requested cancellation.',
        );

        $this->assertSame(Order::STATUS_FAILED, $order->status);
        $this->assertSame([], app(OrderWorkflowService::class)->allowedTransitions($order));
    }
}
