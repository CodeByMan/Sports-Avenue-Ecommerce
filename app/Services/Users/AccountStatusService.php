<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AccountStatusService
{
    public function changeCustomerStatus(
        User $customer,
        string $status,
        User $actor,
        ?string $reason = null,
    ): User {
        if (! $actor->isActive() || $actor->role !== User::ROLE_ADMIN) {
            throw new AuthorizationException('Only an active administrator may change customer status.');
        }

        if ($customer->role !== User::ROLE_CUSTOMER) {
            throw ValidationException::withMessages([
                'status' => trans('Only an active customer record can have its account status changed.'),
            ]);
        }

        if (! in_array($status, [
            User::STATUS_ACTIVE,
            User::STATUS_SUSPENDED,
            User::STATUS_BLOCKED,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => trans('The selected customer status is invalid.'),
            ]);
        }

        if (in_array($status, [User::STATUS_SUSPENDED, User::STATUS_BLOCKED], true) && blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => trans('A reason is required when restricting a customer account.'),
            ]);
        }

        return DB::transaction(function () use ($customer, $status, $actor, $reason): User {
            /** @var User $lockedCustomer */
            $lockedCustomer = User::query()
                ->whereKey($customer->getKey())
                ->where('role', User::ROLE_CUSTOMER)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedCustomer->forceFill([
                'status' => $status,
                'suspended_at' => $status === User::STATUS_SUSPENDED ? now() : null,
                'blocked_at' => $status === User::STATUS_BLOCKED ? now() : null,
                'status_changed_at' => now(),
                'status_reason' => $status === User::STATUS_ACTIVE ? null : trim((string) $reason),
                'updated_by' => $actor->getKey(),
                'remember_token' => Str::random(60),
            ])->save();

            return $lockedCustomer->fresh();
        }, 3);
    }
}
