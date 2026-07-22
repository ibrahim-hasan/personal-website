<?php

namespace Tests\Feature\Api;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\TestCase;

class EditorialArticleApiTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->client = Client::factory()->asClientCredentials()->create([
            'scopes' => ['articles:read', 'articles:write', 'articles:publish', 'articles:archive', 'media:write'],
        ]);
    }

    public function test_requests_without_an_oauth_token_are_rejected(): void
    {
        $this->postJson('/api/v1/articles', $this->articlePayload())
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_a_client_with_write_scope_can_create_a_draft_and_replay_its_idempotent_request(): void
    {
        $headers = ['Idempotency-Key' => 'create-article-001'];

        $response = $this->asClient(['articles:write'])
            ->withHeaders($headers)
            ->postJson('/api/v1/articles', $this->articlePayload());

        $response->assertCreated()
            ->assertHeader('ETag', '"1"')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.revision', 1);

        $replay = $this->asClient(['articles:write'])
            ->withHeaders($headers)
            ->postJson('/api/v1/articles', $this->articlePayload());

        $replay->assertCreated()
            ->assertHeader('Idempotent-Replay', 'true');
        $this->assertDatabaseCount('articles', 1);
    }

    public function test_article_creation_requires_complete_bilingual_content(): void
    {
        $payload = $this->articlePayload();
        unset($payload['summary']['en']);

        $this->asClient(['articles:write'])
            ->withHeader('Idempotency-Key', 'invalid-bilingual-001')
            ->postJson('/api/v1/articles', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['summary.en']);
    }

    public function test_updates_require_a_current_revision_and_the_write_scope(): void
    {
        $article = Article::factory()->create(['is_published' => false, 'editorial_revision' => 3]);

        $this->asClient(['articles:read'])
            ->withHeaders(['Idempotency-Key' => 'scope-denied-001', 'If-Match' => '"3"'])
            ->patchJson('/api/v1/articles/'.$article->getKey(), ['summary' => ['ar' => 'ملخص', 'en' => 'Summary']])
            ->assertForbidden();

        $this->asClient(['articles:write'])
            ->withHeaders(['Idempotency-Key' => 'stale-update-001', 'If-Match' => '"2"'])
            ->patchJson('/api/v1/articles/'.$article->getKey(), ['summary' => ['ar' => 'ملخص', 'en' => 'Summary']])
            ->assertStatus(409)
            ->assertJsonPath('errors.If-Match.0', 'The supplied revision is no longer current.');

        $this->asClient(['articles:write'])
            ->withHeaders(['Idempotency-Key' => 'valid-update-001', 'If-Match' => '"3"'])
            ->patchJson('/api/v1/articles/'.$article->getKey(), ['summary' => ['ar' => 'ملخص', 'en' => 'Summary']])
            ->assertOk()
            ->assertHeader('ETag', '"4"')
            ->assertJsonPath('data.revision', 4);
    }

    public function test_only_a_confirmed_publish_with_a_managed_image_can_make_a_draft_public(): void
    {
        $article = Article::factory()->create(['is_published' => false, 'image' => null, 'editorial_revision' => 1]);

        $this->asClient(['articles:publish'])
            ->withHeaders(['Idempotency-Key' => 'publish-no-image-001', 'If-Match' => '"1"'])
            ->postJson('/api/v1/articles/'.$article->getKey().'/publish', ['confirm' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['article']);

        $this->asClient(['media:write'])
            ->withHeaders(['Idempotency-Key' => 'upload-image-001', 'If-Match' => '"1"'])
            ->put('/api/v1/articles/'.$article->getKey().'/image', [
                'image' => UploadedFile::fake()->image('hero.jpg', 1600, 900),
            ])
            ->assertOk()
            ->assertHeader('ETag', '"2"');

        $this->asClient(['articles:publish'])
            ->withHeaders(['Idempotency-Key' => 'publish-image-001', 'If-Match' => '"2"'])
            ->postJson('/api/v1/articles/'.$article->getKey().'/publish', ['confirm' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'published')
            ->assertHeader('ETag', '"3"');

        $this->assertTrue($article->fresh()->is_published);
    }

    public function test_archive_and_restore_require_the_archive_scope_and_current_revisions(): void
    {
        $article = Article::factory()->create(['is_published' => false, 'editorial_revision' => 1]);

        $this->asClient(['articles:archive'])
            ->withHeaders(['Idempotency-Key' => 'archive-001', 'If-Match' => '"1"'])
            ->deleteJson('/api/v1/articles/'.$article->getKey(), ['confirm' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'archived')
            ->assertHeader('ETag', '"2"');

        $this->asClient(['articles:archive'])
            ->withHeaders(['Idempotency-Key' => 'restore-001', 'If-Match' => '"2"'])
            ->postJson('/api/v1/articles/'.$article->getKey().'/restore', ['confirm' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'draft')
            ->assertHeader('ETag', '"3"');
    }

    /** @param list<string> $scopes */
    private function asClient(array $scopes): static
    {
        Passport::actingAsClient($this->client, $scopes);
        $this->withToken('test-oauth-token');

        return $this;
    }

    /** @return array<string, mixed> */
    private function articlePayload(): array
    {
        return [
            'key' => 'api-editorial-article',
            'title' => ['ar' => 'عنوان المقال', 'en' => 'Article title'],
            'slug' => ['ar' => 'عنوان-المقال', 'en' => 'article-title'],
            'type' => ['ar' => 'مقال', 'en' => 'Article'],
            'read_minutes' => ['ar' => 5, 'en' => 4],
            'summary' => ['ar' => 'ملخص المقال', 'en' => 'A concise article summary.'],
            'lead' => ['ar' => 'مقدمة المقال', 'en' => 'The opening article paragraph.'],
            'sections' => [
                'ar' => [['heading' => 'الفكرة', 'paragraphs' => ['تفصيل الفكرة.']]],
                'en' => [['heading' => 'The idea', 'paragraphs' => ['The detailed point.']]],
            ],
            'closing' => ['ar' => 'الخلاصة', 'en' => 'The conclusion.'],
            'seo_title' => ['ar' => 'عنوان SEO', 'en' => 'SEO title'],
            'seo_description' => ['ar' => 'وصف SEO', 'en' => 'SEO description.'],
            'topic_keys' => ['strategy'],
        ];
    }
}
