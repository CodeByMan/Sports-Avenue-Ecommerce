<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Validation\PasswordResetValidationTest;

class PasswordResetTest extends TestCase
{
    use PasswordResetValidationTest;

    public User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
        $this->user = $this->createUser(['email' => 'johnlennon@beatles.com', 'role' => User::$roles[2]]);
    }

    #[Test]
    public function forgot_password_page_can_be_rendered(): void
    {
        $res = $this->get(route('password.request'));

        $res->assertOk()
            ->assertSee(trans('Email Password Reset Link'));
    }

    #[Test]
    public function user_can_send_the_reset_password_link(): void
    {
        Notification::fake();

        $res = $this->post(route('password.email'), ['email' => $this->user->email]);

        $res->assertSessionHas('status');
        Notification::assertSentTo($this->user, ResetPasswordNotification::class);
    }

    #[Test]
    public function reset_password_page_can_be_rendered(): void
    {
        $res = $this->get(route('password.reset', 'token123'));

        $res->assertOk()
            ->assertSee(trans('Reset Password'));
    }

    #[Test]
    public function user_can_reset_password(): void
    {
        Event::fake();
        $newPassword = 'secret@12345';
        $token = Password::createToken($this->user);

        $res = $this->post(route('password.store'), $this->resetPasswordData($this->user->email, $newPassword, $newPassword, $token));

        $res->assertValid()
            ->assertSessionHas('status');

        Event::assertDispatched(PasswordReset::class);

        $this->assertTrue(Hash::check($newPassword, $this->user->fresh()->getAuthPassword()));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $this->user->email]);
    }

    private function resetPasswordData(string $email, string $password, string $passwordConfirmation, string $token): array
    {
        return [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
            'token' => $token,
        ];
    }

    private function getToken(): string
    {
        return Password::createToken($this->user);
    }
}
