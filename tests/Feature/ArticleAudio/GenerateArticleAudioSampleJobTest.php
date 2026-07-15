<?php

namespace Tests\Feature\ArticleAudio;

use App\Jobs\GenerateArticleAudioSample;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
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
        Storage::disk('public')->assertExists($sample['path']);
        $this->assertNotNull($request);
        $this->assertSame('eleven_v3', $request['model_id']);
        $this->assertSame('ar', $request['language_code']);
        $this->assertStringContainsString('[thoughtful]', $request['text']);
        Http::assertSentCount(1);
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
}
