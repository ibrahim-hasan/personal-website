<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Support\Editorial\ArticleBody;

class UpdateEditorialArticle
{
    public function __construct(
        private readonly AssertEditorialArticleIsDraft $assertEditorialArticleIsDraft,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Article $article, array $attributes): Article
    {
        $this->assertEditorialArticleIsDraft->handle($article);
        $articleBody = app(ArticleBody::class);
        $updatesBody = isset($attributes['body'])
            || isset($attributes['lead'])
            || isset($attributes['sections'])
            || isset($attributes['closing']);
        $attributes = $articleBody->normalizeInput($attributes);

        $article->update([
            ...$attributes,
            'modified_at' => today(),
            'editorial_revision' => $article->editorial_revision + 1,
        ]);

        if ($updatesBody) {
            $articleBody->cleanUpUnusedImages($article);
        }

        return $article->refresh();
    }
}
