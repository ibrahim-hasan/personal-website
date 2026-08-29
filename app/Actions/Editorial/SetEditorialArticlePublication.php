<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Services\EditorialApi\EditorialArticleRelations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetEditorialArticlePublication
{
    public function __construct(
        private readonly ArticlePublicationDate $publicationDate,
        private readonly EditorialArticleRelations $relations,
    ) {}

    public function handle(
        Article $article,
        bool $published,
        ?int $expectedRevision = null,
        string $feedbackLocale = 'en',
    ): Article {
        return DB::transaction(function () use ($article, $expectedRevision, $published, $feedbackLocale): Article {
            $article = $this->lockedArticle($article);

            $this->assertExpectedRevision($article, $expectedRevision, $feedbackLocale);
            $this->assertPublicationTransition($article, $published, $feedbackLocale);

            $hasPublicationHistory = $article->revisionSnapshots()
                ->whereIn('action', ['article.published', 'article.unpublished_from_public'])
                ->exists();

            $article->update([
                'is_published' => $published,
                'published_at' => $published
                    ? $this->publicationDate->forPublication($article)
                    : $article->published_at,
                'modified_at' => Article::publicationToday(),
                'editorial_revision' => $article->editorial_revision + 1,
            ]);

            $article = $article->refresh();
            $this->relations->captureRevisionSnapshot(
                $article,
                match (true) {
                    $published => 'article.published',
                    ! $hasPublicationHistory => 'article.unpublished_from_public',
                    default => 'article.unpublished',
                },
            );

            return $article;
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

    private function assertPublicationTransition(Article $article, bool $published, string $feedbackLocale): void
    {
        if ($article->is_published !== $published) {
            return;
        }

        throw ValidationException::withMessages([
            'article' => [__('editorial_admin.feedback.invalid_publication_transition', [], $feedbackLocale)],
        ]);
    }
}
