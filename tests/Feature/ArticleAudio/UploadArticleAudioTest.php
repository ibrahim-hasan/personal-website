<?php

namespace Tests\Feature\ArticleAudio;

use App\Enums\ArticleAudioStatus;
use App\Filament\Pages\ManageArticleAudio;
use App\Models\ArticleAudio;
use App\Models\User;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UploadArticleAudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([ArticleSeeder::class, PermissionSeeder::class]);
        Storage::fake('public');
        config()->set('services.elevenlabs.audio_disk', 'public');
    }

    public function test_editor_can_upload_audio_without_ai_or_provider_configuration(): void
    {
        config()->set('ai.providers.openai.key', null);
        config()->set('services.elevenlabs.api_key', null);

        $editor = $this->editor();

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.upload', [
                'article' => 'ai-product-moat',
                'locale' => 'ar',
            ]), [
                'audio' => UploadedFile::fake()->create('my-narration.mp3', 256, 'audio/mpeg'),
            ])
            ->assertRedirect(ManageArticleAudio::getUrl());

        $audio = ArticleAudio::query()->where('article_key', 'ai-product-moat')->where('locale', 'ar')->firstOrFail();
        $article = app(ArticleCatalog::class)->findByKey('ai-product-moat');
        $this->assertNotNull($article);

        $this->assertSame(ArticleAudio::SOURCE_UPLOADED, $audio->source_type);
        $this->assertSame(ArticleAudioStatus::Ready, $audio->status);
        $this->assertSame(
            app(ArticleNarrationScript::class)->fingerprint($article, 'ar'),
            $audio->content_hash,
        );
        $this->assertSame('audio/mpeg', $audio->mime_type);
        Storage::disk('public')->assertExists($audio->path);
    }

    public function test_upload_replaces_the_previous_track_and_rejects_non_audio_files(): void
    {
        $editor = $this->editor();
        $oldPath = 'article-audio/ar/old-track.mp3';
        Storage::disk('public')->put($oldPath, 'old-audio');
        ArticleAudio::factory()->create([
            'article_key' => 'ai-product-moat',
            'locale' => 'ar',
            'path' => $oldPath,
        ]);

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.upload', [
                'article' => 'ai-product-moat',
                'locale' => 'ar',
            ]), [
                'audio' => UploadedFile::fake()->create('my-narration.wav', 256, 'audio/wav'),
            ])
            ->assertRedirect(ManageArticleAudio::getUrl());

        $audio = ArticleAudio::query()->where('article_key', 'ai-product-moat')->where('locale', 'ar')->firstOrFail();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($audio->path);
        $this->assertSame(ArticleAudio::SOURCE_UPLOADED, $audio->source_type);

        $this->actingAs($editor)
            ->post(route('filament.admin.article-audio.upload', [
                'article' => 'ai-product-moat',
                'locale' => 'ar',
            ]), [
                'audio' => UploadedFile::fake()->create('notes.txt', 256, 'text/plain'),
            ])
            ->assertSessionHasErrors('audio');

        $this->assertSame($audio->path, ArticleAudio::query()->findOrFail($audio->getKey())->path);
    }

    private function editor(): User
    {
        $role = Role::create([
            'name' => fake()->unique()->slug(),
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(['update articles', 'view_any articles']);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
