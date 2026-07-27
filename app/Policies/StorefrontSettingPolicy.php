<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StorefrontSetting;
use App\Models\User;

class StorefrontSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->role === User::ROLE_ADMIN;
    }

    public function view(User $user, StorefrontSetting $setting): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, StorefrontSetting $setting): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, StorefrontSetting $setting): bool
    {
        return $this->viewAny($user);
    }
}
