<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_responses_include_csp_and_modern_security_headers(): void
    {
        Route::middleware('web')->get('/security-headers-test', fn (): string => 'ok');

        $this->get('/security-headers-test')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-site')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_pusher_tls_verification_is_not_disabled(): void
    {
        $options = config('broadcasting.connections.pusher.options', []);
        $clientOptions = config('broadcasting.connections.pusher.client_options', []);

        $this->assertTrue($options['useTLS']);
        $this->assertArrayNotHasKey('curl_options', $options);
        $this->assertNotSame(false, $clientOptions['verify'] ?? null);
    }
}
