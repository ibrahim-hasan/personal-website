<?php

namespace Tests\Feature\Seo;

use App\Models\Article;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\SeoServiceRelationshipSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class SeoAuthorityUpgradeMigrationsTest extends TestCase
{
    use RefreshDatabase;

    private const array SERVICE_ARTICLES = [
        'transformation' => [
            'digital-transformation-roadmap-process-to-impact',
            'transformation-before-software',
            'measure-digital-impact',
        ],
        'ai-adoption' => [
            'ai-use-case-register-governance-template',
            'ai-adoption-roadmap-saudi-companies-2026',
            'first-ai-use-case',
            'ai-value',
        ],
        'data-governance' => [
            'data-governance-before-ai',
            'data-readiness',
            'ai-governance',
        ],
        'systems' => [
            'workflow-audit-what-should-be-automated',
            'automation-assistant-agent',
            'ai-not-answer',
        ],
    ];

    private const array SERVICE_PROJECTS = [
        'transformation' => ['wafaa', 'bosalty'],
        'ai-adoption' => ['digi-pedia', 'maazim'],
        'data-governance' => ['rafid-360', 'digi-pedia'],
        'systems' => ['2060-investments', 'wafaa'],
    ];

    private const array ARTICLE_PROJECTS = [
        'transformation-before-software' => ['wafaa', 'bosalty'],
        'measure-digital-impact' => ['wafaa'],
        'first-ai-use-case' => ['digi-pedia', 'maazim'],
        'ai-value' => ['digi-pedia'],
        'data-readiness' => ['rafid-360', 'digi-pedia'],
        'ai-governance' => ['rafid-360'],
        'automation-assistant-agent' => ['2060-investments', 'wafaa'],
        'ai-not-answer' => ['2060-investments'],
        'ai-adoption-roadmap-saudi-companies-2026' => ['digi-pedia'],
        'ai-use-case-register-governance-template' => ['digi-pedia'],
        'data-governance-before-ai' => ['rafid-360'],
        'digital-transformation-roadmap-process-to-impact' => ['wafaa', 'bosalty'],
        'workflow-audit-what-should-be-automated' => ['2060-investments', 'wafaa'],
    ];

    private const array ARTICLE_ALIASES = [
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

    public function test_relationship_upgrade_normalizes_every_planned_order_and_restores_missing_rows(): void
    {
        $this->seed([ServiceSeeder::class, ProjectSeeder::class, ArticleSeeder::class]);
        $this->createReleaseArticles();
        $this->seed(SeoServiceRelationshipSeeder::class);

        DB::table('article_service')->update(['sort_order' => 90]);
        DB::table('project_service')->update(['sort_order' => 91]);
        DB::table('article_project')->update(['sort_order' => 92]);
        DB::table('article_service')->where('id', DB::table('article_service')->min('id'))->delete();
        DB::table('project_service')->where('id', DB::table('project_service')->min('id'))->delete();
        DB::table('article_project')->where('id', DB::table('article_project')->min('id'))->delete();

        $this->relationshipMigration()->up();

        foreach (self::SERVICE_ARTICLES as $serviceKey => $articleKeys) {
            foreach ($articleKeys as $sortOrder => $articleKey) {
                $this->assertDatabaseHas('article_service', [
                    'article_id' => Article::query()->where('key', $articleKey)->value('id'),
                    'service_id' => DB::table('services')->where('key', $serviceKey)->value('id'),
                    'sort_order' => $sortOrder,
                ]);
            }
        }

        foreach (self::SERVICE_PROJECTS as $serviceKey => $projectKeys) {
            foreach ($projectKeys as $sortOrder => $projectKey) {
                $this->assertDatabaseHas('project_service', [
                    'project_id' => DB::table('projects')->where('key', $projectKey)->value('id'),
                    'service_id' => DB::table('services')->where('key', $serviceKey)->value('id'),
                    'sort_order' => $sortOrder,
                ]);
            }
        }

        foreach (self::ARTICLE_PROJECTS as $articleKey => $projectKeys) {
            foreach ($projectKeys as $sortOrder => $projectKey) {
                $this->assertDatabaseHas('article_project', [
                    'article_id' => Article::query()->where('key', $articleKey)->value('id'),
                    'project_id' => DB::table('projects')->where('key', $projectKey)->value('id'),
                    'sort_order' => $sortOrder,
                ]);
            }
        }
    }

    public function test_slug_alias_upgrade_preserves_all_default_localized_paths_after_custom_slug_changes(): void
    {
        $this->seed(ArticleSeeder::class);

        foreach (array_keys(self::ARTICLE_ALIASES) as $index => $articleKey) {
            Article::query()->where('key', $articleKey)->firstOrFail()->update([
                'slug' => [
                    'ar' => "مسار-مخصص-{$index}",
                    'en' => "custom-path-{$index}",
                ],
            ]);
        }

        $migration = $this->slugAliasMigration();
        $migration->up();
        $migration->up();

        foreach (self::ARTICLE_ALIASES as $articleKey => $localizedAliases) {
            $articleId = Article::query()->where('key', $articleKey)->value('id');

            foreach ($localizedAliases as $locale => $slug) {
                $this->assertDatabaseHas('article_slug_redirects', [
                    'article_id' => $articleId,
                    'locale' => $locale,
                    'slug' => $slug,
                ]);
            }
        }

        $this->assertSame(count(self::ARTICLE_ALIASES) * 2, DB::table('article_slug_redirects')->count());
    }

    public function test_slug_alias_upgrade_rejects_a_default_path_used_as_another_articles_current_slug(): void
    {
        $this->seed(ArticleSeeder::class);
        $target = Article::query()->where('key', 'ai-governance')->firstOrFail();
        $target->update(['slug' => ['ar' => 'مسار-حوكمة-جديد', 'en' => 'new-governance-path']]);
        Article::factory()->create([
            'key' => 'conflicting-current-slug',
            'slug' => [
                'ar' => 'مسار-غير-متعارض',
                'en' => self::ARTICLE_ALIASES['ai-governance']['en'],
            ],
        ]);

        try {
            $this->slugAliasMigration()->up();
            $this->fail('Expected the migration to reject a current-slug collision.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('current slug of article [conflicting-current-slug]', $exception->getMessage());
        }

        $this->assertDatabaseMissing('article_slug_redirects', [
            'article_id' => $target->getKey(),
            'locale' => 'ar',
            'slug' => self::ARTICLE_ALIASES['ai-governance']['ar'],
        ]);
    }

    public function test_slug_alias_upgrade_rejects_a_default_path_owned_by_another_articles_history(): void
    {
        $this->seed(ArticleSeeder::class);
        $target = Article::query()->where('key', 'ai-governance')->firstOrFail();
        $target->update(['slug' => ['ar' => 'مسار-حوكمة-جديد', 'en' => 'new-governance-path']]);
        $otherArticle = Article::factory()->create(['key' => 'conflicting-slug-history']);
        DB::table('article_slug_redirects')->insert([
            'article_id' => $otherArticle->getKey(),
            'locale' => 'en',
            'slug' => self::ARTICLE_ALIASES['ai-governance']['en'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $this->slugAliasMigration()->up();
            $this->fail('Expected the migration to reject a historical-slug collision.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('another article owns that historical slug', $exception->getMessage());
        }

        $this->assertDatabaseMissing('article_slug_redirects', [
            'article_id' => $target->getKey(),
            'locale' => 'ar',
            'slug' => self::ARTICLE_ALIASES['ai-governance']['ar'],
        ]);
    }

    public function test_slug_alias_upgrade_preserves_aliases_for_restorable_soft_deleted_articles(): void
    {
        $this->seed(ArticleSeeder::class);
        $article = Article::query()->where('key', 'ai-governance')->firstOrFail();
        $article->update(['slug' => ['ar' => 'مسار-محذوف', 'en' => 'deleted-path']]);
        $article->delete();

        $this->slugAliasMigration()->up();

        $this->assertDatabaseHas('article_slug_redirects', [
            'article_id' => $article->getKey(),
            'locale' => 'en',
            'slug' => self::ARTICLE_ALIASES['ai-governance']['en'],
        ]);

        $this->get('/en/writing/'.self::ARTICLE_ALIASES['ai-governance']['en'])
            ->assertNotFound();

        $article->restore();

        $this->get('/en/writing/'.self::ARTICLE_ALIASES['ai-governance']['en'])
            ->assertStatus(301)
            ->assertRedirect(localized_route('writing.show', ['article' => 'deleted-path'], locale: 'en'));
    }

    public function test_schedule_upgrade_only_reschedules_unpublished_articles_on_the_exact_old_dates(): void
    {
        $schedule = [
            'ai-use-case-register-governance-template' => ['2026-09-05', '2026-10-14'],
            'data-governance-before-ai' => ['2026-09-12', '2026-10-21'],
            'digital-transformation-roadmap-process-to-impact' => ['2026-09-19', '2026-10-28'],
            'workflow-audit-what-should-be-automated' => ['2026-09-26', '2026-11-04'],
        ];

        $index = 0;
        foreach ($schedule as $key => $dates) {
            Article::factory()->create([
                'key' => $key,
                'slug' => ['ar' => "مسار-مجدول-{$index}", 'en' => "scheduled-path-{$index}"],
                'is_published' => false,
                'published_at' => $dates[0],
            ]);
            $index++;
        }

        $customized = Article::query()->where('key', 'workflow-audit-what-should-be-automated')->firstOrFail();
        $customized->update(['published_at' => '2026-12-01']);
        $migration = $this->scheduleMigration();
        $migration->up();

        foreach (array_slice($schedule, 0, 3, true) as $key => $dates) {
            $this->assertSame($dates[1], Article::query()->where('key', $key)->firstOrFail()->published_at->toDateString());
        }
        $this->assertSame('2026-12-01', $customized->fresh()->published_at->toDateString());

        $customized->update(['published_at' => $schedule['workflow-audit-what-should-be-automated'][0]]);
        $migration->up();
        $this->assertSame('2026-11-04', $customized->fresh()->published_at->toDateString());

        $customized->refresh()->update(['is_published' => true, 'published_at' => '2026-09-26']);
        $migration->up();
        $this->assertSame('2026-09-26', $customized->fresh()->published_at->toDateString());
    }

    public function test_data_upgrade_migrations_are_explicitly_irreversible(): void
    {
        foreach ([
            $this->scheduleMigration(),
            $this->relationshipMigration(),
            $this->slugAliasMigration(),
        ] as $migration) {
            try {
                $migration->down();
                $this->fail('Expected an irreversible data migration to throw.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('irreversible', $exception->getMessage());
            }
        }
    }

    private function createReleaseArticles(): void
    {
        $releaseArticleKeys = collect(self::SERVICE_ARTICLES)
            ->flatten()
            ->merge(array_keys(self::ARTICLE_PROJECTS))
            ->unique()
            ->values();
        $existingKeys = Article::query()->whereIn('key', $releaseArticleKeys)->pluck('key');

        $releaseArticleKeys
            ->diff($existingKeys)
            ->values()
            ->each(function (string $key, int $index): void {
                Article::factory()->create([
                    'key' => $key,
                    'slug' => [
                        'ar' => "مقال-سلطة-البحث-{$index}",
                        'en' => $key,
                    ],
                    'is_published' => false,
                ]);
            });
    }

    private function scheduleMigration(): Migration
    {
        return require database_path('migrations/2026_08_28_215713_reschedule_seo_authority_resources_for_phase_two.php');
    }

    private function relationshipMigration(): Migration
    {
        return require database_path('migrations/2026_08_28_222127_normalize_seo_authority_relationship_orders.php');
    }

    private function slugAliasMigration(): Migration
    {
        return require database_path('migrations/2026_08_28_222128_backfill_seo_authority_internal_link_aliases.php');
    }
}
