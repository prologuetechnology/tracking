<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttachRequestContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->requestId($request);
        $context = array_filter([
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => $request->route()?->getName(),
            'user_id' => $request->user()?->getAuthIdentifier(),
            'company_id' => $this->companyId($request),
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        Context::add($context);
        Log::shareContext($context);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    private function requestId(Request $request): string
    {
        $requestId = $request->headers->get('X-Request-Id');

        if (is_string($requestId) && $requestId !== '' && strlen($requestId) <= 100) {
            return $requestId;
        }

        return (string) Str::uuid();
    }

    private function companyId(Request $request): ?int
    {
        $company = $request->route('company');

        if ($company instanceof Company) {
            return $company->id;
        }

        return is_numeric($company) ? (int) $company : null;
    }
}
