<?php

namespace Tests\Feature\Auth;

use App\Models\Jersey;
use App\Models\User;
use Database\Seeders\JerseySeeder;
use Database\Seeders\LeagueSeeder;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Validation\AuthenticationValidationTest;

class AuthenticationTest extends TestCase
{
    use AuthenticationValidationTest;

    public User $user;

    public string $fakePassword = 'abcabcabcabc';

    public string $realPassword;

    public array $data = [
        'username' => 'johnlennon',
        'email' => 'johnlennon@beatles.com',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutExceptionHandling();

        $this->realPassword = (string) config('demo.password');
        $this->data['role'] = User::$roles[2];
        $this->user = $this->createUser($this->data);
    }

    #[Test]
    public function login_page_can_be_rendered(): void
    {
        $res = $this->get(route('login'));

        $res->assertOk()
            ->assertSee(trans('Login'));
    }

    #[Test]
    public function user_can_login_with_email(): void
    {
        $credentials = ['username' => $this->data['email'], 'password' => $this->realPassword];

        $res = $this->post(route('login'), $credentials);

        $res->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    #[Test]
    public function user_can_login_with_username(): void
    {
        $credentials = ['username' => $this->data['username'], 'password' => $this->realPassword];

        $res = $this->post(route('login'), $credentials);

        $res->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }

    #[Test]
    public function inactive_user_cannot_login(): void
    {
        $this->withExceptionHandling();
        $this->user->forceFill(['status' => User::STATUS_INACTIVE])->save();

        $this->post(route('login'), [
            'username' => $this->data['email'],
            'password' => $this->realPassword,
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function an_existing_session_is_ended_when_the_account_becomes_inactive(): void
    {
        $this->user->forceFill(['status' => User::STATUS_INACTIVE])->save();
        $this->withExceptionHandling();

        $this->actingAs($this->user)
            ->get(route('cart'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function suspended_user_cannot_login(): void
    {
        $this->withExceptionHandling();
        $this->user->forceFill([
            'status' => User::STATUS_SUSPENDED,
            'status_reason' => 'Account review is in progress.',
        ])->save();

        $this->post(route('login'), [
            'username' => $this->data['email'],
            'password' => $this->realPassword,
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function blocked_user_cannot_login_or_keep_an_existing_session(): void
    {
        $this->withExceptionHandling();
        $this->user->forceFill([
            'status' => User::STATUS_BLOCKED,
            'status_reason' => 'Fraud prevention block.',
        ])->save();

        $this->post(route('login'), [
            'username' => $this->data['email'],
            'password' => $this->realPassword,
        ])->assertSessionHasErrors('username');

        $this->actingAs($this->user)
            ->get(route('cart'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    #[Test]
    public function authenticated_user_can_access_the_authenticated_routes(): void
    {
        $res = $this->actingAs($this->user)
            ->get(route('cart'));

        $res->assertOk()
            ->assertSee(trans('Shopping Cart'));
        $this->assertAuthenticated();
    }

    #[Test]
    public function if_there_is_cart_route_session_then_should_redirect_to_jersey_detail_page(): void
    {
        $this->seed(LeagueSeeder::class);
        $this->seed(JerseySeeder::class);
        $jersey = Jersey::bestSeller()
            ->inRandomOrder()
            ->first();
        $sessionData = route('jersey.detail', $jersey);
        $credentials = ['username' => $this->data['username'], 'password' => $this->realPassword];

        $res = $this->withSession(['cartRoute' => $sessionData])
            ->post(route('login'), $credentials);

        $res->assertRedirect($sessionData);
        $this->assertAuthenticated();
    }
}
