<?php

namespace Tests\Feature\Editorial;

use App\Models\Article;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ArticleCatalogDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_database_articles_are_the_canonical_catalog_source(): void
    {
        $stored = Article::factory()->create([
            'key' => 'database-first',
            'slug' => ['ar' => 'مقال-من-قاعدة-البيانات', 'en' => 'database-backed-article'],
            'title' => ['ar' => 'عنوان من قاعدة البيانات', 'en' => 'A database-backed title'],
            'published_at' => today()->subDay(),
        ]);

        $catalog = app(ArticleCatalog::class);

        $this->assertCount(1, $catalog->all());
        $this->assertSame($stored->key, $catalog->all()[0]->key);
        $this->assertSame('A database-backed title', $catalog->all()[0]->localized('en')['title']);
        $this->assertSame('مقال-من-قاعدة-البيانات', $catalog->all()[0]->slug('ar'));
    }

    public function test_editorial_topic_keys_are_presented_with_bilingual_labels(): void
    {
        $stored = Article::factory()->create([
            'key' => 'ai-product-moat',
            'topic_keys' => [
                'artificial-intelligence',
                'product-strategy',
                'saas',
                'competitive-advantage',
                'ai-products',
                'knowledge-management',
                'rag',
            ],
        ]);

        $article = app(ArticleCatalog::class)->findByKey($stored->key);

        $this->assertNotNull($article);
        $this->assertSame(
            [
                'تبنّي الذكاء الاصطناعي وحوكمته',
                'استراتيجية المنتج والقياس',
                'البيانات وأنظمة المعرفة',
            ],
            $article->localized('ar')['topics'],
        );
        $this->assertSame(
            [
                'AI adoption & governance',
                'Product strategy & measurement',
                'Data & knowledge systems',
            ],
            $article->localized('en')['topics'],
        );
    }

    public function test_related_articles_rank_shared_normalized_topic_clusters_before_newer_unrelated_articles(): void
    {
        $current = Article::factory()->create([
            'key' => 'related-current-cluster',
            'slug' => ['ar' => 'المقال-الحالي', 'en' => 'current-cluster-article'],
            'topic_keys' => ['ai_strategy'],
            'published_at' => Article::publicationToday()->subDays(2),
        ]);
        $sharedCluster = Article::factory()->create([
            'key' => 'related-shared-cluster',
            'slug' => ['ar' => 'مقال-الحوكمة', 'en' => 'shared-cluster-article'],
            'topic_keys' => ['governance'],
            'published_at' => Article::publicationToday()->subDays(4),
        ]);
        Article::factory()->create([
            'key' => 'related-newer-unrelated',
            'slug' => ['ar' => 'مقال-بيانات', 'en' => 'newer-unrelated-article'],
            'topic_keys' => ['data'],
            'published_at' => Article::publicationToday(),
        ]);

        $catalog = app(ArticleCatalog::class);
        $resolvedCurrent = $catalog->findByKey($current->key);

        $this->assertNotNull($resolvedCurrent);
        $this->assertSame(
            $sharedCluster->key,
            $catalog->related($resolvedCurrent, limit: 2, locale: 'en', includeBody: false)[0]['key'],
        );
    }

    public function test_an_existing_database_catalog_does_not_fall_back_when_every_article_is_a_draft(): void
    {
        Article::factory()->create(['is_published' => false]);

        $this->assertSame([], app(ArticleCatalog::class)->all());
    }

    public function test_scheduled_articles_follow_the_saudi_publication_date_across_a_cached_catalog(): void
    {
        $this->travelTo(Carbon::parse('2026-08-28 20:59:00', 'UTC'));
        $article = Article::factory()->create([
            'slug' => ['ar' => 'إصدار-مجدول', 'en' => 'scheduled-release'],
            'published_at' => '2026-08-29',
            'is_published' => true,
        ]);

        $this->get('/en/writing/'.$article->getTranslation('slug', 'en', false))
            ->assertNotFound();

        $this->travelTo(Carbon::parse('2026-08-28 21:01:00', 'UTC'));

        $this->get('/en/writing/'.$article->getTranslation('slug', 'en', false))
            ->assertOk();
    }

    public function test_the_idempotent_import_preserves_all_stable_catalog_keys(): void
    {
        $this->seed(ArticleSeeder::class);
        $firstKeys = Article::query()->orderBy('key')->pluck('key')->all();
        $edited = Article::query()->where('key', 'ai-value')->firstOrFail();
        $edited->update(['title' => ['ar' => 'تحرير محفوظ', 'en' => 'Preserved edit']]);

        $this->seed(ArticleSeeder::class);

        $this->assertCount(10, $firstKeys);
        $this->assertSame($firstKeys, Article::query()->orderBy('key')->pluck('key')->all());
        $this->assertContains('ai-value', $firstKeys);
        $this->assertSame(
            'Preserved edit',
            Article::query()->where('key', 'ai-value')->firstOrFail()->getTranslation('title', 'en'),
        );
    }

    public function test_the_bootstrap_import_does_not_resurrect_a_deleted_article(): void
    {
        $this->seed(ArticleSeeder::class);
        Article::query()->where('key', 'ai-value')->firstOrFail()->delete();

        $this->seed(ArticleSeeder::class);

        $this->assertTrue(Article::withTrashed()->where('key', 'ai-value')->firstOrFail()->trashed());
        $this->assertNull(app(ArticleCatalog::class)->findByKey('ai-value'));
    }

    public function test_localized_article_slugs_are_synchronized_and_unique(): void
    {
        $article = Article::factory()->create([
            'slug' => ['ar' => 'مسار-فريد', 'en' => 'unique-path'],
        ]);

        $this->assertSame('مسار-فريد', $article->fresh()->getAttribute('slug_ar'));
        $this->assertSame('unique-path', $article->fresh()->getAttribute('slug_en'));

        $this->expectException(QueryException::class);

        Article::factory()->create([
            'slug' => ['ar' => 'مسار-آخر', 'en' => 'unique-path'],
        ]);
    }
}
