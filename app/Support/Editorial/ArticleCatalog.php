<?php

namespace App\Support\Editorial;

use App\Models\Article as ArticleRecord;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;

final class ArticleCatalog
{
    /**
     * @var list<array{
     *     key: string,
     *     slugs: array{ar: string, en: string},
     *     published_at: string,
     *     modified_at: string,
     *     image: string,
     *     read_minutes: array{ar: int, en: int},
     *     topics: list<string>,
     *     featured?: bool,
     *     source_url?: string
     * }>
     */
    private const DEFINITIONS = [
        [
            'key' => 'ai-value',
            'slugs' => [
                'ar' => 'من-تجربة-الذكاء-الاصطناعي-إلى-تحقيق-القيمة',
                'en' => 'from-ai-experiment-to-business-value',
            ],
            'published_at' => '2026-07-11',
            'modified_at' => '2026-07-15',
            'image' => 'images/ibrahim/ibrahim-speaking-hero.webp',
            'read_minutes' => ['ar' => 16, 'en' => 14],
            'topics' => ['ai_strategy', 'transformation', 'leadership'],
            'featured' => true,
            'source_url' => 'https://www.linkedin.com/pulse/%D9%85%D9%86-%D8%AA%D8%AC%D8%B1%D8%A8%D8%A9-%D8%A7%D9%84%D8%B0%D9%83%D8%A7%D8%A1-%D8%A7%D9%84%D8%A7%D8%B5%D8%B7%D9%86%D8%A7%D8%B9%D9%8A-%D8%A5%D9%84%D9%89-%D8%AA%D8%AD%D9%82%D9%8A%D9%82-%D8%A7%D9%84%D9%82%D9%8A%D9%85%D8%A9-%D8%A7%D9%84%D8%AE%D8%B7%D9%88%D8%A7%D8%AA-%D8%A7%D9%84%D8%B9%D9%85%D9%84%D9%8A%D8%A9-hasan-kjgwf/',
        ],
        [
            'key' => 'ai-not-answer',
            'slugs' => [
                'ar' => 'متى-لا-يكون-الذكاء-الاصطناعي-هو-الحل',
                'en' => 'when-ai-is-not-the-answer',
            ],
            'published_at' => '2026-07-05',
            'modified_at' => '2026-07-05',
            'image' => 'images/projects/atlas/digi-pedia-ai-learning.webp',
            'read_minutes' => ['ar' => 8, 'en' => 7],
            'topics' => ['ai_strategy', 'operations'],
            'featured' => true,
        ],
        [
            'key' => 'transformation-before-software',
            'slugs' => [
                'ar' => 'لماذا-يفشل-التحول-قبل-بناء-البرمجيات',
                'en' => 'why-transformation-fails-before-software',
            ],
            'published_at' => '2026-06-28',
            'modified_at' => '2026-06-28',
            'image' => 'images/projects/atlas/wafaa-education-transformation.webp',
            'read_minutes' => ['ar' => 8, 'en' => 7],
            'topics' => ['transformation', 'leadership', 'operations'],
            'featured' => true,
        ],
        [
            'key' => 'data-readiness',
            'slugs' => [
                'ar' => 'جاهزية-البيانات-قبل-الذكاء-الاصطناعي',
                'en' => 'data-readiness-before-ai',
            ],
            'published_at' => '2026-06-20',
            'modified_at' => '2026-06-20',
            'image' => 'images/projects/atlas/rafid-humanitarian-collaboration.webp',
            'read_minutes' => ['ar' => 9, 'en' => 8],
            'topics' => ['data', 'governance', 'ai_strategy'],
        ],
        [
            'key' => 'human-in-loop',
            'slugs' => [
                'ar' => 'الإنسان-داخل-حلقة-القرار',
                'en' => 'where-human-judgment-belongs-in-ai-workflows',
            ],
            'published_at' => '2026-06-12',
            'modified_at' => '2026-06-12',
            'image' => 'images/projects/atlas/rannan-caller-trust.webp',
            'read_minutes' => ['ar' => 8, 'en' => 8],
            'topics' => ['governance', 'operations', 'ai_strategy'],
        ],
        [
            'key' => 'first-ai-use-case',
            'slugs' => [
                'ar' => 'اختيار-أول-حالة-استخدام-للذكاء-الاصطناعي',
                'en' => 'choosing-your-first-measurable-ai-use-case',
            ],
            'published_at' => '2026-06-04',
            'modified_at' => '2026-06-04',
            'image' => 'images/projects/atlas/maazim-gifting-operations.webp',
            'read_minutes' => ['ar' => 9, 'en' => 8],
            'topics' => ['ai_strategy', 'leadership', 'operations'],
        ],
        [
            'key' => 'automation-assistant-agent',
            'slugs' => [
                'ar' => 'الأتمتة-أم-المساعد-أم-الوكيل',
                'en' => 'automation-assistant-or-agent',
            ],
            'published_at' => '2026-05-27',
            'modified_at' => '2026-05-27',
            'image' => 'images/projects/atlas/investments-2060-shareholder-services.webp',
            'read_minutes' => ['ar' => 7, 'en' => 7],
            'topics' => ['operations', 'ai_strategy', 'products'],
        ],
        [
            'key' => 'measure-digital-impact',
            'slugs' => [
                'ar' => 'قياس-أثر-المنتجات-والتحول-الرقمي',
                'en' => 'measuring-digital-product-and-transformation-impact',
            ],
            'published_at' => '2026-05-18',
            'modified_at' => '2026-05-18',
            'image' => 'images/projects/atlas/bosalty-tourism-journeys.webp',
            'read_minutes' => ['ar' => 8, 'en' => 8],
            'topics' => ['products', 'transformation', 'leadership'],
        ],
        [
            'key' => 'ai-governance',
            'slugs' => [
                'ar' => 'نموذج-تشغيلي-لحوكمة-الذكاء-الاصطناعي',
                'en' => 'a-practical-operating-model-for-ai-governance',
            ],
            'published_at' => '2026-05-08',
            'modified_at' => '2026-05-08',
            'image' => 'images/projects/atlas/rafid-humanitarian-collaboration.webp',
            'read_minutes' => ['ar' => 10, 'en' => 9],
            'topics' => ['governance', 'ai_strategy', 'leadership'],
        ],
    ];

