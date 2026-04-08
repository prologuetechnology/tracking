<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if the user is authenticated and has the Super Admin role
        if (! $user || ! $user->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
