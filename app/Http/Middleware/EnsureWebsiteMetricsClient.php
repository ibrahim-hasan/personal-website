<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\Exceptions\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebsiteMetricsClient
{
    public function __construct(private readonly ConfigRepository $config) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredClientId = trim((string) $this->config->get('services.website_performance.metrics_api_client_id'));
        $client = $request->attributes->get('api_client');
        $clientId = $client instanceof Client ? (string) $client->getKey() : '';

        if ($configuredClientId === ''
            || $clientId === ''
            || ! hash_equals($configuredClientId, $clientId)
            || $client->scopes !== ['analytics:read']) {
            throw new AuthenticationException;
        }

        return $next($request);
    }
}
