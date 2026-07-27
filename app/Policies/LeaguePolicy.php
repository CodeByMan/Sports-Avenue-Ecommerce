<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\League;
use App\Models\User;

class LeaguePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && checkRole(User::ROLE_STAFF, $user->role);
    }

    public function view(User $user, League $league): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, League $record): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, League $league): bool
    {
        return $this->update($user, $league);
    }
}
