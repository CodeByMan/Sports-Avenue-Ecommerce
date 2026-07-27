<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Jersey;
use App\Models\User;

class JerseyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && checkRole(User::ROLE_STAFF, $user->role);
    }

    public function view(User $user, Jersey $jersey): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Jersey $record): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Jersey $jersey): bool
    {
        return $this->update($user, $jersey);
    }
}
