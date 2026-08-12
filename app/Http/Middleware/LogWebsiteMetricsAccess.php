<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Exceptions\AuthenticationException;
use Symfony\Component\HttpFoundation\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogWebsiteMetricsAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $this->record($request, $this->statusFor($exception));

            throw $exception;
        }

        $this->record($request, $response->getStatusCode());

        return $response;
    }

    private function record(Request $request, int $status): void
    {
        Log::info('website_metrics_api_access', [
            'request_id' => $request->attributes->get('api_request_id'),
            'client_id' => $request->attributes->get('api_client')?->getKey(),
            'route' => $request->route()?->getName(),
            'status' => $status,
            'outcome' => $this->outcomeFor($status),
        ]);
    }

    private function statusFor(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof HttpResponseException => $exception->getResponse()->getStatusCode(),
            $exception instanceof AuthenticationException => 401,
            $exception instanceof AuthorizationException => 403,
            $exception instanceof ValidationException => 422,
            $exception instanceof HttpExceptionInterface => $exception->getStatusCode(),
            default => 500,
        };
    }

    private function outcomeFor(int $status): string
    {
        return match ($status) {
            401 => 'unauthenticated',
            403 => 'forbidden',
            422 => 'invalid_request',
            429 => 'rate_limited',
            default => $status >= 200 && $status < 300 ? 'success' : 'error',
        };
    }
}
