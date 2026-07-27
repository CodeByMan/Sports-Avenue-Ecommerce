<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    #[Test]
    public function the_order_notification_should_be_sent_after_the_status_order_has_been_changed(): void
    {
        Notification::fake();

        $roles = Arr::except(User::$roles, [2]);
        for ($i = 0; $i < 3; $i++) {
            User::factory(['role' => Arr::random($roles)])
                ->create();
        }
        $employees = getEmployees();

        $orderStatus = Arr::except(Order::$status, [0]);
        $order = Order::factory([
            'user_id' => User::factory(['role' => User::$roles[2]])->create()->id, 'status' => Arr::random($orderStatus),
        ])
            ->create();

        Notification::send($employees, new OrderNotification($order));

        Notification::assertSentTo($employees, OrderNotification::class);
    }
}
