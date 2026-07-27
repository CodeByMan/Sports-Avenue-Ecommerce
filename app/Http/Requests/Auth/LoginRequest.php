<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function username(): string
    {
        return filter_var($this->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
    }

    /** @return array<string, Rule|array|string> */
    public function rules(): array
    {
        $rules = $this->username() === 'email'
            ? ['required', 'string', 'email']
            : ['required', 'string'];

        return [
            'username' => $rules,
            'password' => ['required', 'string'],
        ];
    }

    /** @return array<string, mixed> */
    public function credentials(): array
    {
        return [
            $this->username() => $this->username,
            'password' => $this->password,
            'status' => User::STATUS_ACTIVE,
        ];
    }

    /** @throws ValidationException */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->credentials(), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            $user = User::query()
                ->where($this->username(), $this->string('username')->trim()->value())
                ->first();

            if ($user !== null
                && ! false
                && Hash::check((string) $this->password, $user->password)
                && ! $user->isActive()) {
                throw ValidationException::withMessages([
                    'username' => $user->accountRestrictionMessage(),
                ]);
            }

            throw ValidationException::withMessages([
                'username' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /** @throws ValidationException */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('username')).'|'.$this->ip());
    }
}
