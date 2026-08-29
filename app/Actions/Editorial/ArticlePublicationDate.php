<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use Carbon\CarbonInterface;

final class ArticlePublicationDate
{
    public function forPublication(Article $article): CarbonInterface
    {
        $hasPublicationHistory = $article->is_published
            || $article->revisionSnapshots()
                ->whereIn('action', ['article.published', 'article.unpublished_from_public'])
                ->exists();

        if ($hasPublicationHistory && $article->published_at !== null) {
            return $article->published_at;
        }

        return Article::publicationToday();
    }
}
