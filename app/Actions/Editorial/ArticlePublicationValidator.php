<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Support\Editorial\ArticleBody;

/**
 * Read-only public eligibility for an Article.
 *
 * This is intentionally separate from the publish action: legacy public
 * Articles may use the existing approved image path while new editorial
 * publishing requires a managed upload.
 */
final class ArticlePublicationValidator
{
    private const array LOCALES = ['ar', 'en'];

    private const array REQUIRED_TEXT_FIELDS = [
        'title',
        'slug',
        'type',
        'summary',
        'image_alt',
        'seo_title',
        'seo_description',
    ];

    public function __construct(private readonly ArticleBody $body) {}

    /** @return list<string> */
    public function violations(Article $article, bool $requirePublicState = true): array
    {
        $violations = [];

        if ($requirePublicState) {
            if (! $article->is_published) {
                $violations[] = 'article.not_published';
            }

            if ($article->published_at === null || $article->published_at->isFuture()) {
                $violations[] = 'article.not_available';
            }

            if ($article->trashed()) {
                $violations[] = 'article.deleted';
            }
        }

        foreach (self::LOCALES as $locale) {
            foreach (self::REQUIRED_TEXT_FIELDS as $field) {
                $value = $article->getTranslation($field, $locale, false);

                if (! is_string($value) || trim($value) === '') {
                    $violations[] = "translation.{$locale}.{$field}.missing";
                }
            }

            $content = $article->getTranslation('body', $locale, false);

            if (! $this->body->isComplete(is_array($content) ? $content : null)) {
                $violations[] = "translation.{$locale}.body.incomplete";

                continue;
            }

            $ownedAttachmentIds = $article->getMedia(Article::bodyCollection($locale))
                ->pluck('uuid')
                ->all();

            foreach ($this->body->images($content) as $image) {
                if ($image['id'] === '' || ! in_array($image['id'], $ownedAttachmentIds, true)) {
                    $violations[] = "translation.{$locale}.body.image_not_owned";
                }

                if ($image['alt'] === '') {
                    $violations[] = "translation.{$locale}.body.image_alt_missing";
                }
            }
        }

        if (! is_array($article->topic_keys) || $article->topic_keys === []) {
            $violations[] = 'article.topics_missing';
        }

        if (trim($article->imageUrl()) === '') {
            $violations[] = 'article.image_missing';
        }

        return array_values(array_unique($violations));
    }

    public function isPubliclyEligible(Article $article): bool
    {
        return $this->violations($article) === [];
    }

    public function isPublishable(Article $article): bool
    {
        return $this->isPubliclyEligible($article);
    }
}
