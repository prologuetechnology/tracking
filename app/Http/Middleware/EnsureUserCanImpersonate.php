<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanImpersonate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if the user is authenticated and has the 'company.read' permission
        if (! $user || ! $user->can('user:impersonate')) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
