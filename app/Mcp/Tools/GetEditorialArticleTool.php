<?php

namespace App\Mcp\Tools;

use App\Http\Resources\Api\EditorialArticleResource;
use App\Models\Article;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Retrieve one editorial article or list articles by status, including drafts.')]
class GetEditorialArticleTool extends EditorialTool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $this->requireScope('articles:read');

        if ($request->get('id') !== null) {
            return $this->articleResponse(Article::withTrashed()->findOrFail($request->get('id')));
        }

        $status = $request->get('status', 'draft');
        $query = $status === 'archived' ? Article::onlyTrashed() : Article::query();
        $articles = $query
            ->when($status === 'draft', fn ($query) => $query->where('is_published', false))
            ->when($status === 'published', fn ($query) => $query->where('is_published', true))
            ->latest('id')
            ->limit(min((int) $request->get('limit', 25), 100))
            ->get()
            ->map(fn (Article $article): array => (new EditorialArticleResource($article))->resolve(request()))
            ->all();

        return Response::text(json_encode(['articles' => $articles], JSON_THROW_ON_ERROR));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Article ID to retrieve. Omit to list.'),
            'status' => $schema->string()->description('Filter for list requests: draft, published, or archived.'),
            'limit' => $schema->integer()->min(1)->max(100),
        ];
    }
}
