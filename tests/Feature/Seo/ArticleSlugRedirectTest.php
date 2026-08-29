<?php

namespace Tests\Feature\Seo;

use App\Actions\Editorial\UpdateEditorialArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleSlugRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://ibrahimhasan.net']);
    }

    public function test_retired_localized_slugs_redirect_permanently_and_only_current_slugs_are_canonical(): void
    {
        $article = $this->draftWithSlugs(
            'redirected-article',
            ['ar' => 'المسار-العربي-القديم', 'en' => 'the-old-english-path'],
        );
        $article = $this->renameDraft($article, [
            'ar' => 'المسار-العربي-الحالي',
            'en' => 'the-current-english-path',
        ]);
        $article->update(['is_published' => true, 'published_at' => today()]);

        $oldArabicPath = $this->articlePath('المسار-العربي-القديم', 'ar');
        $oldEnglishPath = $this->articlePath('the-old-english-path', 'en');
        $currentArabicUrl = $this->articleUrl($article, 'ar');
        $currentEnglishUrl = $this->articleUrl($article, 'en');
        $canonicalArabicUrl = $this->canonicalArticleUrl($article, 'ar');
        $canonicalEnglishUrl = $this->canonicalArticleUrl($article, 'en');

        $this->get($oldArabicPath.'?ref=archive&utm_source=google')
            ->assertStatus(301)
            ->assertRedirect($currentArabicUrl.'?ref=archive&utm_source=google');

        $this->get($oldEnglishPath.'?utm_source=google')
            ->assertStatus(301)
            ->assertRedirect($currentEnglishUrl.'?utm_source=google');

        $this->get(parse_url($currentArabicUrl, PHP_URL_PATH))
            ->assertOk()
            ->assertSee('rel="canonical" href="'.$canonicalArabicUrl.'"', false);

        $this->get(parse_url($currentEnglishUrl, PHP_URL_PATH))
            ->assertOk()
            ->assertSee('rel="canonical" href="'.$canonicalEnglishUrl.'"', false);

        $this->get($this->articlePath('the-old-english-path', 'ar'))->assertNotFound();
        $this->get($this->articlePath('المسار-العربي-القديم', 'en'))->assertNotFound();

        $sitemap = (string) $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString($canonicalArabicUrl, $sitemap);
        $this->assertStringContainsString($canonicalEnglishUrl, $sitemap);
        $this->assertStringNotContainsString('the-old-english-path', $sitemap);
        $this->assertStringNotContainsString('المسار-العربي-القديم', $sitemap);
    }

    public function test_retired_slugs_for_non_public_articles_remain_not_found(): void
    {
        $draft = $this->renameDraft(
            $this->draftWithSlugs(
                'draft-redirect-target',
                ['ar' => 'مسار-مسودة-قديم', 'en' => 'old-draft-path'],
            ),
            ['ar' => 'مسار-مسودة-حالي', 'en' => 'current-draft-path'],
        );
        $future = $this->renameDraft(
            $this->draftWithSlugs(
                'future-redirect-target',
                ['ar' => 'مسار-مستقبلي-قديم', 'en' => 'old-future-path'],
            ),
            ['ar' => 'مسار-مستقبلي-حالي', 'en' => 'current-future-path'],
        );
        $future->update([
            'is_published' => true,
            'published_at' => Article::publicationToday()->addDay(),
        ]);
        $deleted = $this->renameDraft(
            $this->draftWithSlugs(
                'deleted-redirect-target',
                ['ar' => 'مسار-محذوف-قديم', 'en' => 'old-deleted-path'],
            ),
            ['ar' => 'مسار-محذوف-حالي', 'en' => 'current-deleted-path'],
        );
        $deleted->update(['is_published' => true, 'published_at' => today()]);
        $deleted->delete();

        $this->get($this->articlePath('old-draft-path', 'en'))->assertNotFound();
        $this->get($this->articlePath('old-future-path', 'en'))->assertNotFound();
        $this->get($this->articlePath('old-deleted-path', 'en'))->assertNotFound();

        $this->assertFalse($draft->is_published);
    }

    public function test_a_retired_slug_does_not_redirect_when_its_current_translation_is_unavailable(): void
    {
        $article = $this->renameDraft(
            $this->draftWithSlugs(
                'missing-translation-redirect-target',
                ['ar' => 'مسار-ترجمة-قديم', 'en' => 'old-translated-path'],
            ),
            ['ar' => 'مسار-ترجمة-حالي', 'en' => 'current-translated-path'],
        );
        $article->update(['is_published' => true, 'published_at' => today()]);
        DB::table('articles')->where('id', $article->getKey())->update([
            'slug' => json_encode(['ar' => 'مسار-ترجمة-حالي'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'slug_en' => '',
        ]);
        $article->refresh();

        $this->get($this->articlePath('old-translated-path', 'en'))->assertNotFound();
    }

    public function test_each_localized_slug_change_is_preserved_and_an_article_can_reclaim_its_own_alias(): void
    {
        $originalSlugs = ['ar' => 'المسار-الأصلي', 'en' => 'the-original-path'];
        $middleSlugs = ['ar' => 'المسار-الأوسط', 'en' => 'the-middle-path'];
        $currentSlugs = ['ar' => 'المسار-الأحدث', 'en' => 'the-newest-path'];
        $article = $this->draftWithSlugs('slug-history', $originalSlugs);

        $article = $this->renameDraft($article, $middleSlugs);
        $article = $this->renameDraft($article, $currentSlugs);

        foreach (['ar', 'en'] as $locale) {
            $this->assertDatabaseHas('article_slug_redirects', [
                'article_id' => $article->getKey(),
                'locale' => $locale,
                'slug' => $originalSlugs[$locale],
            ]);
            $this->assertDatabaseHas('article_slug_redirects', [
                'article_id' => $article->getKey(),
                'locale' => $locale,
                'slug' => $middleSlugs[$locale],
            ]);
        }

        $article = $this->renameDraft($article, $originalSlugs);

        foreach (['ar', 'en'] as $locale) {
            $this->assertSame($originalSlugs[$locale], $article->getTranslation('slug', $locale, false));
            $this->assertDatabaseMissing('article_slug_redirects', [
                'article_id' => $article->getKey(),
                'locale' => $locale,
                'slug' => $originalSlugs[$locale],
            ]);
            $this->assertDatabaseHas('article_slug_redirects', [
                'article_id' => $article->getKey(),
                'locale' => $locale,
                'slug' => $currentSlugs[$locale],
            ]);
        }

        $this->assertCount(4, $article->slugRedirects()->get());
    }

    public function test_editorial_api_rejects_retired_slug_collisions_on_create_and_update(): void
    {
        $source = $this->renameDraft(
            $this->draftWithSlugs(
                'api-slug-source',
                ['ar' => 'مسار-api-قديم', 'en' => 'retired-api-path'],
            ),
            ['ar' => 'مسار-api-حالي', 'en' => 'current-api-path'],
        );
        $client = Client::factory()->asClientCredentials()->create([
            'scopes' => ['articles:write'],
        ]);
        Passport::actingAsClient($client, ['articles:write']);
        $this->withToken('test-oauth-token');
        $payload = $this->apiArticlePayload();
        $payload['slug']['en'] = 'retired-api-path';

        $this->withHeader('Idempotency-Key', 'retired-slug-create')
            ->postJson('/api/v1/articles', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug.en']);

        $candidate = Article::factory()->create([
            'key' => 'api-slug-candidate',
            'is_published' => false,
            'editorial_revision' => 4,
        ]);

        $this->withHeaders([
            'Idempotency-Key' => 'retired-slug-update',
            'If-Match' => '"4"',
        ])->patchJson('/api/v1/articles/'.$candidate->getKey(), [
            'slug' => [
                'ar' => $candidate->getTranslation('slug', 'ar', false),
                'en' => 'retired-api-path',
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors(['slug.en']);

        $this->assertSame('current-api-path', $source->getTranslation('slug', 'en', false));
        $this->assertNotSame('retired-api-path', $candidate->fresh()->getTranslation('slug', 'en', false));
    }

    public function test_filament_rejects_a_retired_slug_owned_by_another_article(): void
    {
        Storage::fake('public');
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->renameDraft(
            $this->draftWithSlugs(
                'filament-slug-source',
                ['ar' => 'مسار-filament-قديم', 'en' => 'retired-filament-path'],
            ),
            ['ar' => 'مسار-filament-حالي', 'en' => 'current-filament-path'],
        );
        $candidate = Article::factory()->create([
            'key' => 'filament-slug-candidate',
            'is_published' => false,
        ]);

        Livewire::actingAs($admin)
            ->test(EditArticle::class, ['record' => $candidate->getKey()])
            ->set('data.slug.en', 'retired-filament-path')
            ->call('save')
            ->assertHasFormErrors(['slug.en']);

        $this->assertNotSame('retired-filament-path', $candidate->fresh()->getTranslation('slug', 'en', false));
    }

    /** @param array{ar: string, en: string} $slugs */
    private function draftWithSlugs(string $key, array $slugs): Article
    {
        return Article::factory()->create([
            'key' => $key,
            'slug' => $slugs,
            'is_published' => false,
        ]);
    }

    /** @param array{ar: string, en: string} $slugs */
    private function renameDraft(Article $article, array $slugs): Article
    {
        return app(UpdateEditorialArticle::class)->handle(
            $article,
            ['slug' => $slugs],
            $article->editorial_revision,
        );
    }

    private function articlePath(string $slug, string $locale): string
    {
        return localized_route(
            'writing.show',
            ['article' => $slug],
            false,
            $locale,
        );
    }

    private function articleUrl(Article $article, string $locale): string
    {
        return localized_route(
            'writing.show',
            ['article' => $article->getTranslation('slug', $locale, false)],
            true,
            $locale,
        );
    }

    private function canonicalArticleUrl(Article $article, string $locale): string
    {
        return rtrim((string) config('app.url'), '/').$this->articlePath(
            $article->getTranslation('slug', $locale, false),
            $locale,
        );
    }

    /** @return array<string, mixed> */
    private function apiArticlePayload(): array
    {
        return [
            'key' => 'api-retired-slug-candidate',
            'title' => ['ar' => 'عنوان المقال', 'en' => 'Article title'],
            'slug' => ['ar' => 'مسار-api-مرشح', 'en' => 'api-candidate-path'],
            'type' => ['ar' => 'مقال', 'en' => 'Article'],
            'summary' => ['ar' => 'ملخص المقال', 'en' => 'A concise article summary.'],
            'body' => [
                'ar' => $this->articleDocument('مقدمة المقال', 'الفكرة', 'تفصيل عملي واضح.'),
                'en' => $this->articleDocument('The opening paragraph.', 'The idea', 'Useful practical detail.'),
            ],
            'image_alt' => ['ar' => 'رسم يوضح فكرة المقال', 'en' => 'Diagram illustrating the article idea'],
            'seo_title' => ['ar' => 'عنوان محركات البحث', 'en' => 'Search title'],
            'seo_description' => ['ar' => 'وصف موجز للمقال.', 'en' => 'A concise search description.'],
            'topic_keys' => ['strategy'],
        ];
    }

    /** @return array<string, mixed> */
    private function articleDocument(string $opening, string $heading, string $detail): array
    {
        return [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $opening]]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $heading]]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => str_repeat($detail.' ', 35)]]],
            ],
        ];
    }
}
