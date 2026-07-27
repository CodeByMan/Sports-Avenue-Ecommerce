<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/** @return array<string, int> */
function getRulesRole(): array
{
    $roles = User::$roles;
    $rolesCount = count($roles);
    $rules = [];

    foreach ($roles as $role) {
        $rules[$role] = $rolesCount--;
    }

    return $rules;
}

function checkRole(string $availableRole, string $userRole): bool
{
    $rules = getRulesRole();

    return isset($rules[$userRole], $rules[$availableRole])
        && $rules[$userRole] >= $rules[$availableRole];
}

function transformDateFormat(string $data, ?string $format = null): string|Carbon
{
    $result = Carbon::parse($data);

    return $format === null ? $result : $result->translatedFormat($format);
}

function currencyFormat(int $value): string
{
    return 'Rs. '.number_format($value);
}

function integerFormat(string $value): int
{
    return (int) preg_replace('/[^0-9]/', '', $value);
}

function generateInvoiceNumber(): string
{
    return 'SA-'.now()->format('Ymd').'-'.Str::upper(Str::random(12));
}

function getOrderCart(): ?Order
{
    return auth()->user()?->orders()
        ->where('status', Order::STATUS_IN_CART)
        ->with('items.jersey')
        ->first();
}

/** @param array<string, mixed>|null $couriers */
function courierServiceFormat(?array $couriers = null): string
{
    if ($couriers === null) {
        return '-';
    }

    $code = strtoupper((string) ($couriers['code'] ?? ''));
    $service = (string) ($couriers['service'] ?? '');
    $description = (string) ($couriers['description'] ?? '');
    $value = currencyFormat((int) ($couriers['value'] ?? 0));
    $etd = (string) ($couriers['etd'] ?? '');

    return trim("{$code} - {$service} {$description} {$value} ETA: {$etd}");
}

function namesetFormat(array|string|null $nameset): string
{
    $state = is_array($nameset) ? $nameset : json_decode((string) $nameset, true);

    if (! is_array($state) || $state === []) {
        return '-';
    }

    return collect($state)
        ->map(fn (array $values): string => trim(implode(' ', array_filter($values, fn ($value): bool => $value !== null && $value !== ''))))
        ->filter()
        ->implode(', ');
}

function setPermissions(string $availableRole, User $user, ?Closure $ability = null): bool
{
    return $ability === null
        ? checkRole($availableRole, $user->role)
        : $ability() && checkRole($availableRole, $user->role);
}

/** @return Collection<int, User> */
function getEmployees(): Collection
{
    return User::query()
        ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])
        ->where('status', User::STATUS_ACTIVE)
        ->get();
}

function checkSessionRoute(): string
{
    return session()->has('cartRoute')
        ? (string) session()->pull('cartRoute')
        : route('home');
}
