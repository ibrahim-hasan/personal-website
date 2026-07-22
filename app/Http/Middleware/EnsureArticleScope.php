<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\AccessToken;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Exceptions\MissingScopeException;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

class EnsureArticleScope
{
    public function __construct(
        private readonly ClientRepository $clients,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
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
            Log::notice('editorial_api_token_validation_failed', [
                'request_id' => $request->attributes->get('editorial_api_request_id'),
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

        foreach ($scopes as $scope) {
            if ($token->cant($scope)) {
                throw new MissingScopeException($scope);
            }
        }

        $user = Auth::guard('api')->user();
        $isClientCredentialsToken = $client->hasGrantType('client_credentials')
            && (string) $token->oauth_user_id === (string) $token->oauth_client_id;

        if (! $isClientCredentialsToken && filled($token->oauth_user_id) && ($user === null || ! $user->is_active || ! $this->userCanUseScopes($user, $scopes))) {
            throw new AuthenticationException;
        }

        $request->attributes->set('editorial_api_token', $token);
        $request->attributes->set('editorial_api_client', $client);

        return $next($request);
    }

    /** @param list<string> $scopes */
    private function userCanUseScopes(object $user, array $scopes): bool
    {
        $permissions = [
            'articles:read' => 'view_any articles',
            'articles:write' => 'create articles',
            'articles:publish' => 'publish articles',
            'articles:archive' => 'delete articles',
            'media:write' => 'update articles',
        ];

        foreach ($scopes as $scope) {
            if (! $user->can($permissions[$scope])) {
                return false;
            }
        }

        return true;
    }
}
