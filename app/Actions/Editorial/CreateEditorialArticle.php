<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Support\Editorial\ArticleBody;

class CreateEditorialArticle
{
    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): Article
    {
        $attributes = app(ArticleBody::class)->normalizeInput($attributes);

        $article = Article::query()->create([
            ...$attributes,
            'published_at' => today(),
            'modified_at' => today(),
            'featured' => false,
            'is_published' => false,
            'editorial_revision' => 1,
        ]);

        return $article;
    }
}
