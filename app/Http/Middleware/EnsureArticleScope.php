<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Client;

class EnsureArticleScope extends EnsureApiScope
{
    protected function requiresClientCredentials(): bool
    {
        return false;
    }

    /** @param list<string> $scopes */
    protected function userCanUseScopes(?object $user, array $scopes): bool
    {
        if ($user === null || ! $user->is_active) {
            return false;
        }

        $permissions = [
            'articles:read' => 'view_any articles',
            'articles:write' => 'create articles',
            'articles:publish' => 'publish articles',
            'articles:archive' => 'delete articles',
            'media:write' => 'update articles',
        ];

        foreach ($scopes as $scope) {
            if (! isset($permissions[$scope]) || ! $user->can($permissions[$scope])) {
                return false;
            }
        }

        return true;
    }

    protected function tokenValidationLogEvent(): string
    {
        return 'editorial_api_token_validation_failed';
    }

    protected function setRequestAttributes(Request $request, AccessToken $token, Client $client): void
    {
        parent::setRequestAttributes($request, $token, $client);

        $request->attributes->set('editorial_api_token', $token);
        $request->attributes->set('editorial_api_client', $client);
    }
}
