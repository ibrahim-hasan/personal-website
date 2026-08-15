<?php

namespace Tests\Feature\Editorial;

use App\Actions\Editorial\ArticlePublicationValidator;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Models\Article;
use App\Models\User;
use App\Support\Editorial\ArticleBody;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleAdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $this->bootAdminPanel();
    }

    public function test_an_admin_creates_a_featured_draft_through_the_editorial_lifecycle(): void
    {
        $admin = $this->administrator();
        $key = 'admin-lifecycle-create';

        $component = Livewire::actingAs($admin)
            ->test(CreateArticle::class)
            ->assertFormComponentDoesNotExist('published_at')
            ->fillForm($this->articleFormData($key, featured: true))
            ->call('create')
            ->assertHasNoFormErrors();

        $article = Article::query()->where('key', $key)->firstOrFail();

        $component->assertRedirect(ArticleResource::getUrl('edit', ['record' => $article], isAbsolute: false));

        $this->assertModelExists($article);
        $this->assertTrue($article->featured);
        $this->assertFalse($article->is_published);
        $this->assertTrue($article->published_at->isSameDay(today()));
        $this->assertTrue($article->modified_at->isSameDay(today()));
        $this->assertSame(1, $article->editorial_revision);
        $this->assertTrue($article->hasMedia(Article::IMAGE_COLLECTION));

        $snapshot = $article->revisionSnapshots()->sole();

        $this->assertSame(1, $snapshot->revision);
        $this->assertSame('article.created', $snapshot->action);
    }

    public function test_a_draft_edit_uses_the_editorial_lifecycle_and_ignores_forged_protected_fields(): void
    {
        $admin = $this->administrator();
        $originalPublishedAt = today()->subDays(14);
        $article = Article::factory()->create([
            'key' => 'protected-draft',
            'is_published' => false,
            'published_at' => $originalPublishedAt,
            'modified_at' => today()->subDays(7),
            'editorial_revision' => 7,
        ]);

        Livewire::actingAs($admin)
            ->test(EditArticle::class, ['record' => $article->getKey()])
            ->set('data.key', 'forged-key')
            ->set('data.is_published', true)
            ->set('data.published_at', today()->addDays(30)->toDateString())
            ->set('data.modified_at', today()->addDays(31)->toDateString())
            ->set('data.editorial_revision', 999)
            ->fillForm([
                'summary' => [
                    'ar' => 'ملخص محدث للمسودة.',
                    'en' => 'An updated draft summary.',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $article->refresh();

        $this->assertSame('protected-draft', $article->key);
        $this->assertFalse($article->is_published);
        $this->assertTrue($article->published_at->isSameDay($originalPublishedAt));
        $this->assertTrue($article->modified_at->isSameDay(today()));
        $this->assertSame(8, $article->editorial_revision);
        $this->assertSame('An updated draft summary.', $article->getTranslation('summary', 'en', false));

        $snapshot = $article->revisionSnapshots()->sole();

        $this->assertSame(8, $snapshot->revision);
        $this->assertSame('article.updated', $snapshot->action);
    }

    public function test_a_stale_edit_is_rejected_without_overwriting_the_latest_draft(): void
    {
        $admin = $this->administrator();
        $article = Article::factory()->create([
            'is_published' => false,
            'editorial_revision' => 1,
        ]);
        $inlineImage = $article
            ->addMedia(UploadedFile::fake()->image('existing-inline-image.jpg', 1200, 800))
            ->toMediaCollection(Article::BODY_EN_COLLECTION);
        $englishBody = $article->getTranslation('body', 'en', false);
        $englishBody['content'][] = [
            'type' => 'image',
            'attrs' => [
                'id' => $inlineImage->uuid,
                'src' => $inlineImage->getUrl(),
                'alt' => 'An existing inline image that a stale editor must not remove',
            ],
        ];
        $article->setTranslation('body', 'en', $englishBody)->save();
        $article->refresh();

        $staleBody = $article->getTranslation('body', 'en', false);
        $staleBody['content'] = array_values(array_filter(
            $staleBody['content'],
            static fn (array $node): bool => ($node['type'] ?? null) !== 'image',
        ));

        $firstEditor = Livewire::actingAs($admin)
            ->test(EditArticle::class, ['record' => $article->getKey()]);
        $staleEditor = Livewire::actingAs($admin)
            ->test(EditArticle::class, ['record' => $article->getKey()]);

        $firstEditor
            ->fillForm([
                'summary' => [
                    'ar' => 'ملخص المحرر الأول.',
                    'en' => 'The first editor summary.',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $staleEditor
            ->fillForm([
                'summary' => [
                    'ar' => 'ملخص محرر قديم.',
                    'en' => 'A stale editor summary.',
                ],
                'body_en' => $staleBody,
            ])
            ->call('save')
            ->assertHasFormErrors(['key'])
            ->assertSeeText(__('editorial_admin.feedback.stale_edit'));

        $article->refresh();

        $this->assertSame(2, $article->editorial_revision);
        $this->assertSame('The first editor summary.', $article->getTranslation('summary', 'en', false));
        $this->assertSame(1, $article->revisionSnapshots()->count());
        $this->assertSame('article.updated', $article->revisionSnapshots()->sole()->action);
        $this->assertNotNull($article->getMedia(Article::BODY_EN_COLLECTION)->firstWhere('uuid', $inlineImage->uuid));
        $this->assertContains(
            $inlineImage->uuid,
            array_column(app(ArticleBody::class)->images($article->getTranslation('body', 'en', false)), 'id'),
        );
    }

    public function test_an_invalid_rich_editor_document_is_rejected_before_a_draft_is_updated(): void
    {
        $admin = $this->administrator();
        $article = Article::factory()->create([
            'is_published' => false,
            'editorial_revision' => 4,
        ]);
        $originalBody = $article->getTranslation('body', 'en', false);

        Livewire::actingAs($admin)
            ->test(EditArticle::class, ['record' => $article->getKey()])
            ->fillForm([
                'body_en' => [
                    'type' => 'unsupported-node',
                    'content' => [],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['body_en']);

        $article->refresh();

        $this->assertSame(4, $article->editorial_revision);
        $this->assertEquals($originalBody, $article->getTranslation('body', 'en', false));
        $this->assertSame(0, $article->revisionSnapshots()->count());
    }

    public function test_the_saved_draft_checklist_gates_publishing_and_records_publish_transitions(): void
    {
        $admin = $this->administrator();
        $validator = app(ArticlePublicationValidator::class);
        $legacyImageDraft = Article::factory()->create([
            'is_published' => false,
            'image' => 'images/legacy/article.webp',
        ]);

        $this->assertSame(['article.image_missing'], $validator->publishReadinessViolations($legacyImageDraft));

        Livewire::actingAs($admin)
            ->test(EditArticle::class, ['record' => $legacyImageDraft->getKey()])
            ->assertSeeText(__('editorial_admin.readiness.violations.image_missing'))
            ->assertActionVisible('publish')
            ->assertActionDisabled('publish');

        $readyDraft = Article::factory()->create([
            'is_published' => false,
            'image' => null,
            'editorial_revision' => 4,
        ]);
        $readyDraft
            ->addMedia(UploadedFile::fake()->image('managed-hero.jpg', 1600, 900))
            ->toMediaCollection(Article::IMAGE_COLLECTION);
        $readyDraft->refresh();

        $this->assertTrue($validator->isReadyToPublish($readyDraft));

        $component = Livewire::actingAs($admin)
            ->test(EditArticle::class, ['record' => $readyDraft->getKey()])
            ->assertSeeText(__('editorial_admin.readiness.ready'))
            ->assertActionVisible('publish')
            ->assertActionEnabled('publish')
            ->callAction('publish');

        $readyDraft->refresh();

        $this->assertTrue($readyDraft->is_published);
        $this->assertTrue($readyDraft->published_at->isSameDay(today()));
        $this->assertSame(5, $readyDraft->editorial_revision);
        $this->assertSame('article.published', $readyDraft->revisionSnapshots()->sole()->action);

        $component
            ->assertActionVisible('unpublish')
            ->callAction('unpublish');

        $readyDraft->refresh();

        $this->assertFalse($readyDraft->is_published);
        $this->assertSame(6, $readyDraft->editorial_revision);
        $this->assertSame('article.published', $readyDraft->revisionSnapshots()->where('revision', 5)->sole()->action);
        $this->assertSame('article.unpublished', $readyDraft->revisionSnapshots()->where('revision', 6)->sole()->action);
    }

    /** @return array<string, mixed> */
    private function articleFormData(string $key, bool $featured): array
    {
        return [
            'key' => $key,
            'title' => [
                'ar' => 'مسودة دورة حياة الإدارة',
                'en' => 'Admin lifecycle draft',
            ],
            'slug' => [
                'ar' => 'مسودة-دورة-حياة-الإدارة',
                'en' => $key,
            ],
            'type' => [
                'ar' => 'مقال',
                'en' => 'Article',
            ],
            'summary' => [
                'ar' => 'ملخص واضح لمسودة اختبار دورة الحياة.',
                'en' => 'A clear summary for the lifecycle test draft.',
            ],
            'body_ar' => $this->richBody(
                'مقدمة عملية توضّح قراراً تحريرياً واضحاً.',
                'خطوة عملية',
                'تفصيل عربي مفيد يدعم فهم القرار والتحرك التالي. ',
            ),
            'body_en' => $this->richBody(
                'An opening paragraph that explains a clear editorial decision.',
                'A practical step',
                'Useful English detail that supports the decision and the next action. ',
            ),
            'image_alt' => [
                'ar' => 'رسم يوضح سير العمل التحريري',
                'en' => 'An illustration of the editorial workflow',
            ],
            'image_caption' => [
                'ar' => null,
                'en' => null,
            ],
            'seo_title' => [
                'ar' => 'مسودة دورة حياة الإدارة',
                'en' => 'Admin lifecycle draft',
            ],
            'seo_description' => [
                'ar' => 'وصف موجز لمسودة تختبر دورة الحياة التحريرية.',
                'en' => 'A concise description for an editorial lifecycle test draft.',
            ],
            'topic_keys' => ['products'],
            'source_url' => null,
            'featured' => $featured,
            Article::IMAGE_COLLECTION => [UploadedFile::fake()->image('hero.jpg', 1600, 900)],
        ];
    }

    /** @return array<string, mixed> */
    private function richBody(string $opening, string $heading, string $detail): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [[
                        'type' => 'text',
                        'text' => $opening.' '.str_repeat($detail, 20),
                    ]],
                ],
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 2],
                    'content' => [[
                        'type' => 'text',
                        'text' => $heading,
                    ]],
                ],
                [
                    'type' => 'paragraph',
                    'content' => [[
                        'type' => 'text',
                        'text' => str_repeat($detail, 30),
                    ]],
                ],
            ],
        ];
    }

    private function administrator(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        return $admin;
    }

    private function bootAdminPanel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }
}
