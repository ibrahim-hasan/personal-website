<?php

namespace Tests\Feature\ArticleAudio;

use App\Enums\ArticleAudioStatus;
use App\Filament\Pages\ManageArticleAudio;
use App\Jobs\GenerateArticleAudio;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Models\User;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GenerateArticleAudioEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ArticleSeeder::class, PermissionSeeder::class]);
        config()->set('services.elevenlabs.api_key', 'server-only-secret');
        config()->set('services.elevenlabs.voice_id', 'abcd1234voice5678wxyz');
        config()->set('services.elevenlabs.model_id', 'eleven_multilingual_v2');
        Queue::fake();
        Storage::fake('public');

        $article = app(ArticleCatalog::class)->findByKey('ai-value');
        $this->assertNotNull($article);
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');
        $narration = ArticleNarration::factory()->approved()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'source_hash' => hash('sha256', $source),
            'script' => $source,
        ]);
        $narration->updateSample('eleven_multilingual_v2', [
            'status' => 'ready',
            'script_hash' => $narration->scriptFingerprint(),
            'voice_id' => 'abcd1234voice5678wxyz',
            'disk' => 'public',
            'path' => 'article-audio/samples/ar/current-v2.mp3',
        ]);
    }

    public function test_authorized_editor_can_queue_arabic_article_audio(): void
    {
        $editor = $this->userWithPermissions([
            'view_any articles',
            'update articles',
        ]);

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.generate', [
                'article' => 'ai-value',
                'locale' => 'ar',
            ]))
            ->assertRedirect(ManageArticleAudio::getUrl());

        $audio = ArticleAudio::query()->firstOrFail();

        $this->assertSame('ai-value', $audio->article_key);
        $this->assertSame('ar', $audio->locale);
        $this->assertSame($editor->getKey(), $audio->requested_by_user_id);
        $this->assertSame(ArticleAudioStatus::Queued, $audio->status);
        $this->assertNotNull($audio->queued_at);
        $this->assertSame(64, strlen((string) $audio->content_hash));

        Queue::assertPushed(GenerateArticleAudio::class, fn (GenerateArticleAudio $job): bool => $job->articleKey === 'ai-value' && $job->locale === 'ar');
    }

    public function test_guest_is_redirected_and_viewer_without_edit_permission_is_forbidden(): void
    {
        $url = route('filament.admin.article-audio.generate', [
            'article' => 'ai-value',
            'locale' => 'ar',
        ]);

        $this->post($url)->assertRedirect();

        $viewer = $this->userWithPermissions(['view_any articles']);

        $this->actingAs($viewer)->post($url)->assertForbidden();
        Queue::assertNothingPushed();
    }

    public function test_invalid_article_or_locale_is_not_queued(): void
    {
        $editor = $this->userWithPermissions([
            'view_any articles',
            'update articles',
        ]);

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.generate', [
                'article' => 'missing-article',
                'locale' => 'ar',
            ]))
            ->assertNotFound();

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.generate', [
                'article' => 'ai-value',
                'locale' => 'fr',
            ]))
            ->assertNotFound();

        Queue::assertNothingPushed();
        $this->assertDatabaseEmpty('article_audio');
    }

    public function test_missing_environment_configuration_does_not_queue_generation(): void
    {
        config()->set('services.elevenlabs.api_key', null);
        $editor = $this->userWithPermissions([
            'view_any articles',
            'update articles',
        ]);

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.generate', [
                'article' => 'ai-value',
                'locale' => 'ar',
            ]))
            ->assertRedirect(ManageArticleAudio::getUrl());

        Queue::assertNothingPushed();
        $this->assertDatabaseEmpty('article_audio');
    }

    public function test_duplicate_click_does_not_dispatch_a_second_generation_job(): void
    {
        $editor = $this->userWithPermissions([
            'view_any articles',
            'update articles',
        ]);
        ArticleAudio::factory()->processing()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
        ]);

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.generate', [
                'article' => 'ai-value',
                'locale' => 'ar',
            ]))
            ->assertRedirect(ManageArticleAudio::getUrl());

        Queue::assertNothingPushed();
    }

    public function test_stalled_generation_can_be_queued_again_after_its_unique_lock_window(): void
    {
        $editor = $this->userWithPermissions([
            'view_any articles',
            'update articles',
        ]);
        ArticleAudio::factory()->processing()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'generation_started_at' => now()->subMinutes(30),
        ]);

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.generate', [
                'article' => 'ai-value',
                'locale' => 'ar',
            ]))
            ->assertRedirect(ManageArticleAudio::getUrl());

        Queue::assertPushed(GenerateArticleAudio::class, 1);
        $this->assertDatabaseHas('article_audio', [
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'status' => ArticleAudioStatus::Queued->value,
        ]);
    }

    public function test_admin_page_masks_configuration_and_never_renders_api_key(): void
    {
        $editor = $this->userWithPermissions([
            'view_any articles',
            'update articles',
        ]);

        $this->actingAs($editor)
            ->get(ManageArticleAudio::getUrl())
            ->assertOk()
            ->assertSee('abcd…wxyz', false)
            ->assertDontSee('server-only-secret', false);
    }

    public function test_admin_page_explains_a_payment_failure_without_rendering_the_raw_provider_error(): void
    {
        $editor = $this->userWithPermissions([
            'view_any articles',
            'update articles',
        ]);
        ArticleAudio::factory()->failed()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'last_error' => 'ElevenLabs speech generation failed with HTTP 402 (request unavailable).',
        ]);

        $this->actingAs($editor)
            ->get(ManageArticleAudio::getUrl())
            ->assertOk()
            ->assertSee(__('article_audio.errors.insufficient_credits'))
            ->assertDontSee('request unavailable');
    }

    /** @param list<string> $permissions */
    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create([
            'name' => fake()->unique()->slug(),
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
