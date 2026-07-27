<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Validation\RegistrationValidationTest;

class RegistrationTest extends TestCase
{
    use RegistrationValidationTest;

    protected array $data = [
        'name' => 'John Lennon',
        'username' => 'johnlennon',
        'email' => 'johnlennon@beatles.com',
        'password' => 'abc@1234567',
        'password_confirmation' => 'abc@1234567',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();
    }

    #[Test]
    public function registration_page_can_be_rendered(): void
    {
        $res = $this->get(route('register'));

        $res->assertViewIs('auth.register')
            ->assertSee(trans('Register'));
    }

    #[Test]
    public function user_can_register(): void
    {
        Event::fake();
        Notification::fake();

        $payload = array_merge($this->data, [
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_INACTIVE,
            'created_by' => 999,
            'updated_by' => 999,
        ]);

        $res = $this->post(route('register'), $payload);

        $res->assertValid()
            ->assertSessionHas('status')
            ->assertRedirect(route('login'));
        $this->assertDatabaseHas(User::class, Arr::except($this->data, ['password', 'password_confirmation']))
            ->assertDatabaseCount(User::class, 1);

        $user = User::first();
        $this->assertSame(User::ROLE_CUSTOMER, $user->role);
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertNull($user->created_by);
        $this->assertNull($user->updated_by);
        $user->notify(new VerifyEmail);
        Event::assertDispatched(Registered::class);
        Event::assertListening(Registered::class, SendEmailVerificationNotification::class);
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
