<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use Illuminate\Validation\ValidationException;

final class AssertEditorialArticleIsDraft
{
    public function handle(Article $article): void
    {
        if (! $article->is_published) {
            return;
        }

        throw ValidationException::withMessages([
            'article' => ['Unpublish this article before editing its content or media, then publish it again after review.'],
        ]);
    }
}
