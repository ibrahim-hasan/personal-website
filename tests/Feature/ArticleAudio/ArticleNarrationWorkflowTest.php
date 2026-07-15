<?php

namespace Tests\Feature\ArticleAudio;

use App\Enums\ArticleNarrationStatus;
use App\Filament\Pages\ManageArticleAudio;
use App\Jobs\GenerateArticleAudio;
use App\Jobs\GenerateArticleAudioSample;
use App\Jobs\PrepareArticleNarration;
use App\Models\ArticleNarration;
use App\Models\User;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArticleNarrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Queue::fake();
        Storage::fake('public');
        config()->set('services.openai.api_key', 'openai-server-only-secret');
        config()->set('services.elevenlabs.api_key', 'eleven-server-only-secret');
        config()->set('services.elevenlabs.voice_id', 'arabic-editorial-voice');
    }

    public function test_editor_can_queue_ai_preparation_then_review_and_approve_the_draft(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.narration.prepare', [
                'article' => 'ai-value',
                'locale' => 'ar',
            ]))
            ->assertRedirect(ManageArticleAudio::getUrl());

        $narration = ArticleNarration::query()->firstOrFail();

        $this->assertSame(ArticleNarrationStatus::Queued, $narration->status);
        $this->assertSame($editor->getKey(), $narration->requested_by_user_id);
        Queue::assertPushed(PrepareArticleNarration::class, 1);

        $article = app(ArticleCatalog::class)->findByKey('ai-value');
        $this->assertNotNull($article);
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');
        $narration->forceFill([
            'status' => ArticleNarrationStatus::Draft,
            'source_hash' => hash('sha256', $source),
            'script' => $source,
            'prepared_at' => now(),
        ])->save();

        $reviewed = $source."\n\n[long pause]";

        $this->actingAs($editor)
            ->put(route('filament.admin.article-audio.narration.update', [
                'article' => 'ai-value',
                'locale' => 'ar',
            ]), [
                'script' => $reviewed,
                'action' => 'approve',
            ])
            ->assertRedirect(ManageArticleAudio::getUrl());

        $narration->refresh();

        $this->assertSame(ArticleNarrationStatus::Approved, $narration->status);
        $this->assertSame($reviewed, $narration->script);
        $this->assertNotNull($narration->approved_at);
    }

    public function test_short_sample_is_required_before_full_generation_for_the_selected_model(): void
    {
        $editor = $this->editor();
        $narration = $this->approvedNarration();
        $fullUrl = route('filament.admin.article-audio.generate', [
            'article' => 'ai-value',
            'locale' => 'ar',
        ]);

        $this->actingAs($editor)
            ->post($fullUrl, ['model_id' => 'eleven_v3'])
            ->assertRedirect(ManageArticleAudio::getUrl());

        Queue::assertNotPushed(GenerateArticleAudio::class);

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.sample.generate', [
                'article' => 'ai-value',
                'locale' => 'ar',
            ]), ['model_id' => 'eleven_v3'])
            ->assertRedirect(ManageArticleAudio::getUrl());

        Queue::assertPushed(GenerateArticleAudioSample::class, fn (GenerateArticleAudioSample $job): bool => $job->modelId === 'eleven_v3');

        $narration->refresh()->updateSample('eleven_v3', [
            'status' => 'ready',
            'script_hash' => $narration->scriptFingerprint(),
            'disk' => 'public',
            'path' => 'article-audio/samples/ar/approved-v3.mp3',
            'generated_at' => now()->toIso8601String(),
        ]);
        Storage::disk('public')->put('article-audio/samples/ar/approved-v3.mp3', 'sample-audio');

        $this->actingAs($editor)
            ->post($fullUrl, ['model_id' => 'eleven_v3'])
            ->assertRedirect(ManageArticleAudio::getUrl());

        Queue::assertPushed(GenerateArticleAudio::class, function (GenerateArticleAudio $job): bool {
            return $job->articleKey === 'ai-value'
                && $job->locale === 'ar'
                && $job->modelId === 'eleven_v3';
        });
    }

    public function test_admin_page_exposes_workflow_controls_without_rendering_provider_secrets(): void
    {
        $editor = $this->editor();
        $this->approvedNarration();

        $this->actingAs($editor)
            ->get(ManageArticleAudio::getUrl())
            ->assertOk()
            ->assertSee(__('article_audio.actions.generate_sample'))
            ->assertSee(__('article_audio.actions.approve'))
            ->assertDontSee('openai-server-only-secret', false)
            ->assertDontSee('eleven-server-only-secret', false);
    }

    private function approvedNarration(): ArticleNarration
    {
        $article = app(ArticleCatalog::class)->findByKey('ai-value');
        $this->assertNotNull($article);
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');

        return ArticleNarration::factory()->approved()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'source_hash' => hash('sha256', $source),
            'script' => $source,
            'samples' => [],
        ]);
    }

    private function editor(): User
    {
        $role = Role::create([
            'name' => fake()->unique()->slug(),
            'guard_name' => 'web',
        ]);
        $role->syncPermissions([
            'view_any intellectual_libraries',
            'update intellectual_libraries',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
