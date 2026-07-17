<?php

namespace Tests\Feature\ArticleAudio;

use App\Enums\ArticleNarrationStatus;
use App\Filament\Pages\ManageArticleAudio;
use App\Jobs\GenerateArticleAudio;
use App\Jobs\GenerateArticleAudioSample;
use App\Jobs\PrepareArticleNarration;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Models\User;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArticleNarrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ArticleSeeder::class, PermissionSeeder::class]);
        Queue::fake();
        Storage::fake('public');
        config()->set('ai.providers.openai.key', 'openai-server-only-secret');
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
            'voice_id' => 'arabic-editorial-voice',
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

    public function test_active_work_polling_is_isolated_from_the_interactive_page(): void
    {
        $editor = $this->editor();
        $article = app(ArticleCatalog::class)->findByKey('ai-value');
        $this->assertNotNull($article);
        $sourceHash = app(ArticleNarrationScript::class)->fingerprint($article, 'ar');
        $narration = ArticleNarration::factory()->preparing()->create([
            'source_hash' => $sourceHash,
        ]);

        $this->actingAs($editor)
            ->get(ManageArticleAudio::getUrl())
            ->assertOk()
            ->assertSee('wire:poll.5s.visible="pollWorkStatus"', false)
            ->assertSee('wire:key="article-audio-ai-value-ar"', false)
            ->assertDontSee('<div wire:poll.5s.visible class="space-y-6">', false);

        $component = Livewire::actingAs($editor)
            ->test(ManageArticleAudio::class)
            ->assertSet('activeWork', true)
            ->assertSet('observedActiveWork', true);

        $narration->forceFill([
            'status' => ArticleNarrationStatus::Draft,
            'prepared_at' => now(),
        ])->save();

        $component
            ->call('pollWorkStatus')
            ->assertSet('activeWork', false)
            ->assertSet('observedActiveWork', true);

        $page = app(ManageArticleAudio::class);
        $page->mount();

        Event::fake([
            'eloquent.retrieved: '.ArticleAudio::class,
            'eloquent.retrieved: '.ArticleNarration::class,
        ]);

        $page->pollWorkStatus();

        $this->assertFalse($page->activeWork);
        Event::assertNotDispatched('eloquent.retrieved: '.ArticleAudio::class);
        Event::assertNotDispatched('eloquent.retrieved: '.ArticleNarration::class);
    }

    public function test_stale_or_unconfigured_sample_work_does_not_lock_the_admin_page(): void
    {
        $editor = $this->editor();
        $article = app(ArticleCatalog::class)->findByKey('ai-value');
        $this->assertNotNull($article);
        $sourceHash = app(ArticleNarrationScript::class)->fingerprint($article, 'ar');

        $narration = ArticleNarration::factory()->create([
            'source_hash' => 'stale-source-hash',
            'samples' => [
                'eleven_v3' => ['status' => 'processing'],
            ],
        ]);
        ArticleAudio::factory()->queued()->create([
            'article_key' => 'obsolete-article',
            'queued_at' => now(),
        ]);

        Livewire::actingAs($editor)
            ->test(ManageArticleAudio::class)
            ->assertSet('activeWork', false);

        $narration->forceFill([
            'source_hash' => $sourceHash,
            'samples' => [
                'retired_model' => ['status' => 'processing'],
            ],
        ])->save();

        Livewire::actingAs($editor)
            ->test(ManageArticleAudio::class)
            ->assertSet('activeWork', false);

        $narration->forceFill([
            'samples' => [
                'eleven_v3' => ['status' => 'processing'],
            ],
        ])->save();

        Livewire::actingAs($editor)
            ->test(ManageArticleAudio::class)
            ->assertSet('activeWork', true);
    }

    public function test_samples_require_the_current_shared_voice(): void
    {
        $narration = ArticleNarration::factory()->create([
            'article_key' => 'ai-value',
            'locale' => 'en',
            'script' => 'An editorial script.',
            'samples' => [
                'eleven_v3' => [
                    'status' => 'ready',
                    'script_hash' => hash('sha256', 'An editorial script.'),
                    'path' => 'article-audio/samples/en/old.mp3',
                    'voice_id' => 'old-voice',
                ],
            ],
        ]);

        $this->assertFalse($narration->hasCurrentSample('eleven_v3'));

        $narration->updateSample('eleven_v3', [
            'voice_id' => 'arabic-editorial-voice',
        ]);

        $this->assertTrue($narration->hasCurrentSample('eleven_v3'));
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
            'view_any articles',
            'update articles',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
