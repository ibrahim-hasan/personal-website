<?php

namespace Tests\Feature\Editorial;

use App\Actions\Editorial\PublishEditorialArticle;
use App\Models\Article;
use App\Support\Editorial\Article as EditorialArticle;
use App\Support\Editorial\ArticleBody;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArticleRichContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_database_uses_one_translatable_body_column(): void
    {
        $this->assertTrue(Schema::hasColumn('articles', 'body'));
        $this->assertFalse(Schema::hasColumn('articles', 'body_ar'));
        $this->assertFalse(Schema::hasColumn('articles', 'body_en'));
    }

    public function test_filament_locale_adapters_write_one_translatable_body_and_recalculate_reading_time(): void
    {
        $body = app(ArticleBody::class);
        $article = Article::factory()->create(['is_published' => false]);
        $arabic = $body->toDocument(
            '<p>'.str_repeat('قرار واضح يدعم العمل اليومي. ', 60).'</p><h2>خطوة عملية</h2><p>'.str_repeat('تفصيل مفيد. ', 20).'</p>',
        );
        $english = $body->toDocument(
            '<p>'.str_repeat('A clear decision supports the daily workflow. ', 70).'</p><h2>A practical step</h2><p>'.str_repeat('Useful detail. ', 30).'</p>',
        );

        $article->update([
            Article::bodyAttribute('ar') => $arabic,
            Article::bodyAttribute('en') => $english,
        ]);
        $article->refresh();

        $this->assertEquals($arabic, $article->getTranslation('body', 'ar', false));
        $this->assertEquals($english, $article->getTranslation('body', 'en', false));
        $this->assertSame($body->readingMinutes($arabic, 'ar'), $article->getTranslation('read_minutes', 'ar', false));
        $this->assertSame($body->readingMinutes($english, 'en'), $article->getTranslation('read_minutes', 'en', false));
    }

    public function test_rich_content_renders_safely_with_stable_headings_and_optimized_owned_images(): void
    {
        Storage::fake('public');
        $body = app(ArticleBody::class);
        $article = Article::factory()->create();
        $media = $article
            ->addMedia(UploadedFile::fake()->image('diagram.jpg', 1800, 1200))
            ->toMediaCollection(Article::BODY_EN_COLLECTION);
        $document = $body->toDocument(
            '<p>Opening context for the reader.</p>'
            .'<h2>A descriptive heading</h2>'
            .'<p>Useful detail with <a href="https://example.com" target="_blank">a source</a>.</p>'
            .'<blockquote><p>A concise callout.</p></blockquote>',
        );
        $document['content'][] = [
            'type' => 'image',
            'attrs' => [
                'id' => $media->uuid,
                'src' => $media->getUrl(),
                'alt' => 'A workflow diagram connecting the decision steps',
            ],
        ];
        $article->setTranslation('body', 'en', $document)->save();

        $presented = $body->presentForArticle($article->fresh(), 'en');

        $this->assertSame([[
            'id' => 'article-section-1',
            'label' => 'A descriptive heading',
        ]], $presented['headings']);
        $this->assertStringContainsString('id="article-section-1"', $presented['html']);
        $this->assertStringContainsString('loading="lazy"', $presented['html']);
        $this->assertStringContainsString('decoding="async"', $presented['html']);
        $this->assertStringContainsString('alt="A workflow diagram connecting the decision steps"', $presented['html']);
        $this->assertStringContainsString('rel="noopener noreferrer"', $presented['html']);
        $this->assertStringContainsString(Article::BODY_IMAGE_CONVERSION, $presented['html']);
    }

    public function test_public_rendering_removes_an_inline_image_that_is_not_owned_by_the_article_locale(): void
    {
        $body = app(ArticleBody::class);
        $article = Article::factory()->create();
        $document = $body->toDocument(
            '<p>Opening context for the reader.</p>'
            .'<h2>A descriptive heading</h2>'
            .'<p>'.str_repeat('Useful practical detail. ', 40).'</p>'
            .'<img src="https://tracker.invalid/pixel.png" alt="Remote tracking pixel">',
        );
        $article->setTranslation('body', 'en', $document)->save();

        $presented = $body->presentForArticle($article->fresh(), 'en');

        $this->assertStringNotContainsString('tracker.invalid', $presented['html']);
        $this->assertStringNotContainsString('Remote tracking pixel', $presented['html']);
    }

    public function test_optional_caption_and_html_fallbacks_do_not_leak_translation_keys_or_old_copy(): void
    {
        $article = new EditorialArticle(
            key: 'fallback-article',
            slugs: ['ar' => 'مقال-احتياطي', 'en' => 'fallback-article'],
            publishedAt: '2026-07-25',
            modifiedAt: '2026-07-25',
            image: 'images/fallback.webp',
            readMinutes: ['ar' => 1, 'en' => 1],
            topicKeys: ['products'],
            translations: [
                'title' => ['ar' => 'عنوان', 'en' => 'Title'],
                'body' => [
                    'ar' => '<p>محتوى حديث.</p><h2>فكرة</h2><p>تفصيل.</p>',
                    'en' => '<p>Current copy.</p><h2>An idea</h2><p>Useful detail.</p>',
                ],
            ],
        );

        $localized = $article->localized('en');

        $this->assertSame('', $localized['image_caption']);
        $this->assertIsString($localized['body']);
        $this->assertStringContainsString('Current copy.', $localized['body_html']);
    }

    public function test_publishing_rejects_an_inline_image_from_another_locale_collection(): void
    {
        Storage::fake('public');
        $body = app(ArticleBody::class);
        $article = Article::factory()->create(['is_published' => false, 'image' => null]);
        $article
            ->addMedia(UploadedFile::fake()->image('hero.jpg', 1600, 900))
            ->toMediaCollection(Article::IMAGE_COLLECTION);
        $foreignMedia = $article
            ->addMedia(UploadedFile::fake()->image('arabic-only.jpg', 1200, 800))
            ->toMediaCollection(Article::BODY_AR_COLLECTION);
        $english = $article->getTranslation('body', 'en', false);
        $english['content'][] = [
            'type' => 'image',
            'attrs' => [
                'id' => $foreignMedia->uuid,
                'src' => $foreignMedia->getUrl(),
                'alt' => 'An image in the wrong locale collection',
            ],
        ];
        $article->setTranslation('body', 'en', $english)->save();

        try {
            app(PublishEditorialArticle::class)->handle($article->fresh());
            $this->fail('Publishing should reject cross-locale inline media.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'Every inline image must belong to this article and language',
                $exception->errors()['article'][0],
            );
        }

        $this->assertFalse($article->fresh()->is_published);
        $this->assertNotEmpty($body->images($english));
    }

    public function test_all_ten_rewrites_are_concise_scannable_and_metadata_safe(): void
    {
        $body = app(ArticleBody::class);

        foreach (['ar', 'en'] as $locale) {
            $articles = Lang::get('article_rewrites.articles', [], $locale);
            $this->assertCount(10, $articles);

            foreach ($articles as $key => $article) {
                $words = $body->wordCount($article['content']);
                $minimum = $locale === 'ar' ? 450 : 500;
                $maximum = $locale === 'ar' ? 750 : 800;
                $presented = $body->present($article['content']);

                $this->assertGreaterThanOrEqual($minimum, $words, "{$key} in {$locale} is too short.");
                $this->assertLessThanOrEqual($maximum, $words, "{$key} in {$locale} is too long.");
                $this->assertGreaterThanOrEqual(4, count($presented['headings']));
                $this->assertLessThanOrEqual(5, count($presented['headings']));
                $this->assertStringNotContainsString('<h1', $article['content']);
                $this->assertStringContainsString('<ul>', $article['content']);
                $this->assertLessThanOrEqual(70, mb_strlen($article['seo_title']));
                $this->assertLessThanOrEqual(170, mb_strlen($article['seo_description']));
            }
        }
    }

    public function test_the_rewrite_migration_updates_the_live_only_slug_without_changing_its_existing_key(): void
    {
        $article = Article::factory()->create([
            'key' => 'legacy-production-key',
            'slug' => [
                'ar' => 'حين-يصبح-الذكاء-الاصطناعي-متاحا-للجميع-كيف-تبني-منتجا-يصعب-تقليده',
                'en' => 'building-an-ai-product-that-is-hard-to-copy',
            ],
            'title' => ['ar' => 'عنوان قديم', 'en' => 'Old title'],
        ]);

        $migration = require database_path('migrations/2026_07_25_202321_rewrite_articles_for_readability.php');
        $migration->up();
        $article->refresh();

        $this->assertSame('legacy-production-key', $article->key);
        $this->assertSame(
            Lang::get('article_rewrites.articles.ai-product-moat.title', [], 'en'),
            $article->getTranslation('title', 'en', false),
        );
        $this->assertNotEmpty($article->getTranslation('body', 'en', false));
        $this->assertDatabaseCount('articles', 1);
    }

    public function test_the_rewrite_migration_updates_all_existing_articles_in_place_and_is_idempotent(): void
    {
        $records = ArticleCatalog::bootstrapRecords();
        $existingArticles = [];

        foreach ($records as $record) {
            $key = $record['key'] === 'ai-product-moat'
                ? 'legacy-production-key'
                : $record['key'];
            $article = Article::factory()->create([
                'key' => $key,
                'slug' => $record['slug'],
                'title' => ['ar' => 'عنوان قديم', 'en' => 'Old title'],
                'summary' => ['ar' => 'ملخص قديم', 'en' => 'Old summary'],
            ]);

            $existingArticles[$record['key']] = [
                'id' => $article->getKey(),
                'key' => $key,
                'revision' => $article->editorial_revision,
            ];
        }

        $migration = require database_path('migrations/2026_07_25_202321_rewrite_articles_for_readability.php');
        $migration->up();

        $this->assertDatabaseCount('articles', 10);

        foreach ($records as $record) {
            $existing = $existingArticles[$record['key']];
            $article = Article::query()->findOrFail($existing['id']);

            $this->assertSame($existing['key'], $article->key);
            $this->assertSame($existing['revision'] + 1, $article->editorial_revision);

            foreach (['ar', 'en'] as $locale) {
                $this->assertSame(
                    $record['title'][$locale],
                    $article->getTranslation('title', $locale, false),
                );
                $this->assertEquals(
                    $record['body'][$locale],
                    $article->getTranslation('body', $locale, false),
                );
                $this->assertSame(
                    $record['read_minutes'][$locale],
                    $article->getTranslation('read_minutes', $locale, false),
                );
            }
        }

        $revisions = Article::query()->pluck('editorial_revision', 'id');
        $migration->up();

        $this->assertDatabaseCount('articles', 10);
        $this->assertSame($revisions->all(), Article::query()->pluck('editorial_revision', 'id')->all());
    }

    public function test_the_rewrite_migration_rejects_conflicting_key_and_slug_identities(): void
    {
        Article::factory()->create([
            'key' => 'ai-product-moat',
            'slug' => [
                'ar' => 'مسار-عربي-مختلف',
                'en' => 'a-different-english-path',
            ],
        ]);
        Article::factory()->create([
            'key' => 'legacy-production-key',
            'slug' => [
                'ar' => 'حين-يصبح-الذكاء-الاصطناعي-متاحا-للجميع-كيف-تبني-منتجا-يصعب-تقليده',
                'en' => 'building-an-ai-product-that-is-hard-to-copy',
            ],
        ]);

        $migration = require database_path('migrations/2026_07_25_202321_rewrite_articles_for_readability.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Article identity collision for [ai-product-moat].');

        $migration->up();
    }

    public function test_the_rewrite_migration_reports_that_the_editorial_rewrite_is_irreversible(): void
    {
        $migration = require database_path('migrations/2026_07_25_202321_rewrite_articles_for_readability.php');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('intentionally irreversible');

        $migration->down();
    }

    public function test_the_article_seeder_recognizes_the_live_slug_when_the_production_key_differs(): void
    {
        $article = Article::factory()->create([
            'key' => 'legacy-production-key',
            'slug' => [
                'ar' => 'حين-يصبح-الذكاء-الاصطناعي-متاحا-للجميع-كيف-تبني-منتجا-يصعب-تقليده',
                'en' => 'building-an-ai-product-that-is-hard-to-copy',
            ],
        ]);

        $this->seed(ArticleSeeder::class);

        $this->assertDatabaseCount('articles', 10);
        $this->assertSame('legacy-production-key', $article->fresh()->key);
        $this->assertSame(
            1,
            Article::withTrashed()->where('slug_en', 'building-an-ai-product-that-is-hard-to-copy')->count(),
        );
    }
}
