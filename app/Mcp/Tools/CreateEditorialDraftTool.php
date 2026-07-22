<?php

namespace App\Mcp\Tools;

use App\Actions\Editorial\CreateEditorialArticle;
use App\Http\Requests\Api\StoreEditorialArticleRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new bilingual article draft. This never publishes the article.')]
class CreateEditorialDraftTool extends EditorialTool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $this->requireScope('articles:write');
        $attributes = $request->validate([
            'article' => ['required', 'array'],
        ])['article'];
        $attributes = Validator::make($attributes, app(StoreEditorialArticleRequest::class)->rules())->validate();
        $article = app(CreateEditorialArticle::class)->handle($attributes);
        $this->audit('article.created', $article);

        return $this->articleResponse($article);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'article' => $schema->object()->description('Complete article payload using Arabic and English translated fields.')->required(),
        ];
    }
}
