<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialiteController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        $supportReference = (string) Str::uuid();

        try {
            $googleUser = Socialite::driver('google')->user();
            $email = strtolower(trim((string) $googleUser->getEmail()));
            $googleId = trim((string) $googleUser->getId());

            if ($email === '' || $googleId === '') {
                throw new \RuntimeException('Google did not return a usable identity.');
            }

            $user = DB::transaction(function () use ($googleUser, $email, $googleId): User {
                /** @var User|null $user */
                $user = User::query()
                    ->where('google_id', $googleId)
                    ->orWhere('email', $email)
                    ->lockForUpdate()
                    ->first();

                if ($user !== null && $user->google_id !== null && $user->google_id !== $googleId) {
                    throw new \RuntimeException('The email is already connected to another Google identity.');
                }

                if ($user === null) {
                    $user = new User;
                    $user->fill([
                        'google_id' => $googleId,
                        'name' => (string) ($googleUser->getName() ?: Str::before($email, '@')),
                        'username' => 'google'.substr(hash('sha256', $googleId), 0, 24),
                        'email' => $email,
                        'password' => Hash::make(Str::random(64)),
                    ]);
                    $user->forceFill([
                        'role' => User::ROLE_CUSTOMER,
                        'status' => User::STATUS_ACTIVE,
                        'email_verified_at' => now(),
                    ])->save();
                } else {
                    $user->forceFill([
                        'google_id' => $googleId,
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ])->save();
                }

                return $user;
            });

            if (! $user->isActive()) {
                return to_route('login')->withErrors([
                    'email' => $user->accountRestrictionMessage(),
                ]);
            }

            Auth::login($user);
            request()->session()->regenerate();

            return redirect()->to(checkSessionRoute());
        } catch (Throwable) {
            Log::warning('Google authentication failed.', [
                'support_reference' => $supportReference,
                'error_code' => 'GOOGLE_AUTHENTICATION_FAILED',
            ]);

            return to_route('login')->withErrors([
                'email' => "Google sign-in could not be completed. Support reference: {$supportReference}",
            ]);
        }
    }
}
