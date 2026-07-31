<?php

namespace Tests\Feature\ArticleAudio;

use App\Enums\ArticleAudioStatus;
use App\Filament\Pages\ManageArticleAudio;
use App\Models\ArticleAudio;
use App\Models\User;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Services\ArticleAudio\StoreUploadedArticleAudio;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Mockery;
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

    public function test_editor_can_upload_audio_through_the_native_filament_action(): void
    {
        $editor = $this->editor();
        config()->set('livewire.temporary_file_upload.disk', 'public');

        Livewire::actingAs($editor)
            ->test(ManageArticleAudio::class)
            ->assertActionExists('uploadAudio')
            ->callAction('uploadAudio', [
                'audio' => UploadedFile::fake()->create('editor-narration.mp3', 256, 'audio/mpeg'),
            ], [
                'article_key' => 'ai-product-moat',
                'locale' => 'ar',
            ])
            ->assertHasNoActionErrors();

        $audio = ArticleAudio::query()
            ->where('article_key', 'ai-product-moat')
            ->where('locale', 'ar')
            ->firstOrFail();

        $this->assertSame(ArticleAudio::SOURCE_UPLOADED, $audio->source_type);
        $this->assertSame(ArticleAudioStatus::Ready, $audio->status);
        Storage::disk('public')->assertExists($audio->path);
    }

    public function test_native_upload_keeps_the_existing_one_hundred_megabyte_limit(): void
    {
        $this->assertSame([
            'required',
            'file',
            'max:102400',
        ], config('livewire.temporary_file_upload.rules'));
    }

    public function test_native_filament_upload_rejects_a_non_audio_file(): void
    {
        $editor = $this->editor();

        Livewire::actingAs($editor)
            ->test(ManageArticleAudio::class)
            ->callAction('uploadAudio', [
                'audio' => UploadedFile::fake()->create('notes.txt', 256, 'text/plain'),
            ], [
                'article_key' => 'ai-product-moat',
                'locale' => 'ar',
            ])
            ->assertHasActionErrors(['audio']);

        $this->assertDatabaseMissing('article_audio', [
            'article_key' => 'ai-product-moat',
            'locale' => 'ar',
        ]);
    }

    public function test_native_upload_reads_remote_temporary_files_from_a_stream(): void
    {
        $article = app(ArticleCatalog::class)->findByKey('ai-product-moat');
        $this->assertNotNull($article);

        $contents = 'remote-temporary-audio';
        $stream = fopen('php://temp', 'w+b');
        $this->assertIsResource($stream);
        fwrite($stream, $contents);
        rewind($stream);

        $file = Mockery::mock(TemporaryUploadedFile::class);
        $file->shouldReceive('readStream')->once()->andReturn($stream);
        $file->shouldReceive('getRealPath')->never();
        $file->shouldReceive('getMimeType')->atLeast()->once()->andReturn('audio/mpeg');
        $file->shouldReceive('storeAs')
            ->once()
            ->andReturnUsing(function (string $directory, string $name, string $disk) use ($contents): string {
                $path = trim($directory.'/'.$name, '/');
                Storage::disk($disk)->put($path, $contents);

                return $path;
            });

        $audio = app(StoreUploadedArticleAudio::class)->handle($article, 'ar', $file, null);

        $this->assertNotNull($audio);
        $this->assertSame(ArticleAudio::SOURCE_UPLOADED, $audio->source_type);
        Storage::disk('public')->assertExists($audio->path);
    }

    public function test_read_only_editor_cannot_access_the_native_filament_upload_action(): void
    {
        $viewer = $this->viewer();

        Livewire::actingAs($viewer)
            ->test(ManageArticleAudio::class)
            ->assertActionHidden('uploadAudio');
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

    private function viewer(): User
    {
        $role = Role::create([
            'name' => fake()->unique()->slug(),
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(['view_any articles']);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
