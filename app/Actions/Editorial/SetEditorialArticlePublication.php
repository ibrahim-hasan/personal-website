<?php

namespace App\Actions\Editorial;

use App\Models\Article;

class SetEditorialArticlePublication
{
    public function handle(Article $article, bool $published): Article
    {
        $article->update([
            'is_published' => $published,
            'published_at' => $published ? today() : $article->published_at,
            'modified_at' => today(),
            'editorial_revision' => $article->editorial_revision + 1,
        ]);

        return $article->refresh();
    }
}
