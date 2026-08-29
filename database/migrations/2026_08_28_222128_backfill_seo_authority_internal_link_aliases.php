<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('articles')
            || ! Schema::hasTable('article_slug_redirects')
            || ! Schema::hasColumns('articles', ['key', 'slug_ar', 'slug_en', 'deleted_at'])) {
            return;
        }

        $aliases = [
            'ai-governance' => [
                'ar' => 'نموذج-تشغيلي-لحوكمة-الذكاء-الاصطناعي',
                'en' => 'a-practical-operating-model-for-ai-governance',
            ],
            'first-ai-use-case' => [
                'ar' => 'اختيار-أول-حالة-استخدام-للذكاء-الاصطناعي',
                'en' => 'choosing-your-first-measurable-ai-use-case',
            ],
            'data-readiness' => [
                'ar' => 'جاهزية-البيانات-قبل-الذكاء-الاصطناعي',
                'en' => 'data-readiness-before-ai',
            ],
            'automation-assistant-agent' => [
                'ar' => 'الأتمتة-أم-المساعد-أم-الوكيل',
                'en' => 'automation-assistant-or-agent',
            ],
            'human-in-loop' => [
                'ar' => 'الإنسان-داخل-حلقة-القرار',
                'en' => 'where-human-judgment-belongs-in-ai-workflows',
            ],
            'ai-value' => [
                'ar' => 'من-تجربة-الذكاء-الاصطناعي-إلى-تحقيق-القيمة',
                'en' => 'from-ai-experiment-to-business-value',
            ],
            'measure-digital-impact' => [
                'ar' => 'قياس-أثر-المنتجات-والتحول-الرقمي',
                'en' => 'measuring-digital-product-and-transformation-impact',
            ],
            'ai-not-answer' => [
                'ar' => 'متى-لا-يكون-الذكاء-الاصطناعي-هو-الحل',
                'en' => 'when-ai-is-not-the-answer',
            ],
            'transformation-before-software' => [
                'ar' => 'لماذا-يفشل-التحول-قبل-بناء-البرمجيات',
                'en' => 'why-transformation-fails-before-software',
            ],
        ];
        $slugColumns = ['ar' => 'slug_ar', 'en' => 'slug_en'];
        $now = now();

        DB::transaction(function () use ($aliases, $now, $slugColumns): void {
            $articles = DB::table('articles')
                ->whereIn('key', array_keys($aliases))
                ->get(['id', 'key', 'slug_ar', 'slug_en'])
                ->keyBy('key');

            foreach ($aliases as $articleKey => $localizedAliases) {
                $article = $articles->get($articleKey);

                if ($article === null) {
                    continue;
                }

                foreach ($localizedAliases as $locale => $alias) {
                    $slugColumn = $slugColumns[$locale];
                    $currentSlugOwner = DB::table('articles')
                        ->where($slugColumn, $alias)
                        ->where('id', '!=', $article->id)
                        ->first(['id', 'key']);

                    if ($currentSlugOwner !== null) {
                        throw new RuntimeException("Cannot assign the {$locale} slug alias [{$alias}] to article [{$articleKey}]; it is the current slug of article [{$currentSlugOwner->key}].");
                    }

                    $redirectOwner = DB::table('article_slug_redirects')
                        ->where('locale', $locale)
                        ->where('slug', $alias)
                        ->first(['article_id']);

                    if ($redirectOwner !== null && (int) $redirectOwner->article_id !== (int) $article->id) {
                        throw new RuntimeException("Cannot assign the {$locale} slug alias [{$alias}] to article [{$articleKey}]; another article owns that historical slug.");
                    }

                    if ((string) $article->{$slugColumn} === $alias || $redirectOwner !== null) {
                        continue;
                    }

                    DB::table('article_slug_redirects')->insert([
                        'article_id' => $article->id,
                        'locale' => $locale,
                        'slug' => $alias,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new LogicException('The SEO authority article slug alias migration is intentionally irreversible. Use a forward migration to retire an alias safely.');
    }
};
