<?php

namespace Tests\Feature\Editorial;

use App\Models\Article;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ],
        ]);

        $article = app(ArticleCatalog::class)->findByKey($stored->key);

        $this->assertNotNull($article);
        $this->assertSame(
            ['الذكاء الاصطناعي', 'استراتيجية المنتج', 'البرمجيات كخدمة', 'الميزة التنافسية'],
            $article->localized('ar')['topics'],
        );
        $this->assertSame(
            ['Artificial intelligence', 'Product strategy', 'SaaS', 'Competitive advantage'],
            $article->localized('en')['topics'],
        );
    }

    public function test_an_existing_database_catalog_does_not_fall_back_when_every_article_is_a_draft(): void
    {
        Article::factory()->create(['is_published' => false]);

        $this->assertSame([], app(ArticleCatalog::class)->all());
    }

    public function test_the_idempotent_import_preserves_all_stable_catalog_keys(): void
    {
        $this->seed(ArticleSeeder::class);
        $firstKeys = Article::query()->orderBy('key')->pluck('key')->all();
        $edited = Article::query()->where('key', 'ai-value')->firstOrFail();
        $edited->update(['title' => ['ar' => 'تحرير محفوظ', 'en' => 'Preserved edit']]);

        $this->seed(ArticleSeeder::class);

        $this->assertCount(9, $firstKeys);
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
