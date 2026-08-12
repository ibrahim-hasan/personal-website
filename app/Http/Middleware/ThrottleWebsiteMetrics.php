<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleWebsiteMetrics
{
    private const int MaximumAttempts = 30;

    private const int DecaySeconds = 60;

    public function __construct(private readonly RateLimiter $rateLimiter) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->key($request);

        if ($this->rateLimiter->tooManyAttempts($key, self::MaximumAttempts)) {
            throw new ThrottleRequestsException('Too Many Attempts.', headers: [
                'Retry-After' => max(1, $this->rateLimiter->availableIn($key)),
                'X-RateLimit-Limit' => self::MaximumAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        $this->rateLimiter->hit($key, self::DecaySeconds);

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) self::MaximumAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->rateLimiter->remaining($key, self::MaximumAttempts));

        return $response;
    }

    private function key(Request $request): string
    {
        $clientId = (string) $request->attributes->get('api_client')?->getKey();

        return 'website-metrics:'.hash('sha256', $clientId.'|'.$request->ip());
    }
}
