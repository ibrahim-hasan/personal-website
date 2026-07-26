<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\Operations\ReleaseReadiness;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadinessController extends Controller
{
    public function __invoke(Request $request, ReleaseReadiness $readiness, RateLimiter $rateLimiter): Response
    {
        try {
            if (! $this->hasValidSecret($request) || ! $this->withinRateLimit($request, $rateLimiter)) {
                return $this->response(Response::HTTP_SERVICE_UNAVAILABLE);
            }

            return $this->response($readiness->passes() ? Response::HTTP_NO_CONTENT : Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\Throwable) {
            return $this->response(Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    private function withinRateLimit(Request $request, RateLimiter $rateLimiter): bool
    {
        $key = 'operations:readiness:'.hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $attempts = max(1, (int) config('operations.readiness.rate_limit_attempts'));

        if ($rateLimiter->tooManyAttempts($key, $attempts)) {
            return false;
        }

        $rateLimiter->hit($key, max(1, (int) config('operations.readiness.rate_limit_decay_seconds')));

        return true;
    }

    private function hasValidSecret(Request $request): bool
    {
        $expected = (string) config('operations.readiness.secret');
        $provided = (string) $request->header((string) config('operations.readiness.header'));

        return $expected !== ''
            && $provided !== ''
            && hash_equals(hash('sha256', $expected), hash('sha256', $provided));
    }

    private function response(int $status): Response
    {
        return response()->noContent($status)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, max-age=0, must-revalidate',
            'X-Robots-Tag' => 'noindex, noarchive',
        ]);
    }
}
