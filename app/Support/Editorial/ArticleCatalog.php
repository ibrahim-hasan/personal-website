<?php

namespace App\Support\Editorial;

use App\Actions\Editorial\ArticlePublicationValidator;
use App\Models\Article as ArticleRecord;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;

final class ArticleCatalog
{
    /** @var list<Article>|null */
    private ?array $articles = null;

    public function __construct(private readonly ArticlePublicationValidator $publicationValidator) {}

    /**
     * @var list<array{
     *     key: string,
     *     slugs: array{ar: string, en: string},
     *     published_at: string,
     *     modified_at: string,
     *     image: string,
     *     image_alt: array{ar: string, en: string},
     *     read_minutes: array{ar: int, en: int},
     *     topics: list<string>,
     *     featured?: bool,
     *     source_url?: string
     * }>
     */
    private const DEFINITIONS = [
        [
            'key' => 'ai-product-moat',
            'slugs' => [
                'ar' => 'حين-يصبح-الذكاء-الاصطناعي-متاحا-للجميع-كيف-تبني-منتجا-يصعب-تقليده',
                'en' => 'building-an-ai-product-that-is-hard-to-copy',
            ],
            'published_at' => '2026-07-22',
            'modified_at' => '2026-07-25',
            'image' => 'images/ibrahim/product-systems.png',
            'image_alt' => [
                'ar' => 'رسم توضيحي لنظام منتج مترابط يجمع وحدات وسير بيانات متعدد',
                'en' => 'Illustration of an interconnected product system with multiple modules and data flows',
            ],
            'read_minutes' => ['ar' => 4, 'en' => 3],
            'topics' => ['artificial-intelligence', 'product-strategy', 'competitive-advantage', 'saas', 'governance'],
            'featured' => true,
        ],
        [
            'key' => 'ai-value',
            'slugs' => [
                'ar' => 'من-تجربة-الذكاء-الاصطناعي-إلى-تحقيق-القيمة',
                'en' => 'from-ai-experiment-to-business-value',
            ],
            'published_at' => '2026-07-11',
            'modified_at' => '2026-07-25',
            'image' => 'images/ibrahim/ibrahim-speaking-hero.webp',
            'image_alt' => [
                'ar' => 'إبراهيم حسن يتحدث في جلسة عن الذكاء الاصطناعي والتحول',
                'en' => 'Ibrahim Hasan speaking during a session about AI and transformation',
            ],
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
            'modified_at' => '2026-07-25',
            'image' => 'images/projects/atlas/digi-pedia-ai-learning.webp',
            'image_alt' => [
                'ar' => 'واجهة الموسوعة الرقمية لتعلّم الذكاء الاصطناعي بالعربية',
                'en' => 'Digi Pedia Arabic AI learning platform interface',
            ],
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
            'modified_at' => '2026-07-25',
            'image' => 'images/projects/atlas/wafaa-education-transformation.webp',
            'image_alt' => [
                'ar' => 'منظومة وفاء لإدارة التعليم والعمل غير الربحي',
                'en' => 'Wafaa education and nonprofit operating ecosystem',
            ],
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
            'modified_at' => '2026-07-25',
            'image' => 'images/projects/atlas/rafid-humanitarian-collaboration.webp',
            'image_alt' => [
                'ar' => 'منصة رافد 360 للتعاون بين المنظمات الإنسانية',
                'en' => 'Rafid 360 humanitarian organization collaboration workspace',
            ],
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
            'modified_at' => '2026-07-25',
            'image' => 'images/projects/atlas/rannan-caller-trust.webp',
            'image_alt' => [
                'ar' => 'تجربة رنان للتعرّف على هوية المتصل',
                'en' => 'Rannan caller-identification experience',
            ],
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
            'modified_at' => '2026-07-25',
            'image' => 'images/projects/atlas/maazim-gifting-operations.webp',
            'image_alt' => [
                'ar' => 'تجربة معازيم لطلب الهدايا وتوصيلها',
                'en' => 'Maazim gift ordering and delivery experience',
            ],
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
            'modified_at' => '2026-07-25',
            'image' => 'images/projects/atlas/investments-2060-shareholder-services.webp',
            'image_alt' => [
                'ar' => 'تجربة استثمارات عشرين ستين لخدمات المساهمين',
                'en' => '2060 Investments shareholder services experience',
            ],
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
            'modified_at' => '2026-07-25',
            'image' => 'images/projects/atlas/bosalty-tourism-journeys.webp',
            'image_alt' => [
                'ar' => 'تجربة بوصلتي لاكتشاف الوجهات وتخطيط الرحلات',
                'en' => 'Bosalty destination discovery and trip planning experience',
            ],
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
            'modified_at' => '2026-07-25',
            'image' => 'images/projects/atlas/rafid-humanitarian-collaboration.webp',
            'image_alt' => [
                'ar' => 'منصة رافد 360 للتعاون بين المنظمات الإنسانية',
                'en' => 'Rafid 360 humanitarian organization collaboration workspace',
            ],
            'read_minutes' => ['ar' => 10, 'en' => 9],
            'topics' => ['governance', 'ai_strategy', 'leadership'],
        ],
    ];

