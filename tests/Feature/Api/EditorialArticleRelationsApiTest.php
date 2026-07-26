<?php

namespace Tests\Feature\Api;

use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDeliveryEntity;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectEvidenceLevel;
use App\Enums\ProjectPermissionStatus;
use App\Models\Article;
use App\Models\EditorialApiAuditLog;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use App\Services\EditorialApi\EditorialArticleRelations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\TestCase;

class EditorialArticleRelationsApiTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->client = Client::factory()->asClientCredentials()->create([
            'scopes' => ['articles:read', 'articles:write'],
        ]);
    }

    public function test_creation_replaces_ordered_related_content_and_preserves_stable_keys_in_all_api_records(): void
    {
        $firstService = Service::factory()->create(['key' => 'service-first']);
        $secondService = Service::factory()->create(['key' => 'service-second']);
        $firstProject = $this->eligibleProject('project-first');
        $secondProject = $this->eligibleProject('project-second');
        $payload = $this->articlePayload();
        $payload['service_keys'] = [$secondService->key, $firstService->key];
        $payload['project_keys'] = [$secondProject->key, $firstProject->key];
        $headers = ['Idempotency-Key' => 'article-relations-create-001'];

        $this->asClient(['articles:write'])
            ->withHeaders($headers)
            ->postJson('/api/v1/articles', $payload)
            ->assertCreated()
            ->assertJsonPath('data.service_keys', [$secondService->key, $firstService->key])
            ->assertJsonPath('data.project_keys', [$secondProject->key, $firstProject->key]);

        $article = Article::query()->where('key', $payload['key'])->firstOrFail();

        $this->assertSame([$secondService->key, $firstService->key], $article->relatedServiceKeys());
        $this->assertSame([$secondProject->key, $firstProject->key], $article->relatedProjectKeys());
        $this->assertDatabaseHas('article_service', [
            'article_id' => $article->getKey(),
            'service_id' => $secondService->getKey(),
            'sort_order' => 0,
        ]);
        $this->assertDatabaseHas('article_project', [
            'article_id' => $article->getKey(),
            'project_id' => $secondProject->getKey(),
            'sort_order' => 0,
        ]);

        $snapshot = $article->revisionSnapshots()->sole();
        $this->assertSame(1, $snapshot->revision);
        $this->assertSame([$secondService->key, $firstService->key], $snapshot->service_keys);
        $this->assertSame([$secondProject->key, $firstProject->key], $snapshot->project_keys);

        $audit = EditorialApiAuditLog::query()->sole();
        $this->assertEquals([
            'service_keys' => [$secondService->key, $firstService->key],
            'project_keys' => [$secondProject->key, $firstProject->key],
        ], $audit->related_content_keys);

        $this->asClient(['articles:write'])
            ->withHeaders($headers)
            ->postJson('/api/v1/articles', $payload)
            ->assertCreated()
            ->assertHeader('Idempotent-Replay', 'true')
            ->assertJsonPath('data.service_keys', [$secondService->key, $firstService->key])
            ->assertJsonPath('data.project_keys', [$secondProject->key, $firstProject->key]);

        $this->assertDatabaseCount('articles', 1);
        $this->assertDatabaseCount('editorial_article_revision_snapshots', 1);
        $this->assertDatabaseCount('editorial_api_audit_logs', 1);
    }

    public function test_an_update_replaces_only_the_relation_set_that_was_supplied_in_its_order(): void
    {
        $firstService = Service::factory()->create(['key' => 'existing-service']);
        $replacementService = Service::factory()->create(['key' => 'replacement-service']);
        $firstProject = $this->eligibleProject('existing-project');
        $secondProject = $this->eligibleProject('second-project');
        $article = Article::factory()->create(['is_published' => false, 'editorial_revision' => 1]);
        $article->services()->attach($firstService, ['sort_order' => 0]);
        $article->projects()->attach([
            $secondProject->getKey() => ['sort_order' => 0],
            $firstProject->getKey() => ['sort_order' => 1],
        ]);

        $this->asClient(['articles:write'])
            ->withHeaders([
                'Idempotency-Key' => 'article-relations-update-001',
                'If-Match' => '"1"',
            ])
            ->patchJson('/api/v1/articles/'.$article->getKey(), [
                'service_keys' => [$replacementService->key],
            ])
            ->assertOk()
            ->assertJsonPath('data.service_keys', [$replacementService->key])
            ->assertJsonPath('data.project_keys', [$secondProject->key, $firstProject->key])
            ->assertJsonPath('data.revision', 2);

        $article->refresh();

        $this->assertSame([$replacementService->key], $article->relatedServiceKeys());
        $this->assertSame([$secondProject->key, $firstProject->key], $article->relatedProjectKeys());

        $snapshot = $article->revisionSnapshots()->sole();
        $this->assertSame(2, $snapshot->revision);
        $this->assertSame([$replacementService->key], $snapshot->service_keys);
        $this->assertSame([$secondProject->key, $firstProject->key], $snapshot->project_keys);
    }

    public function test_unknown_duplicate_and_non_public_related_content_keys_are_rejected_with_field_specific_errors(): void
    {
        $inactiveService = Service::factory()->inactive()->create(['key' => 'inactive-service']);
        $nonPublicProject = Project::factory()->create([
            'key' => 'non-public-project',
            'is_detailed_case_study' => false,
        ]);
        $payload = $this->articlePayload();
        $payload['service_keys'] = [$inactiveService->key];
        $payload['project_keys'] = [$nonPublicProject->key];

        $this->asClient(['articles:write'])
            ->withHeader('Idempotency-Key', 'article-relations-not-public-001')
            ->postJson('/api/v1/articles', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_keys.0', 'project_keys.0']);

        $payload = $this->articlePayload();
        $payload['key'] = 'article-with-unknown-relation';
        $payload['slug'] = ['ar' => 'مقال-بعلاقة-غير-معروفة', 'en' => 'article-with-unknown-relation'];
        $payload['service_keys'] = ['missing-service'];

        $this->asClient(['articles:write'])
            ->withHeader('Idempotency-Key', 'article-relations-unknown-001')
            ->postJson('/api/v1/articles', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_keys.0']);

        $publishedService = Service::factory()->create(['key' => 'duplicate-service']);
        $payload = $this->articlePayload();
        $payload['key'] = 'article-with-duplicate-relation';
        $payload['slug'] = ['ar' => 'مقال-بعلاقة-مكررة', 'en' => 'article-with-duplicate-relation'];
        $payload['service_keys'] = [$publishedService->key, $publishedService->key];

        $this->asClient(['articles:write'])
            ->withHeader('Idempotency-Key', 'article-relations-duplicate-001')
            ->postJson('/api/v1/articles', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_keys.0', 'service_keys.1']);

        $payload = $this->articlePayload();
        $payload['key'] = 'article-with-unordered-relation-map';
        $payload['slug'] = ['ar' => 'مقال-بعلاقة-غير-مرتبة', 'en' => 'article-with-unordered-relation-map'];
        $payload['service_keys'] = ['first' => $publishedService->key];

        $this->asClient(['articles:write'])
            ->withHeader('Idempotency-Key', 'article-relations-unordered-001')
            ->postJson('/api/v1/articles', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_keys']);
    }

    public function test_an_api_user_must_be_authorized_to_view_selected_related_content(): void
    {
        $service = Service::factory()->create(['key' => 'service-requiring-view-permission']);
        $user = User::factory()->create();
        $request = request();
        $originalResolver = $request->getUserResolver();
        $request->setUserResolver(static fn (?string $guard = null): User => $user);

        try {
            app(EditorialArticleRelations::class)->validate([
                'service_keys' => [$service->key],
            ]);
            $this->fail('An unauthorized related content key must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('service_keys.0', $exception->errors());
        } finally {
            $request->setUserResolver($originalResolver);
        }
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
            'key' => 'article-related-content',
            'title' => ['ar' => 'عنوان المقال', 'en' => 'Article title'],
            'slug' => ['ar' => 'عنوان-المقال', 'en' => 'article-related-content'],
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

    private function eligibleProject(string $key): Project
    {
        $sections = [
            'ar' => [
                'executive_summary' => 'ملخص تنفيذي يوضح نطاق العمل.',
                'context' => 'السياق التشغيلي للمشروع.',
                'constraints' => ['تعدد أصحاب القرار'],
                'changes' => [['area' => 'workflow', 'body' => 'أعيد تنظيم مسار العمل.']],
                'solution' => 'حل عملي مناسب للسياق.',
                'implementation' => 'نُفذ الحل على مراحل واضحة.',
                'adoption' => 'تلقى الفريق دعماً للتبني.',
                'lessons' => ['وضوح المسؤولية يسرع القرار.'],
            ],
            'en' => [
                'executive_summary' => 'An executive summary of the work.',
                'context' => 'The operational context for the project.',
                'constraints' => ['Several decision makers'],
                'changes' => [['area' => 'workflow', 'body' => 'The workflow was reorganized.']],
                'solution' => 'A practical solution for the context.',
                'implementation' => 'The solution was delivered in clear phases.',
                'adoption' => 'The team received adoption support.',
                'lessons' => ['Clear ownership speeds up decisions.'],
            ],
        ];

        return Project::factory()->create([
            'key' => $key,
            'slug' => ['ar' => 'مشروع-'.$key, 'en' => $key],
            'is_detailed_case_study' => true,
            'is_active' => true,
            'delivery_entity' => ProjectDeliveryEntity::Direct,
            'delivery_period' => ['ar' => '2025', 'en' => '2025'],
            'ibrahim_role' => ['ar' => 'دور قيادي في التنفيذ', 'en' => 'A delivery leadership role'],
            'confidentiality_note' => ['ar' => 'نُشرت التفاصيل بموافقة واضحة.', 'en' => 'The details are published with clear permission.'],
            'case_study_sections' => $sections,
            'case_study_reviewed_at' => now(),
            'permission_status' => ProjectPermissionStatus::ApprovedNamed,
            'permission_reference' => 'Named approval reference',
            'disclosure_level' => ProjectDisclosureLevel::Named,
            'evidence_level' => ProjectEvidenceLevel::Qualitative,
            'image_permission_status' => ProjectAssetPermissionStatus::Approved,
            'image_permission_reference' => 'Image permission record',
            'logo' => 'images/brands/projects/example.webp',
            'logo_permission_status' => ProjectAssetPermissionStatus::Approved,
            'logo_permission_reference' => 'Logo permission record',
        ]);
    }

    /** @return array<string, mixed> */
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
