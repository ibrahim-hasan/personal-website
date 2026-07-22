<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PublishEditorialArticle
{
    public function handle(Article $article): Article
    {
        $requiredTranslations = [
            'title', 'slug', 'type', 'read_minutes', 'summary', 'lead', 'sections', 'closing', 'seo_title', 'seo_description',
        ];

        foreach ($requiredTranslations as $attribute) {
            $translations = $article->getTranslations($attribute);

            if (blank(Arr::get($translations, 'ar')) || blank(Arr::get($translations, 'en'))) {
                throw ValidationException::withMessages([
                    'article' => ['Complete Arabic and English editorial and SEO content is required before publishing.'],
                ]);
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
