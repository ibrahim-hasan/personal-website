<?php

namespace App\Actions\Editorial;

use App\Models\Article;
use App\Models\ArticleSlugRedirect;
use App\Rules\AvailableArticleSlug;
use Illuminate\Validation\ValidationException;

final class ArticleSlugHistory
{
    /** @var list<string> */
    private const array LOCALES = ['ar', 'en'];

    /**
     * @param  array<string, mixed>  $slugs
     */
    public function assertAvailable(
        array $slugs,
        ?Article $ignoredArticle = null,
        string $feedbackLocale = 'en',
    ): void {
        $errors = [];

        foreach (self::LOCALES as $locale) {
            $slug = $slugs[$locale] ?? null;

            if (! is_string($slug)) {
                continue;
            }

            $rule = new AvailableArticleSlug($locale, $ignoredArticle?->getKey());

            if ($rule->isAvailable($slug)) {
                continue;
            }

            $attribute = "slug.{$locale}";
            $errors[$attribute] = [__('validation.unique', ['attribute' => $attribute], $feedbackLocale)];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, mixed>  $requestedSlugs
     */
    public function preserve(
        Article $article,
        array $requestedSlugs,
        string $feedbackLocale = 'en',
    ): void {
        $this->assertAvailable($requestedSlugs, $article, $feedbackLocale);

        foreach (self::LOCALES as $locale) {
            $requestedSlug = $requestedSlugs[$locale] ?? null;

            if (! is_string($requestedSlug)) {
                continue;
            }

            $currentSlug = trim((string) $article->getTranslation('slug', $locale, false));

            if ($currentSlug === $requestedSlug) {
                continue;
            }

            $article->slugRedirects()
                ->where('locale', $locale)
                ->where('slug', $requestedSlug)
                ->delete();

            if ($currentSlug !== '') {
                $redirect = ArticleSlugRedirect::query()->firstOrCreate(
                    ['locale' => $locale, 'slug' => $currentSlug],
                    ['article_id' => $article->getKey()],
                );

                if ((int) $redirect->article_id !== (int) $article->getKey()) {
                    $attribute = "slug.{$locale}";

                    throw ValidationException::withMessages([
                        $attribute => [__('validation.unique', ['attribute' => $attribute], $feedbackLocale)],
                    ]);
                }
            }
        }
    }
}
