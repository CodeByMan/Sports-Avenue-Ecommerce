<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmail;
use DomainException;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'ADMIN';

    public const ROLE_STAFF = 'STAFF';

    public const ROLE_CUSTOMER = 'CUSTOMER';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    public const STATUS_SUSPENDED = 'SUSPENDED';

    public const STATUS_BLOCKED = 'BLOCKED';

    /** @var array<int, string> */
    protected $fillable = [
        'city_id',
        'name',
        'username',
        'email',
        'phone',
        'address',
        'avatar',
        'password',
    ];

    /** @var array<int, string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'suspended_at' => 'datetime',
        'blocked_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    /** @var array<int, string> */
    public static array $roles = [self::ROLE_ADMIN, self::ROLE_STAFF, self::ROLE_CUSTOMER];

    /** @var array<int, string> */
    public static array $status = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_BLOCKED,
    ];

    public static string $directory = 'avatars';

    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            if (auth()->id() === $user->getKey()) {
                throw new DomainException('You cannot delete your own account.');
            }

            if ($user->role === self::ROLE_ADMIN) {
                $remainingAdministrators = User::query()
                    ->where('role', self::ROLE_ADMIN)
                    ->whereKeyNot($user->getKey())
                    ->count();

                if ($remainingAdministrators < 1) {
                    throw new DomainException('The last administrator cannot be deleted.');
                }
            }

            if ($user->orders()->exists()) {
                throw new DomainException('A user with order history cannot be permanently deleted.');
            }
        });
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function accountRestrictionMessage(): string
    {
        return match ($this->status) {
            self::STATUS_SUSPENDED => trans('This account is suspended. Contact support for assistance.'),
            self::STATUS_BLOCKED => trans('This account is blocked. Contact support for assistance.'),
            default => trans('This account is inactive. Contact support for assistance.'),
        };
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = ucwords(trim($value));
    }

    public function setUsernameAttribute(string $value): void
    {
        $this->attributes['username'] = strtolower(trim($value));
    }

    public function getAvatar(): string
    {
        if ($this->avatar === null) {
            return asset('assets/images/no-user.jpg');
        }

        $path = str_starts_with($this->avatar, self::$directory.'/')
            ? $this->avatar
            : self::$directory.'/'.$this->avatar;

        return asset('storage/'.$path);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar === null ? null : $this->getAvatar();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return checkRole(self::ROLE_STAFF, $this->role)
            && $this->isActive()
            && $this->hasVerifiedEmail();
    }
}