    /**
     * @return list<Article>
     */
    public function all(): array
    {
        $articles = $this->storedArticles();

        if ($articles === null) {
            $articles = array_map($this->make(...), self::DEFINITIONS);
        }

        usort(
            $articles,
            fn (Article $first, Article $second): int => $second->publishedAt <=> $first->publishedAt,
        );

        return $articles;
    }

    /**
     * The idempotent import payload for the database-backed publishing system.
     *
     * @return list<array<string, mixed>>
     */
    public static function bootstrapRecords(): array
    {
        return array_map(function (array $definition): array {
            $translations = [];

            foreach (['title', 'summary', 'seo_title', 'seo_description', 'type', 'lead', 'sections', 'closing'] as $field) {
                $translations[$field] = [
                    'ar' => Lang::get("articles.articles.{$definition['key']}.{$field}", [], 'ar'),
                    'en' => Lang::get("articles.articles.{$definition['key']}.{$field}", [], 'en'),
                ];
            }

            return [
                'key' => $definition['key'],
                'slug' => $definition['slugs'],
                ...$translations,
                'published_at' => $definition['published_at'],
                'modified_at' => $definition['modified_at'],
                'image' => $definition['image'],
                'read_minutes' => $definition['read_minutes'],
                'topic_keys' => $definition['topics'],
                'featured' => $definition['featured'] ?? false,
                'source_url' => $definition['source_url'] ?? null,
                'is_published' => true,
            ];
        }, self::DEFINITIONS);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function localized(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return array_map(
            fn (Article $article): array => $this->present($article, $locale),
            $this->all(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function featured(int $limit = 3, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $featured = array_values(array_filter(
            $this->all(),
            fn (Article $article): bool => $article->featured,
        ));

        return array_map(
            fn (Article $article): array => $this->present($article, $locale),
            array_slice($featured, 0, max(0, $limit)),
        );
    }

    public function resolve(string $slug, ?string $locale = null): ?Article
    {
        $locale ??= app()->getLocale();

        foreach ($this->all() as $article) {
            if ($article->slug($locale) === $slug) {
                return $article;
            }
        }

        return null;
    }

    public function findByKey(string $key): ?Article
    {
        foreach ($this->all() as $article) {
            if ($article->key === $key) {
                return $article;
            }
        }

        return null;
    }

    public function url(Article $article, string $locale, bool $absolute = true): string
    {
        return localized_route(
            'writing.show',
            ['article' => $article->slug($locale)],
            $absolute,
            $locale,
        );
    }

    /**
     * @return array{ar: string, en: string}
     */
    public function alternateUrls(Article $article): array
    {
        return [
            'ar' => $this->url($article, 'ar'),
            'en' => $this->url($article, 'en'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function related(Article $current, int $limit = 3, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $candidates = array_values(array_filter(
            $this->all(),
            fn (Article $article): bool => $article->key !== $current->key,
        ));

        usort($candidates, function (Article $first, Article $second) use ($current): int {
            $firstScore = count(array_intersect($current->topicKeys, $first->topicKeys));
            $secondScore = count(array_intersect($current->topicKeys, $second->topicKeys));

            return [$secondScore, $second->publishedAt] <=> [$firstScore, $first->publishedAt];
        });

        return array_map(
            fn (Article $article): array => $this->present($article, $locale),
            array_slice($candidates, 0, max(0, $limit)),
        );
    }

    /**
     * @param  array{
     *     key: string,
     *     slugs: array{ar: string, en: string},
     *     published_at: string,
     *     modified_at: string,
     *     image: string,
     *     read_minutes: array{ar: int, en: int},
     *     topics: list<string>,
     *     featured?: bool,
     *     source_url?: string
     * }  $definition
     */
    private function make(array $definition): Article
    {
        return new Article(
            key: $definition['key'],
            slugs: $definition['slugs'],
            publishedAt: $definition['published_at'],
            modifiedAt: $definition['modified_at'],
            image: $definition['image'],
            readMinutes: $definition['read_minutes'],
            topicKeys: $definition['topics'],
            featured: $definition['featured'] ?? false,
            sourceUrl: $definition['source_url'] ?? null,
        );
    }

    /**
     * @return list<Article>|null
     */
    private function storedArticles(): ?array
    {
        if (! Schema::hasTable('articles')) {
            return null;
        }

        return ArticleRecord::query()
            ->published()
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (ArticleRecord $record): Article => new Article(
                key: $record->key,
                slugs: $record->getTranslations('slug'),
                publishedAt: $record->published_at->toDateString(),
                modifiedAt: $record->modified_at->toDateString(),
                image: $record->imageUrl(),
                readMinutes: $record->getTranslations('read_minutes'),
                topicKeys: $record->topic_keys,
                featured: $record->featured,
                sourceUrl: $record->source_url,
                translations: [
                    'title' => $record->getTranslations('title'),
                    'summary' => $record->getTranslations('summary'),
                    'seo_title' => $record->getTranslations('seo_title'),
                    'seo_description' => $record->getTranslations('seo_description'),
                    'type' => $record->getTranslations('type'),
                    'lead' => $record->getTranslations('lead'),
                    'sections' => $record->getTranslations('sections'),
                    'closing' => $record->getTranslations('closing'),
                ],
            ))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Article $article, string $locale): array
    {
        return [
            ...$article->localized($locale),
            'url' => $this->url($article, $locale),
            'image_url' => asset($article->image),
        ];
    }
}
