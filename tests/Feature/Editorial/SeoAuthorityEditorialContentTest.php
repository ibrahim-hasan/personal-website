<?php

namespace Tests\Feature\Editorial;

use App\Actions\Editorial\ArticlePublicationValidator;
use App\Models\Article;
use App\Support\Editorial\ArticleBody;
use App\Support\Editorial\SeoAuthorityArticlePayload;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\SeoAuthorityEditorialContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoAuthorityEditorialContentTest extends TestCase
{
    use RefreshDatabase;

    private const array UPDATED_ARTICLES = [
        'ai-governance' => '2026-05-08',
        'first-ai-use-case' => '2026-06-04',
        'data-readiness' => '2026-06-20',
    ];

    private const array NEW_ARTICLES = [
        'ai-adoption-roadmap-saudi-companies-2026' => ['2026-08-29', true],
        'ai-use-case-register-governance-template' => ['2026-10-14', true],
        'data-governance-before-ai' => ['2026-10-21', true],
        'digital-transformation-roadmap-process-to-impact' => ['2026-10-28', true],
        'workflow-audit-what-should-be-automated' => ['2026-11-04', true],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-29 12:00:00');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_the_bilingual_payload_is_frozen_complete_and_substantial(): void
    {
        $payload = app(SeoAuthorityArticlePayload::class);
        $body = app(ArticleBody::class);
        $records = $payload->records();

        $this->assertSame(
            '3ba8030546c4f830f47bc7513a63cf7fedcf3b31caf6c6b3a3423bda974db4c5',
            $payload->fingerprint(),
        );
        $this->assertSame([
            ...array_keys(self::UPDATED_ARTICLES),
            ...array_keys(self::NEW_ARTICLES),
        ], array_column($records, 'key'));

        foreach ($records as $record) {
            foreach (['ar', 'en'] as $locale) {
                $document = $record['body'][$locale];
                $html = $body->present($document)['html'];

                $this->assertTrue($body->isValidDocument($document), "{$record['key']} {$locale} is not valid rich content.");
                $this->assertGreaterThanOrEqual(400, $body->wordCount($document), "{$record['key']} {$locale} is too short.");
                $this->assertStringContainsString('<h2', $html);
                $this->assertStringContainsString('<table>', $html);
                $this->assertStringContainsString('<a ', $html);
                $this->assertStringContainsString('href="'.($locale === 'en' ? '/en' : '').'/services#', $html);
                $this->assertStringContainsString('href="'.($locale === 'en' ? '/en' : '').'/work#', $html);
                $this->assertStringContainsString('href="'.($locale === 'en' ? '/en' : '').'/writing/', $html);
                $this->assertLessThanOrEqual(60, mb_strlen($record['seo_title'][$locale]));
                $this->assertLessThanOrEqual(155, mb_strlen($record['seo_description'][$locale]));
            }
        }
    }

    public function test_the_forward_release_preserves_existing_publication_dates_and_is_idempotent(): void
    {
        $this->seed(ArticleSeeder::class);
        $originalDates = [
            'ai-governance' => '2024-03-01',
            'first-ai-use-case' => '2024-04-02',
            'data-readiness' => '2024-05-03',
        ];

        foreach ($originalDates as $key => $publishedAt) {
            Article::query()->where('key', $key)->update(['published_at' => $publishedAt]);
        }

        $migration = require database_path('migrations/2026_08_28_211715_publish_seo_authority_editorial_content.php');
        $migration->up();

        $this->assertDatabaseCount('articles', 15);

        foreach ($originalDates as $key => $publishedAt) {
            $article = Article::query()->where('key', $key)->firstOrFail();

            $this->assertSame($publishedAt, $article->published_at->toDateString());
            $this->assertSame('2026-08-29', $article->modified_at->toDateString());
        }

        $revisions = Article::query()
            ->whereIn('key', array_keys(self::UPDATED_ARTICLES))
            ->pluck('editorial_revision', 'key');

        $migration->up();

        $this->assertSame(
            $revisions->all(),
            Article::query()
                ->whereIn('key', array_keys(self::UPDATED_ARTICLES))
                ->pluck('editorial_revision', 'key')
                ->all(),
        );
        $this->assertDatabaseCount('articles', 15);
    }

    public function test_the_fresh_install_seed_path_creates_managed_heroes_and_four_scheduled_resources(): void
    {
        $this->seed([
            ArticleSeeder::class,
            SeoAuthorityEditorialContentSeeder::class,
        ]);

        $this->assertDatabaseCount('articles', 15);

        foreach (self::NEW_ARTICLES as $key => [$publishedAt, $isPublished]) {
            $article = Article::query()->where('key', $key)->firstOrFail();

            $this->assertSame($publishedAt, $article->published_at->toDateString());
            $this->assertSame($isPublished, $article->is_published);
            $this->assertNotEmpty($article->getTranslation('title', 'ar', false));
            $this->assertNotEmpty($article->getTranslation('title', 'en', false));
            $this->assertNotEmpty($article->getTranslation('body', 'ar', false));
            $this->assertNotEmpty($article->getTranslation('body', 'en', false));
            $this->assertTrue($article->hasMedia(Article::IMAGE_COLLECTION));
            $this->assertSame($key.'.png', $article->getFirstMedia(Article::IMAGE_COLLECTION)?->file_name);
        }

        $pillar = Article::query()
            ->where('key', 'ai-adoption-roadmap-saudi-companies-2026')
            ->firstOrFail();

        $this->assertTrue(app(ArticlePublicationValidator::class)->isPubliclyEligible($pillar));
    }

    public function test_the_forward_media_release_imports_managed_heroes_before_enabling_scheduled_resources(): void
    {
        $this->seed(ArticleSeeder::class);

        $editorialMigration = require database_path('migrations/2026_08_28_211715_publish_seo_authority_editorial_content.php');
        $editorialMigration->up();

        foreach (array_keys(self::NEW_ARTICLES) as $key) {
            $this->assertFalse(
                Article::query()->where('key', $key)->firstOrFail()->hasMedia(Article::IMAGE_COLLECTION),
            );
        }

        $mediaMigration = require database_path('migrations/2026_08_28_222129_publish_scheduled_seo_authority_resources_with_managed_media.php');
        $mediaMigration->up();

        foreach (self::NEW_ARTICLES as $key => [$publishedAt]) {
            $article = Article::query()->where('key', $key)->firstOrFail();

            $this->assertTrue($article->is_published);
            $this->assertSame($publishedAt, $article->published_at->toDateString());
            $this->assertTrue($article->hasMedia(Article::IMAGE_COLLECTION));
        }
    }

    public function test_the_forward_media_release_does_not_overwrite_an_editor_modified_resource(): void
    {
        $this->seed(ArticleSeeder::class);

        $editorialMigration = require database_path('migrations/2026_08_28_211715_publish_seo_authority_editorial_content.php');
        $editorialMigration->up();

        $article = Article::query()
            ->where('key', 'ai-use-case-register-governance-template')
            ->firstOrFail();
        $article->setTranslation('title', 'en', 'Editor-controlled governance register');
        $article->forceFill([
            'editorial_revision' => 2,
            'is_published' => false,
        ])->save();

        $mediaMigration = require database_path('migrations/2026_08_28_222129_publish_scheduled_seo_authority_resources_with_managed_media.php');
        $mediaMigration->up();

        $article->refresh();

        $this->assertSame('Editor-controlled governance register', $article->getTranslation('title', 'en', false));
        $this->assertSame(2, $article->editorial_revision);
        $this->assertFalse($article->is_published);
        $this->assertFalse($article->hasMedia(Article::IMAGE_COLLECTION));
    }

    public function test_the_forward_media_release_is_irreversible(): void
    {
        $migration = require database_path('migrations/2026_08_28_222129_publish_scheduled_seo_authority_resources_with_managed_media.php');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('intentionally irreversible');

        $migration->down();
    }

    public function test_only_the_pillar_is_public_in_both_locales_and_links_render_in_initial_html(): void
    {
        $this->seed([
            ArticleSeeder::class,
            SeoAuthorityEditorialContentSeeder::class,
        ]);

        $this->get('/writing/'.rawurlencode('خارطة-طريق-تبني-الذكاء-الاصطناعي-للشركات-السعودية-2026'))
            ->assertOk()
            ->assertSee('خارطة طريق تبنّي الذكاء الاصطناعي للشركات السعودية في 2026')
            ->assertSee('href="/services#service-ai-adoption"', false)
            ->assertSee('href="/work#project-digi-pedia"', false)
            ->assertSee('https://www.cst.gov.sa/knowledge-center/reports/ai-adoption-guide-for-tech-companies', false);

        $this->get('/en/writing/ai-adoption-roadmap-saudi-companies-2026')
            ->assertOk()
            ->assertSee('AI Adoption Roadmap for Saudi Companies in 2026')
            ->assertSee('href="/en/services#service-ai-adoption"', false)
            ->assertSee('href="/en/work#project-digi-pedia"', false)
            ->assertSee('https://www.cst.gov.sa/en/knowledge-center/reports/ai-adoption-guide-for-tech-companies', false);

        foreach (array_keys(self::NEW_ARTICLES) as $key) {
            if ($key === 'ai-adoption-roadmap-saudi-companies-2026') {
                continue;
            }

            $article = Article::query()->where('key', $key)->firstOrFail();

            $this->get('/writing/'.rawurlencode($article->getTranslation('slug', 'ar', false)))->assertNotFound();
            $this->get('/en/writing/'.$article->getTranslation('slug', 'en', false))->assertNotFound();
        }

        $this->travelTo(Carbon::parse('2026-10-14 12:00:00'));

        $scheduledArticle = Article::query()
            ->where('key', 'ai-use-case-register-governance-template')
            ->firstOrFail();

        $this->assertTrue($scheduledArticle->is_published);
        $this->assertFalse($scheduledArticle->published_at->isFuture());
        $this->assertSame([], app(ArticlePublicationValidator::class)->violations($scheduledArticle));

        $this->get('/en/writing/ai-use-case-register-governance-template')
            ->assertOk()
            ->assertSee('AI Use-Case Register and Governance Template');
    }

    public function test_upgraded_articles_render_sources_tables_and_contextual_links_in_initial_html(): void
    {
        $this->seed([
            ArticleSeeder::class,
            SeoAuthorityEditorialContentSeeder::class,
        ]);

        $this->get('/en/writing/a-practical-operating-model-for-ai-governance')
            ->assertOk()
            ->assertSee('Updated 29 August 2026.')
            ->assertSee('<table>', false)
            ->assertSee('https://nca.gov.sa/en/news/2354/', false)
            ->assertSee('href="/en/services#service-ai-adoption"', false)
            ->assertSee('href="/en/writing/data-readiness-before-ai"', false);

        $this->get('/writing/'.rawurlencode('اختيار-أول-حالة-استخدام-للذكاء-الاصطناعي'))
            ->assertOk()
            ->assertSee('بطاقة القيمة مقابل الجدوى')
            ->assertSee('<table>', false)
            ->assertSee('href="/services#service-ai-adoption"', false);

        $this->get('/en/writing/data-readiness-before-ai')
            ->assertOk()
            ->assertSee('Structured data and knowledge systems need different tests')
            ->assertSee('<table>', false)
            ->assertSee('href="/en/services#service-data-governance"', false);
    }
}
