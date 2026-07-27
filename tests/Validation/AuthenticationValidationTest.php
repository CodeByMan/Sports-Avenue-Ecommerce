<?php

namespace Tests\Validation;

use PHPUnit\Framework\Attributes\Test;

trait AuthenticationValidationTest
{
    #[Test]
    public function the_credentials_does_not_match(): void
    {
        $this->withExceptionHandling();
        $credentials = ['username' => $this->data['username'], 'password' => $this->fakePassword];

        $res = $this->post(route('login'), $credentials);

        $res->assertInvalid(['username']);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_the_authenticated_routes(): void
    {
        $this->withExceptionHandling();

        $res = $this->get(route('cart'));

        $res->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
