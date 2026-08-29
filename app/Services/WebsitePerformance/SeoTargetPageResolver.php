<?php

namespace App\Services\WebsitePerformance;

use App\Models\Article;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class SeoTargetPageResolver
{
    /** @var array<string, list<string>> */
    private const QUERY_GROUP_ASSIGNMENTS = [
        'saudi_ai_digital_transformation_advisory' => ['services'],
        'ai_governance' => ['ai_governance'],
        'first_ai_use_case' => ['first_ai_use_case'],
        'ai_adoption_roadmap' => ['ai_adoption_roadmap'],
        'data_knowledge_systems' => ['data_readiness', 'data_governance_before_ai'],
        'digital_transformation_workflow_automation' => ['transformation_roadmap', 'workflow_audit'],
    ];

    /** @var array<string, string> */
    private const ARTICLE_KEYS = [
        'ai_governance' => 'ai-governance',
        'first_ai_use_case' => 'first-ai-use-case',
        'ai_adoption_roadmap' => 'ai-adoption-roadmap-saudi-companies-2026',
        'data_readiness' => 'data-readiness',
        'data_governance_before_ai' => 'data-governance-before-ai',
        'transformation_roadmap' => 'digital-transformation-roadmap-process-to-impact',
        'workflow_audit' => 'workflow-audit-what-should-be-automated',
    ];

    /** @var array<string, array{ar: string, en: string}> */
    private const FALLBACK_SLUGS = [
        'ai_governance' => [
            'ar' => 'نموذج-تشغيلي-لحوكمة-الذكاء-الاصطناعي',
            'en' => 'a-practical-operating-model-for-ai-governance',
        ],
        'first_ai_use_case' => [
            'ar' => 'اختيار-أول-حالة-استخدام-للذكاء-الاصطناعي',
            'en' => 'choosing-your-first-measurable-ai-use-case',
        ],
        'ai_adoption_roadmap' => [
            'ar' => 'خارطة-طريق-تبني-الذكاء-الاصطناعي-للشركات-السعودية-2026',
            'en' => 'ai-adoption-roadmap-saudi-companies-2026',
        ],
        'data_readiness' => [
            'ar' => 'جاهزية-البيانات-قبل-الذكاء-الاصطناعي',
            'en' => 'data-readiness-before-ai',
        ],
        'data_governance_before_ai' => [
            'ar' => 'حوكمة-البيانات-قبل-الذكاء-الاصطناعي',
            'en' => 'data-governance-before-ai',
        ],
        'transformation_roadmap' => [
            'ar' => 'خارطة-التحول-الرقمي-من-تشخيص-العملية-إلى-الأثر',
            'en' => 'digital-transformation-roadmap-process-to-impact',
        ],
        'workflow_audit' => [
            'ar' => 'تدقيق-سير-العمل-ما-الذي-ينبغي-أتمتته',
            'en' => 'workflow-audit-what-should-be-automated',
        ],
    ];

    /** @var array<string, string>|null */
    private ?array $pathKeys = null;

    /** @return array<string, list<string>> */
    public static function assignments(): array
    {
        return self::QUERY_GROUP_ASSIGNMENTS;
    }

    /** @return list<string> */
    public static function queryGroupKeys(): array
    {
        return array_keys(self::QUERY_GROUP_ASSIGNMENTS);
    }

    /** @return list<string> */
    public static function pageKeys(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::QUERY_GROUP_ASSIGNMENTS))));
    }

    public function pageKey(string $page): ?string
    {
        $path = parse_url($page, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        $decodedPath = '/'.trim(rawurldecode($path), '/');

        return $this->pathKeys()[$decodedPath] ?? null;
    }

    /** @return array<string, string> */
    private function pathKeys(): array
    {
        if ($this->pathKeys !== null) {
            return $this->pathKeys;
        }

        $paths = [
            '/services' => 'services',
            '/en/services' => 'services',
        ];
        $slugs = $this->currentArticleSlugs();

        foreach ($slugs as $pageKey => $localizedSlugs) {
            foreach (['ar', 'en'] as $locale) {
                $slug = $localizedSlugs[$locale] ?? null;

                if (! is_string($slug) || trim($slug) === '' || str_contains($slug, '/')) {
                    continue;
                }

                $prefix = $locale === 'en' ? '/en' : '';
                $paths["{$prefix}/writing/{$slug}"] = $pageKey;
            }
        }

        return $this->pathKeys = $paths;
    }

    /** @return array<string, array{ar: string, en: string}> */
    private function currentArticleSlugs(): array
    {
        try {
            if (! Schema::hasTable('articles')) {
                return self::FALLBACK_SLUGS;
            }

            $articles = Article::query()
                ->whereIn('key', array_values(self::ARTICLE_KEYS))
                ->get(['key', 'slug']);

            if ($articles->isEmpty()) {
                return self::FALLBACK_SLUGS;
            }

            $pageKeys = array_flip(self::ARTICLE_KEYS);
            $slugs = [];

            foreach ($articles as $article) {
                $pageKey = $pageKeys[$article->key] ?? null;
                $translations = $article->getTranslations('slug');

                if ($pageKey === null
                    || ! is_string($translations['ar'] ?? null)
                    || ! is_string($translations['en'] ?? null)) {
                    continue;
                }

                $slugs[$pageKey] = [
                    'ar' => $translations['ar'],
                    'en' => $translations['en'],
                ];
            }

            return $slugs;
        } catch (Throwable) {
            return self::FALLBACK_SLUGS;
        }
    }
}
