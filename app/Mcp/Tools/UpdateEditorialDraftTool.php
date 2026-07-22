<?php

namespace App\Mcp\Tools;

use App\Actions\Editorial\UpdateEditorialArticle;
use App\Http\Requests\Api\UpdateEditorialArticleRequest;
use App\Models\Article;
use App\Services\EditorialApi\Concurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Validator;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Update a draft article. The current revision is required for optimistic concurrency.')]
class UpdateEditorialDraftTool extends EditorialTool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $this->requireScope('articles:write');
        $input = $request->validate([
            'id' => ['required', 'integer'],
            'revision' => ['required', 'integer', 'min:1'],
            'article' => ['required', 'array'],
        ]);
        $article = Article::query()->where('is_published', false)->findOrFail($input['id']);
        request()->headers->set('If-Match', (string) $input['revision']);
        app(Concurrency::class)->assertCurrent(request(), $article);
        $attributes = Validator::make($input['article'], app(UpdateEditorialArticleRequest::class)->rules())->validate();
        $article = app(UpdateEditorialArticle::class)->handle($article, $attributes);
        $this->audit('article.updated', $article);

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
            'id' => $schema->integer()->required(),
            'revision' => $schema->integer()->min(1)->required(),
            'article' => $schema->object()->description('Partial bilingual article fields to update.')->required(),
        ];
    }
}
