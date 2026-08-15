<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Support\Editorial\ArticleBody;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

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

    private const array PUBLISH_REQUIRED_TRANSLATIONS = [
        'title',
        'slug',
        'type',
        'summary',
        'body',
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

    /**
     * Return the saved-draft requirements that the publication action enforces.
     *
     * Unlike public eligibility, this deliberately requires a managed hero image
     * so that a newly published article never reintroduces a legacy image path.
     *
     * @return list<string>
     */
    public function publishReadinessViolations(Article $article): array
    {
        $violations = [];

        foreach (self::PUBLISH_REQUIRED_TRANSLATIONS as $field) {
            $translations = $article->getTranslations($field);

            foreach (self::LOCALES as $locale) {
                if (blank(Arr::get($translations, $locale))) {
                    $violations[] = "translation.{$locale}.{$field}.missing";
                }
            }
        }

        foreach (self::LOCALES as $locale) {
            $content = $article->getTranslation('body', $locale, false);

            if (in_array("translation.{$locale}.body.missing", $violations, true)) {
                continue;
            }

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

        if ($article->topic_keys === [] || $article->topic_keys === null) {
            $violations[] = 'article.topics_missing';
        }

        if (! $article->hasMedia(Article::IMAGE_COLLECTION)) {
            $violations[] = 'article.image_missing';
        }

        return array_values(array_unique($violations));
    }

    public function isReadyToPublish(Article $article): bool
    {
        return $this->publishReadinessViolations($article) === [];
    }

    public function assertReadyToPublish(Article $article, string $feedbackLocale = 'en'): void
    {
        $violations = $this->publishReadinessViolations($article);

        if ($violations === []) {
            return;
        }

        throw ValidationException::withMessages([
            'article' => [$this->publishErrorMessage($violations, $feedbackLocale)],
        ]);
    }

    public function publishReadinessMessage(string $violation): string
    {
        if (preg_match('/^translation\.(ar|en)\.([a-z_]+)\.missing$/', $violation, $matches) === 1) {
            return __('editorial_admin.readiness.violations.translation_missing', [
                'field' => __('editorial_admin.fields.'.$matches[2]),
                'locale' => __('editorial_admin.locales.'.$matches[1]),
            ]);
        }

        if (preg_match('/^translation\.(ar|en)\.body\.(incomplete|image_not_owned|image_alt_missing)$/', $violation, $matches) === 1) {
            return __('editorial_admin.readiness.violations.'.$matches[2], [
                'locale' => __('editorial_admin.locales.'.$matches[1]),
            ]);
        }

        return match ($violation) {
            'article.topics_missing' => __('editorial_admin.readiness.violations.topics_missing'),
            'article.image_missing' => __('editorial_admin.readiness.violations.image_missing'),
            default => __('editorial_admin.readiness.violations.unknown'),
        };
    }

    /** @param list<string> $violations */
    private function publishErrorMessage(array $violations, string $feedbackLocale): string
    {
        if (collect($violations)->contains(fn (string $violation): bool => str_ends_with($violation, '.missing'))) {
            return __('editorial_admin.validation.publish_missing_translations', [], $feedbackLocale);
        }

        foreach ($violations as $violation) {
            $message = match (true) {
                str_ends_with($violation, '.body.incomplete') => __('editorial_admin.validation.publish_incomplete_body', [], $feedbackLocale),
                str_ends_with($violation, '.body.image_not_owned') => __('editorial_admin.validation.publish_image_not_owned', [], $feedbackLocale),
                str_ends_with($violation, '.body.image_alt_missing') => __('editorial_admin.validation.publish_image_alt_missing', [], $feedbackLocale),
                $violation === 'article.topics_missing' => __('editorial_admin.validation.publish_topics_missing', [], $feedbackLocale),
                $violation === 'article.image_missing' => __('editorial_admin.validation.publish_image_missing', [], $feedbackLocale),
                default => null,
            };

            if ($message !== null) {
                return $message;
            }
        }

        return __('editorial_admin.validation.publish_missing_translations', [], $feedbackLocale);
    }
}
