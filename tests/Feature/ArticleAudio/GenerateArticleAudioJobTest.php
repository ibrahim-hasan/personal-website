<?php

namespace Tests\Feature\ArticleAudio;

use App\Enums\ArticleAudioStatus;
use App\Jobs\GenerateArticleAudio;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleAudioScript;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class GenerateArticleAudioJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ArticleSeeder::class);
        Storage::fake('public');
        Storage::fake('local');
        config()->set('services.elevenlabs.api_key', 'server-secret');
        config()->set('services.elevenlabs.voice_id', 'calm-arabic-voice');
        config()->set('services.elevenlabs.base_url', 'https://api.elevenlabs.test/v1');
        config()->set('services.elevenlabs.audio_disk', 'public');
        config()->set('services.elevenlabs.checkpoint_disk', 'local');
        config()->set('services.elevenlabs.max_characters', 9000);
        config()->set('services.elevenlabs.model_id', 'eleven_multilingual_v2');
    }

    public function test_job_generates_stores_and_associates_a_fresh_mp3(): void
    {
        $articles = app(ArticleCatalog::class);
        $article = $articles->findByKey('ai-value');
        $this->assertNotNull($article);
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');
        $narration = ArticleNarration::factory()->approved()->create([
            'article_key' => $article->key,
            'locale' => 'ar',
            'source_hash' => hash('sha256', $source),
            'script' => $source,
        ]);
        $narration->updateSample('eleven_multilingual_v2', [
            'status' => 'ready',
            'script_hash' => $narration->scriptFingerprint(),
            'voice_id' => 'calm-arabic-voice',
            'disk' => 'public',
            'path' => 'article-audio/samples/ar/current-v2.mp3',
        ]);

        Storage::disk('public')->put('article-audio/ar/old.mp3', 'old-audio');
        ArticleAudio::factory()->create([
            'article_key' => $article->key,
            'locale' => 'ar',
            'path' => 'article-audio/ar/old.mp3',
            'content_hash' => str_repeat('0', 64),
        ]);

        $segment = "\xFF\xFB".str_repeat('A', 600);
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response($segment, 200, [
            'Content-Type' => 'audio/mpeg',
            'request-id' => 'eleven-request',
        ]));

        $job = new GenerateArticleAudio($article->key, 'ar', 'eleven_multilingual_v2');
        app()->call([$job, 'handle']);

        $audio = ArticleAudio::query()->where('article_key', $article->key)->where('locale', 'ar')->firstOrFail();
        $expectedHash = app(ArticleAudioScript::class)
            ->approved($article, 'ar', 'eleven_multilingual_v2')
            ?->contentHash;

        $this->assertSame(ArticleAudioStatus::Ready, $audio->status);
        $this->assertSame($expectedHash, $audio->content_hash);
        $this->assertSame('audio/mpeg', $audio->mime_type);
        $this->assertSame('calm-arabic-voice', $audio->voice_id);
        $this->assertSame('eleven_multilingual_v2', $audio->model_id);
        $this->assertGreaterThan(9000, $audio->character_count);
        $this->assertSame(2, $audio->segment_count);
        $this->assertNotNull($audio->generated_at);
        $this->assertNull($audio->last_error);
        Storage::disk('public')->assertExists($audio->path);
        Storage::disk('public')->assertMissing('article-audio/ar/old.mp3');
        $this->assertSame([], Storage::disk('local')->allFiles('article-audio/checkpoints'));
        Http::assertSentCount(2);
    }

    public function test_job_uses_the_long_form_timeout_and_holds_its_unique_lock_longer(): void
    {
        config()->set('services.elevenlabs.job_timeout', 1560);
        config()->set('services.elevenlabs.unique_for', 1800);
        config()->set('queue.connections.database.retry_after', 1620);

        $job = new GenerateArticleAudio('ai-value', 'ar', 'eleven_v3');

        $this->assertSame(1560, $job->timeout);
        $this->assertSame(1800, $job->uniqueFor);
        $this->assertGreaterThan($job->timeout, $job->uniqueFor);
    }

    public function test_job_can_generate_full_audio_without_a_sample_or_approval_when_explicitly_requested(): void
    {
        $article = app(ArticleCatalog::class)->findByKey('ai-value');
        $this->assertNotNull($article);
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');
        ArticleNarration::factory()->create([
            'article_key' => $article->key,
            'locale' => 'ar',
            'source_hash' => hash('sha256', $source),
            'script' => $source,
        ]);
        $segment = "\xFF\xFB".str_repeat('A', 600);
        Http::preventStrayRequests();
        Http::fake(fn () => Http::response($segment, 200, [
            'Content-Type' => 'audio/mpeg',
            'request-id' => 'eleven-request',
        ]));
        $job = new GenerateArticleAudio($article->key, 'ar', 'eleven_multilingual_v2', true, true);
        app()->call([$job, 'handle']);
        $audio = ArticleAudio::query()->where('article_key', $article->key)->where('locale', 'ar')->firstOrFail();
        $this->assertSame(ArticleAudioStatus::Ready, $audio->status);
        Http::assertSentCount(2);
    }

    public function test_failed_job_records_a_sanitized_error_without_deleting_previous_audio(): void
    {
        Storage::disk('public')->put('article-audio/ar/previous.mp3', 'previous-audio');
        $audio = ArticleAudio::factory()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'path' => 'article-audio/ar/previous.mp3',
        ]);

        $job = new GenerateArticleAudio('ai-value', 'ar');
        $job->failed(new RuntimeException('Provider rejected server-secret while generating.'));

        $audio->refresh();

        $this->assertSame(ArticleAudioStatus::Failed, $audio->status);
        $this->assertNotNull($audio->failed_at);
        $this->assertStringContainsString('[redacted]', $audio->last_error);
        $this->assertStringNotContainsString('server-secret', $audio->last_error);
        Storage::disk('public')->assertExists('article-audio/ar/previous.mp3');
    }
}
