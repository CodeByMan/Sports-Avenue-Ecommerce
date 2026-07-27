<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $availableRole): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null
            && $user->status === User::STATUS_ACTIVE
            && checkRole($availableRole, $user->role),
            403,
        );

        return $next($request);
    }
}
