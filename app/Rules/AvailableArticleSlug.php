<?php

namespace App\Rules;

use App\Models\Article;
use App\Models\ArticleSlugRedirect;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;

final class AvailableArticleSlug implements ValidationRule
{
    private readonly string $articleSlugColumn;

    public function __construct(
        private readonly string $locale,
        private readonly int|string|null $ignoredArticleId = null,
    ) {
        $this->articleSlugColumn = match ($this->locale) {
            'ar' => 'slug_ar',
            'en' => 'slug_en',
            default => throw new InvalidArgumentException("Unsupported article slug locale [{$this->locale}]."),
        };
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $this->isAvailable($value)) {
            return;
        }

        $fail(__('validation.unique', ['attribute' => $attribute]));
    }

    public function isAvailable(string $slug): bool
    {
        $currentSlugExists = Article::withTrashed()
            ->where($this->articleSlugColumn, $slug)
            ->when(
                $this->ignoredArticleId !== null,
                fn ($query) => $query->whereKeyNot($this->ignoredArticleId),
            )
            ->exists();

        if ($currentSlugExists) {
            return false;
        }

        return ! ArticleSlugRedirect::query()
            ->where('locale', $this->locale)
            ->where('slug', $slug)
            ->when(
                $this->ignoredArticleId !== null,
                fn ($query) => $query->where('article_id', '!=', $this->ignoredArticleId),
            )
            ->exists();
    }
}
