<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && checkRole(User::ROLE_STAFF, $user->role);
    }

    public function view(User $user, Brand $brand): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Brand $brand): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $this->viewAny($user);
    }
}
