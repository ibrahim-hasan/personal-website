<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPrivacyHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $routeName = (string) $request->route()?->getName();
        $isSensitiveRoute = str_contains($routeName, 'reader.')
            || str_contains($routeName, 'verification.');

        $response->headers->set(
            'Referrer-Policy',
            $isSensitiveRoute ? 'no-referrer' : 'strict-origin',
        );

        return $response;
    }
}
