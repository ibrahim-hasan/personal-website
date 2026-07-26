<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Support\Editorial\ArticleBody;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PublishEditorialArticle
{
    public function handle(Article $article): Article
    {
        $requiredTranslations = [
            'title', 'slug', 'type', 'summary', 'body', 'image_alt', 'seo_title', 'seo_description',
        ];

        foreach ($requiredTranslations as $attribute) {
            $translations = $article->getTranslations($attribute);

            if (blank(Arr::get($translations, 'ar')) || blank(Arr::get($translations, 'en'))) {
                throw ValidationException::withMessages([
                    'article' => ['Complete Arabic and English editorial and SEO content is required before publishing.'],
                ]);
            }
        }

        $body = app(ArticleBody::class);

        foreach (['ar', 'en'] as $locale) {
            $content = $article->getTranslation('body', $locale, false);

            if (! $body->isComplete(is_array($content) ? $content : null)) {
                throw ValidationException::withMessages([
                    'article' => ['Each language needs a substantial article body with at least one H2 heading before publishing.'],
                ]);
            }

            $ownedAttachmentIds = $article->getMedia(Article::bodyCollection($locale))
                ->pluck('uuid')
                ->all();

            foreach ($body->images(is_array($content) ? $content : null) as $image) {
                if ($image['id'] === '' || ! in_array($image['id'], $ownedAttachmentIds, strict: true)) {
                    throw ValidationException::withMessages([
                        'article' => ['Every inline image must belong to this article and language before publishing.'],
                    ]);
                }

                if ($image['alt'] === '') {
                    throw ValidationException::withMessages([
                        'article' => ['Every inline image needs meaningful alt text before publishing.'],
                    ]);
                }
            }
        }

        if ($article->topic_keys === [] || $article->topic_keys === null) {
            throw ValidationException::withMessages([
                'article' => ['At least one topic is required before publishing.'],
            ]);
        }

        if (! $article->hasMedia(Article::IMAGE_COLLECTION)) {
            throw ValidationException::withMessages([
                'article' => ['An article image is required before publishing.'],
            ]);
        }

        $article->update([
            'is_published' => true,
            'published_at' => today(),
            'modified_at' => today(),
            'editorial_revision' => $article->editorial_revision + 1,
        ]);

        return $article->refresh();
    }
}
