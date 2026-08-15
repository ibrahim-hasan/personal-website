<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Services\EditorialApi\EditorialArticleRelations;
use App\Support\Editorial\ArticleBody;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateEditorialArticle
{
    public function __construct(
        private readonly AssertEditorialArticleIsDraft $assertEditorialArticleIsDraft,
        private readonly ArticleBody $articleBody,
        private readonly EditorialArticleRelations $relations,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(
        Article $article,
        array $attributes,
        ?int $expectedRevision = null,
        string $feedbackLocale = 'en',
    ): Article {
        $updatesBody = array_key_exists('body', $attributes)
            || array_key_exists('body_ar', $attributes)
            || array_key_exists('body_en', $attributes)
            || array_key_exists('lead', $attributes)
            || array_key_exists('sections', $attributes)
            || array_key_exists('closing', $attributes);

        return DB::transaction(function () use ($article, $attributes, $expectedRevision, $feedbackLocale, $updatesBody): Article {
            $article = $this->lockedArticle($article);

            $this->assertExpectedRevision($article, $expectedRevision, $feedbackLocale);
            $this->assertEditorialArticleIsDraft->handle($article, $feedbackLocale);
            $this->relations->validate($attributes, $article);

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

    private function lockedArticle(Article $article): Article
    {
        return Article::withTrashed()
            ->lockForUpdate()
            ->findOrFail($article->getKey());
    }

    private function assertExpectedRevision(Article $article, ?int $expectedRevision, string $feedbackLocale): void
    {
        if ($expectedRevision === null || $article->editorial_revision === $expectedRevision) {
            return;
        }

        throw ValidationException::withMessages([
            'article' => [__('editorial_admin.feedback.stale_edit', [], $feedbackLocale)],
        ]);
    }
}
