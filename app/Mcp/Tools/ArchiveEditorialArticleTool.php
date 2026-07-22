<?php

namespace App\Mcp\Tools;

use App\Models\Article;
use App\Services\EditorialApi\Concurrency;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Archive an article. Explicit confirmation and current revision are required.')]
class ArchiveEditorialArticleTool extends EditorialTool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $this->requireScope('articles:archive');
        $input = $request->validate([
            'id' => ['required', 'integer'],
            'revision' => ['required', 'integer', 'min:1'],
            'confirm' => ['required', 'accepted'],
        ]);
        $article = Article::query()->findOrFail($input['id']);
        request()->headers->set('If-Match', (string) $input['revision']);
        app(Concurrency::class)->assertCurrent(request(), $article);
        $article->update(['editorial_revision' => $article->editorial_revision + 1, 'modified_at' => today()]);
        $article->delete();
        $this->audit('article.archived', $article);

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
            'confirm' => $schema->boolean()->description('Must be true to archive.')->required(),
        ];
    }
}
