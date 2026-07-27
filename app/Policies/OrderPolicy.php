<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && checkRole(User::ROLE_STAFF, $user->role);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->viewAny($user) && $order->status !== Order::STATUS_IN_CART;
    }

    public function update(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }
}
