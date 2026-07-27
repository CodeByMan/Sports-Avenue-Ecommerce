<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Payment webhooks are registered in a stateless route file and therefore
     * do not require a CSRF exception here.
     *
     * @var array<int, string>
     */
    protected $except = [];
}