    /**
     * @return list<Article>
     */
    public function all(): array
    {
        if ($this->articles !== null) {
            return $this->articles;
        }

        $articles = $this->storedArticles();

        if ($articles === null) {
            $articles = array_map($this->make(...), self::DEFINITIONS);
        }

        usort(
            $articles,
            fn (Article $first, Article $second): int => $second->publishedAt <=> $first->publishedAt,
        );

        return $this->articles = $articles;
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
            $body = app(ArticleBody::class);

            foreach (['title', 'summary', 'seo_title', 'seo_description', 'type'] as $field) {
                $translations[$field] = [
                    'ar' => Lang::get("article_rewrites.articles.{$definition['key']}.{$field}", [], 'ar'),
                    'en' => Lang::get("article_rewrites.articles.{$definition['key']}.{$field}", [], 'en'),
                ];
            }

            $content = [
                'ar' => $body->toDocument(
                    Lang::get("article_rewrites.articles.{$definition['key']}.content", [], 'ar'),
                ),
                'en' => $body->toDocument(
                    Lang::get("article_rewrites.articles.{$definition['key']}.content", [], 'en'),
                ),
            ];

            $legacyTranslations = [];

            foreach (['lead', 'sections', 'closing'] as $field) {
                $legacyTranslations[$field] = [
                    'ar' => Lang::has("articles.articles.{$definition['key']}.{$field}", 'ar')
                        ? Lang::get("articles.articles.{$definition['key']}.{$field}", [], 'ar')
                        : null,
                    'en' => Lang::has("articles.articles.{$definition['key']}.{$field}", 'en')
                        ? Lang::get("articles.articles.{$definition['key']}.{$field}", [], 'en')
                        : null,
                ];
            }

            return [
                'key' => $definition['key'],
                'slug' => $definition['slugs'],
                ...$translations,
                ...$legacyTranslations,
                'body' => $content,
                'published_at' => $definition['published_at'],
                'modified_at' => $definition['modified_at'],
                'image' => $definition['image'],
                'image_alt' => $definition['image_alt'],
                'image_caption' => ['ar' => '', 'en' => ''],
                'read_minutes' => [
                    'ar' => $body->readingMinutes($content['ar'], 'ar'),
                    'en' => $body->readingMinutes($content['en'], 'en'),
                ],
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
    public function localized(?string $locale = null, bool $includeBody = true): array
    {
        $locale ??= app()->getLocale();

        return array_map(
            fn (Article $article): array => $this->present($article, $locale, $includeBody),
            $this->all(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function featured(int $limit = 3, ?string $locale = null, bool $includeBody = true): array
    {
        $locale ??= app()->getLocale();
        $featured = array_values(array_filter(
            $this->all(),
            fn (Article $article): bool => $article->featured,
        ));

        return array_map(
            fn (Article $article): array => $this->present($article, $locale, $includeBody),
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
    public function related(
        Article $current,
        int $limit = 3,
        ?string $locale = null,
        bool $includeBody = true,
    ): array {
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
            fn (Article $article): array => $this->present($article, $locale, $includeBody),
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
     *     image_alt: array{ar: string, en: string},
     *     read_minutes: array{ar: int, en: int},
     *     topics: list<string>,
     *     featured?: bool,
     *     source_url?: string
     * }  $definition
     */
    private function make(array $definition): Article
    {
        $body = app(ArticleBody::class);
        $content = [
            'ar' => Lang::get("article_rewrites.articles.{$definition['key']}.content", [], 'ar'),
            'en' => Lang::get("article_rewrites.articles.{$definition['key']}.content", [], 'en'),
        ];

        return new Article(
            key: $definition['key'],
            slugs: $definition['slugs'],
            publishedAt: $definition['published_at'],
            modifiedAt: $definition['modified_at'],
            image: $definition['image'],
            readMinutes: [
                'ar' => $body->readingMinutes($content['ar'], 'ar'),
                'en' => $body->readingMinutes($content['en'], 'en'),
            ],
            topicKeys: $definition['topics'],
            featured: $definition['featured'] ?? false,
            sourceUrl: $definition['source_url'] ?? null,
            translations: [
                'body' => $content,
                'image_alt' => $definition['image_alt'],
                'image_caption' => ['ar' => '', 'en' => ''],
            ],
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
            ->with('media')
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (ArticleRecord $record): bool => $this->publicationValidator->isPubliclyEligible($record))
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
                    'body' => [
                        'ar' => $record->getTranslation('body', 'ar', false),
                        'en' => $record->getTranslation('body', 'en', false),
                    ],
                    'image_alt' => $record->getTranslations('image_alt'),
                    'image_caption' => $record->getTranslations('image_caption'),
                ],
                record: $record,
            ))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Article $article, string $locale, bool $includeBody = true): array
    {
        $localized = $article->localized($locale, $includeBody);

        return [
            ...$localized,
            'url' => $this->url($article, $locale),
            'image_url' => $localized['image_media']['src'],
        ];
    }
}
