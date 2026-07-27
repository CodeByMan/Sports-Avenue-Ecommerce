<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->role === User::ROLE_ADMIN;
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user) && $user->getKey() !== $model->getKey();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $model): bool
    {
        return $this->viewAny($user)
            && $user->getKey() !== $model->getKey()
            && $model->role === User::ROLE_STAFF;
    }

    public function delete(User $user, User $model): bool
    {
        return $this->viewAny($user)
            && $user->getKey() !== $model->getKey();
    }
}
