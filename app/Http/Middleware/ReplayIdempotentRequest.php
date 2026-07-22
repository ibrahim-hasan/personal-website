<?php

namespace App\Http\Middleware;

use App\Services\EditorialApi\Idempotency;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReplayIdempotentRequest
{
    public function __construct(
        private readonly Idempotency $idempotency,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $this->idempotency->replay($request) ?? $next($request);
    }
}
