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
            ->assertJsonPath('data.revision', 1)
            ->assertJsonPath('data.read_minutes.en', 1)
            ->assertJsonPath('data.body.en.type', 'doc');

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

    public function test_article_creation_rejects_a_malformed_rich_text_document(): void
    {
        $payload = $this->articlePayload();
        $payload['body']['en'] = [
            'type' => 'paragraph',
            'content' => [['type' => 'text', 'text' => 'This is not a document root.']],
        ];

        $this->asClient(['articles:write'])
            ->withHeader('Idempotency-Key', 'invalid-rich-body-001')
            ->postJson('/api/v1/articles', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body.en']);
    }

    public function test_legacy_structured_clients_are_upgraded_to_the_translatable_rich_body(): void
    {
        $payload = $this->articlePayload();
        unset($payload['body']);
        $payload['key'] = 'legacy-editorial-article';
        $payload['slug'] = ['ar' => 'مقال-قديم', 'en' => 'legacy-article'];
        $payload['lead'] = ['ar' => 'مقدمة المقال', 'en' => 'The article opening.'];
        $payload['sections'] = [
            'ar' => [['heading' => 'الفكرة', 'paragraphs' => [str_repeat('تفصيل عملي واضح. ', 35)]]],
            'en' => [['heading' => 'The idea', 'paragraphs' => [str_repeat('Useful practical detail. ', 35)]]],
        ];
        $payload['closing'] = ['ar' => 'الخلاصة', 'en' => 'The conclusion.'];

        $this->asClient(['articles:write'])
            ->withHeader('Idempotency-Key', 'legacy-create-001')
            ->postJson('/api/v1/articles', $payload)
            ->assertCreated()
            ->assertJsonPath('data.body.ar.type', 'doc')
            ->assertJsonPath('data.body.en.type', 'doc');

        $article = Article::query()->where('key', 'legacy-editorial-article')->firstOrFail();

        $this->assertNotEmpty($article->getTranslation('body', 'ar', false));
        $this->assertNotEmpty($article->getTranslation('body', 'en', false));
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

    public function test_a_partial_legacy_body_update_is_rejected_instead_of_replacing_rich_content(): void
    {
        $article = Article::factory()->create(['is_published' => false, 'editorial_revision' => 1]);
        $originalBody = $article->getTranslations('body');

        $this->asClient(['articles:write'])
            ->withHeaders(['Idempotency-Key' => 'partial-legacy-update-001', 'If-Match' => '"1"'])
            ->patchJson('/api/v1/articles/'.$article->getKey(), [
                'lead' => ['ar' => 'مقدمة بديلة', 'en' => 'A replacement opening.'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sections', 'closing']);

        $this->assertSame($originalBody, $article->fresh()->getTranslations('body'));
    }

    public function test_a_published_article_must_be_unpublished_before_content_or_media_changes(): void
    {
        $article = Article::factory()->create(['is_published' => true, 'editorial_revision' => 1]);
        $originalSummary = $article->getTranslations('summary');

        $this->asClient(['articles:write'])
            ->withHeaders(['Idempotency-Key' => 'published-content-update-001', 'If-Match' => '"1"'])
            ->patchJson('/api/v1/articles/'.$article->getKey(), [
                'summary' => ['ar' => 'تغيير مباشر', 'en' => 'A direct live change'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['article']);

        $this->asClient(['media:write'])
            ->withHeaders(['Idempotency-Key' => 'published-media-update-001', 'If-Match' => '"1"'])
            ->put('/api/v1/articles/'.$article->getKey().'/image', [
                'image' => UploadedFile::fake()->image('replacement.jpg', 1600, 900),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['article']);

        $article->refresh();

        $this->assertSame($originalSummary, $article->getTranslations('summary'));
        $this->assertFalse($article->hasMedia(Article::IMAGE_COLLECTION));
        $this->assertSame(1, $article->editorial_revision);
    }

    public function test_updating_a_draft_body_removes_inline_media_that_is_no_longer_referenced(): void
    {
        $article = Article::factory()->create(['is_published' => false, 'editorial_revision' => 1]);
        $media = $article
            ->addMedia(UploadedFile::fake()->image('old-inline-image.jpg', 1200, 800))
            ->toMediaCollection(Article::BODY_EN_COLLECTION);
        $englishBody = $this->articleBodyDocument(
            'The opening article paragraph.',
            'The idea',
            str_repeat('Useful practical detail. ', 35),
        );
        $englishBody['content'][] = [
            'type' => 'image',
            'attrs' => [
                'id' => $media->uuid,
                'src' => $media->getUrl(),
                'alt' => 'An inline image that will be removed',
            ],
        ];
        $article->setTranslation('body', 'en', $englishBody)->save();

        $this->asClient(['articles:write'])
            ->withHeaders(['Idempotency-Key' => 'remove-inline-media-001', 'If-Match' => '"1"'])
            ->patchJson('/api/v1/articles/'.$article->getKey(), [
                'body' => [
                    'ar' => $this->articleBodyDocument(
                        'مقدمة المقال',
                        'الفكرة',
                        str_repeat('تفصيل عملي واضح. ', 35),
                    ),
                    'en' => $this->articleBodyDocument(
                        'A revised opening.',
                        'A revised idea',
                        str_repeat('Revised practical detail. ', 35),
                    ),
                ],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('media', ['id' => $media->getKey()]);
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
            'summary' => ['ar' => 'ملخص المقال', 'en' => 'A concise article summary.'],
            'body' => [
                'ar' => $this->articleBodyDocument('مقدمة المقال', 'الفكرة', str_repeat('تفصيل عملي واضح. ', 35)),
                'en' => $this->articleBodyDocument('The opening article paragraph.', 'The idea', str_repeat('Useful practical detail. ', 35)),
            ],
            'image_alt' => ['ar' => 'رسم يوضح فكرة المقال', 'en' => 'Diagram illustrating the article idea'],
            'seo_title' => ['ar' => 'عنوان SEO', 'en' => 'SEO title'],
            'seo_description' => ['ar' => 'وصف SEO', 'en' => 'SEO description.'],
            'topic_keys' => ['strategy'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function articleBodyDocument(string $opening, string $heading, string $detail): array
    {
        return [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $opening]]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => $heading]]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $detail]]],
            ],
        ];
    }
}
