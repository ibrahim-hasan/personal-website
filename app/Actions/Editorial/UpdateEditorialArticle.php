<?php

namespace App\Actions\Editorial;

use App\Models\Article;

class UpdateEditorialArticle
{
    /** @param array<string, mixed> $attributes */
    public function handle(Article $article, array $attributes): Article
    {
        $article->update([
            ...$attributes,
            'modified_at' => today(),
            'editorial_revision' => $article->editorial_revision + 1,
        ]);

        return $article->refresh();
    }
}
