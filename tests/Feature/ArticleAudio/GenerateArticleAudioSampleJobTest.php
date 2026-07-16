<?php

namespace Tests\Feature\ArticleAudio;

use App\Jobs\GenerateArticleAudioSample;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Ai\ElevenLabsExecutionBudget;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class GenerateArticleAudioSampleJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ArticleSeeder::class);
        Storage::fake('public');
        config()->set('services.elevenlabs.api_key', 'eleven-server-secret');
        config()->set('services.elevenlabs.voice_id', 'arabic-editorial-voice');
        config()->set('services.elevenlabs.base_url', 'https://api.elevenlabs.test/v1');
        config()->set('services.elevenlabs.audio_disk', 'public');
        config()->set('services.elevenlabs.sample_characters', 650);
    }

    public function test_job_generates_and_stores_a_short_model_specific_sample(): void
    {
        $article = app(ArticleCatalog::class)->findByKey('ai-value');
        $this->assertNotNull($article);
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');
        $narration = ArticleNarration::factory()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'source_hash' => hash('sha256', $source),
            'script' => '[thoughtful] '.$source,
            'samples' => [],
        ]);
        $request = null;
        $segment = "\xFF\xFB".str_repeat('S', 500);

        Http::preventStrayRequests();
        Http::fake(function (Request $sent) use (&$request, $segment) {
            $request = $sent;

            return Http::response($segment, 200, [
                'Content-Type' => 'audio/mpeg',
                'request-id' => 'sample-request-1',
            ]);
        });

        $job = new GenerateArticleAudioSample('ai-value', 'ar', 'eleven_v3');
        app()->call([$job, 'handle']);

        $narration->refresh();
        $sample = $narration->sample('eleven_v3');

        $this->assertNotNull($sample);
        $this->assertSame('ready', $sample['status']);
        $this->assertSame($narration->scriptFingerprint(), $sample['script_hash']);
        $this->assertLessThanOrEqual(650, $sample['character_count']);
        $this->assertSame(['sample-request-1'], $sample['request_ids']);
        $this->assertSame('arabic-editorial-voice', $sample['voice_id']);
        Storage::disk('public')->assertExists($sample['path']);
        $this->assertNotNull($request);
        $this->assertSame('eleven_v3', $request['model_id']);
        $this->assertSame('ar', $request['language_code']);
        $this->assertStringContainsString('[thoughtful]', $request['text']);
        Http::assertSentCount(1);
    }

    public function test_job_timeout_and_unique_lock_cover_the_shared_request_budget(): void
    {
        $job = new GenerateArticleAudioSample('ai-value', 'ar', 'eleven_v3');

        $this->assertSame(ElevenLabsExecutionBudget::sampleJobTimeout('eleven_v3'), $job->timeout);
        $this->assertSame(ElevenLabsExecutionBudget::uniqueFor($job->timeout), $job->uniqueFor);
        $this->assertSame(1, $job->tries);
        $this->assertGreaterThan($job->timeout, $job->uniqueFor);
    }

    public function test_stale_sample_models_merge_against_the_locked_current_row(): void
    {
        $narration = ArticleNarration::factory()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'samples' => [],
        ]);
        $firstWorker = ArticleNarration::query()->findOrFail($narration->getKey());
        $secondWorker = ArticleNarration::query()->findOrFail($narration->getKey());

        $firstWorker->updateSample('eleven_v3', [
            'status' => 'ready',
            'path' => 'article-audio/samples/ar/v3.mp3',
        ]);
        $secondWorker->updateSample('eleven_multilingual_v2', [
            'status' => 'ready',
            'path' => 'article-audio/samples/ar/v2.mp3',
        ]);

        $fresh = $narration->refresh();

        $this->assertSame('article-audio/samples/ar/v3.mp3', data_get($fresh->samples, 'eleven_v3.path'));
        $this->assertSame('article-audio/samples/ar/v2.mp3', data_get($fresh->samples, 'eleven_multilingual_v2.path'));
    }

    public function test_new_sample_file_is_deleted_when_database_persistence_fails(): void
    {
        $article = app(ArticleCatalog::class)->findByKey('ai-value');
        $this->assertNotNull($article);
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');
        $oldPath = 'article-audio/samples/ar/existing-v3.mp3';
        Storage::disk('public')->put($oldPath, 'existing-sample');
        ArticleNarration::factory()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'source_hash' => hash('sha256', $source),
            'script' => $source,
            'samples' => [
                'eleven_v3' => [
                    'status' => 'ready',
                    'path' => $oldPath,
                    'disk' => 'public',
                ],
            ],
        ]);
        $segment = "\xFF\xFB".str_repeat('S', 500);

        Http::preventStrayRequests();
        Http::fake(fn () => Http::response($segment, 200, ['Content-Type' => 'audio/mpeg']));
        ArticleNarration::saving(function (ArticleNarration $narration): void {
            if (data_get($narration->samples, 'eleven_v3.status') === 'ready'
                && data_get($narration->samples, 'eleven_v3.path') !== 'article-audio/samples/ar/existing-v3.mp3') {
                throw new RuntimeException('Simulated sample metadata persistence failure.');
            }
        });

        try {
            $job = new GenerateArticleAudioSample('ai-value', 'ar', 'eleven_v3');
            app()->call([$job, 'handle']);
            $this->fail('The simulated metadata persistence failure should escape the job.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated sample metadata persistence failure.', $exception->getMessage());
        } finally {
            ArticleNarration::flushEventListeners();
            ArticleNarration::clearBootedModels();
        }

        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame([$oldPath], Storage::disk('public')->allFiles('article-audio/samples'));
    }

    public function test_failed_sample_records_a_sanitized_error(): void
    {
        $narration = ArticleNarration::factory()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'samples' => [
                'eleven_v3' => ['status' => 'processing'],
            ],
        ]);

        $job = new GenerateArticleAudioSample('ai-value', 'ar', 'eleven_v3');
        $job->failed(new RuntimeException('Provider rejected eleven-server-secret.'));

        $sample = $narration->refresh()->sample('eleven_v3');

        $this->assertSame('failed', $sample['status']);
        $this->assertStringContainsString('[redacted]', $sample['last_error']);
        $this->assertStringNotContainsString('eleven-server-secret', $sample['last_error']);
    }

    public function test_failed_callback_does_not_replace_a_newer_ready_sample(): void
    {
        $narration = ArticleNarration::factory()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'samples' => [
                'eleven_v3' => [
                    'status' => 'ready',
                    'path' => 'article-audio/samples/ar/current.mp3',
                ],
            ],
        ]);

        $job = new GenerateArticleAudioSample('ai-value', 'ar', 'eleven_v3');
        $job->failed(new RuntimeException('An older worker failed late.'));

        $sample = $narration->refresh()->sample('eleven_v3');

        $this->assertSame('ready', $sample['status']);
        $this->assertSame('article-audio/samples/ar/current.mp3', $sample['path']);
        $this->assertArrayNotHasKey('last_error', $sample);
    }
}
