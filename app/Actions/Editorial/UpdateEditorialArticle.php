<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Services\EditorialApi\EditorialArticleRelations;
use App\Support\Editorial\ArticleBody;
use Illuminate\Support\Facades\DB;

class UpdateEditorialArticle
{
    public function __construct(
        private readonly AssertEditorialArticleIsDraft $assertEditorialArticleIsDraft,
        private readonly ArticleBody $articleBody,
        private readonly EditorialArticleRelations $relations,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Article $article, array $attributes): Article
    {
        $this->assertEditorialArticleIsDraft->handle($article);
        $this->relations->validate($attributes);
        $updatesBody = isset($attributes['body'])
            || isset($attributes['lead'])
            || isset($attributes['sections'])
            || isset($attributes['closing']);

        return DB::transaction(function () use ($article, $attributes, $updatesBody): Article {
            $article->update([
                ...$this->articleBody->normalizeInput($this->relations->withoutRelationKeys($attributes)),
                'modified_at' => today(),
                'editorial_revision' => $article->editorial_revision + 1,
            ]);

            if ($updatesBody) {
                $this->articleBody->cleanUpUnusedImages($article);
            }

            $this->relations->sync($article, $attributes);
            $article = $article->refresh();
            $this->relations->captureRevisionSnapshot($article, 'article.updated');

            return $article->load(['services:id,key', 'projects:id,key']);
        });
    }
}
