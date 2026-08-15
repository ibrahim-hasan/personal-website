<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use Illuminate\Validation\ValidationException;

final class AssertEditorialArticleIsDraft
{
    public function handle(Article $article, string $feedbackLocale = 'en'): void
    {
        if (! $article->is_published) {
            return;
        }

        throw ValidationException::withMessages([
            'article' => [__('editorial_admin.feedback.published_locked', [], $feedbackLocale)],
        ]);
    }
}
