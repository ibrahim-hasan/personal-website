<?php

namespace App\Mcp\Tools;

use App\Http\Resources\Api\EditorialArticleResource;
use App\Models\Article;
use App\Services\EditorialApi\Audit;
use Illuminate\Auth\Access\AuthorizationException;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Passport\AccessToken;

abstract class EditorialTool extends Tool
{
    protected function requireScope(string $scope): void
    {
        /** @var AccessToken|null $token */
        $token = request()->attributes->get('editorial_api_token');

        if ($token === null || $token->cant($scope)) {
            throw new AuthorizationException('The connected client does not have the required editorial scope.');
        }
    }

    protected function articleResponse(Article $article): Response
    {
        return Response::text(json_encode([
            'article' => (new EditorialArticleResource($article))->resolve(request()),
        ], JSON_THROW_ON_ERROR));
    }

    protected function audit(string $action, Article $article): void
    {
        app(Audit::class)->record(request(), $action, 'success', $article);
    }
}
