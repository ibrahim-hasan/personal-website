<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Exceptions\MissingScopeException;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiScope
{
    public function __construct(
        private readonly ClientRepository $clients,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        if (! $request->bearerToken()) {
            throw new AuthenticationException;
        }

        $psrRequest = (new PsrHttpFactory)->createRequest($request);

        try {
            $psrRequest = app(ResourceServer::class)->validateAuthenticatedRequest($psrRequest);
        } catch (OAuthServerException $exception) {
            Log::notice($this->tokenValidationLogEvent(), [
                'request_id' => $this->requestId($request),
                'exception' => $exception::class,
                'reason' => $exception->getMessage(),
            ]);

            throw new AuthenticationException;
        }

        $token = AccessToken::fromPsrRequest($psrRequest);
        $client = $this->clients->findActive((string) $token->oauth_client_id);

        if ($client === null) {
            throw new AuthenticationException;
        }

        $request->attributes->set('api_client', $client);

        foreach ($scopes as $scope) {
            if ($token->cant($scope)) {
                throw new MissingScopeException($scope);
            }
        }

        $isClientCredentialsToken = $this->isClientCredentialsToken($token, $client);

        if ($this->requiresClientCredentials() && ! $isClientCredentialsToken) {
            throw new AuthenticationException;
        }

        if (! $isClientCredentialsToken
            && filled($token->oauth_user_id)
            && ! $this->userCanUseScopes(Auth::guard('api')->user(), $scopes)) {
            throw new AuthenticationException;
        }

        $this->setRequestAttributes($request, $token, $client);

        return $next($request);
    }

    /** @param list<string> $scopes */
    protected function userCanUseScopes(?object $user, array $scopes): bool
    {
        return false;
    }

    protected function requiresClientCredentials(): bool
    {
        return true;
    }

    protected function tokenValidationLogEvent(): string
    {
        return 'api_token_validation_failed';
    }

    protected function setRequestAttributes(Request $request, AccessToken $token, Client $client): void
    {
        $request->attributes->set('api_token', $token);
        $request->attributes->set('api_client', $client);
    }

    private function isClientCredentialsToken(AccessToken $token, Client $client): bool
    {
        return $client->hasGrantType('client_credentials')
            && (blank($token->oauth_user_id) || (string) $token->oauth_user_id === (string) $token->oauth_client_id);
    }

    private function requestId(Request $request): ?string
    {
        $requestId = $request->attributes->get('api_request_id')
            ?? $request->attributes->get('editorial_api_request_id');

        return is_string($requestId) ? $requestId : null;
    }
}
