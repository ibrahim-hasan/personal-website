<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Services\EditorialApi\EditorialArticleRelations;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishEditorialArticle
{
    public function __construct(
        private readonly ArticlePublicationValidator $publicationValidator,
        private readonly EditorialArticleRelations $relations,
    ) {}

    public function handle(
        Article $article,
        ?int $expectedRevision = null,
        string $feedbackLocale = 'en',
    ): Article {
        return DB::transaction(function () use ($article, $expectedRevision, $feedbackLocale): Article {
            $article = $this->lockedArticle($article);

            $this->assertExpectedRevision($article, $expectedRevision, $feedbackLocale);
            $this->publicationValidator->assertReadyToPublish($article, $feedbackLocale);

            $article->update([
                'is_published' => true,
                'published_at' => today(),
                'modified_at' => today(),
                'editorial_revision' => $article->editorial_revision + 1,
            ]);

            $article = $article->refresh();
            $this->relations->captureRevisionSnapshot($article, 'article.published');

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
}
