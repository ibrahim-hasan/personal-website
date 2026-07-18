<?php

namespace Tests\Feature\Console\Commands;

use App\Jobs\GenerateArticleAudio;
use App\Jobs\GenerateArticleAudioSample;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleAudioScript;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\Article;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class QueueMissingArticleAudioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ArticleSeeder::class);
        Bus::fake();
        config()->set('services.elevenlabs.voice_id', 'arabic-editorial-voice');
        config()->set('services.elevenlabs.model_id', 'eleven_multilingual_v2');
    }

    public function test_it_queues_only_missing_audio_and_chains_a_missing_sample_before_its_track(): void
    {
        $catalog = app(ArticleCatalog::class);
        $modelId = 'eleven_multilingual_v2';
        $current = $catalog->findByKey('ai-value');
        $direct = $catalog->findByKey('ai-not-answer');
        $chained = $catalog->findByKey('transformation-before-software');

        $this->assertNotNull($current);
        $this->assertNotNull($direct);
        $this->assertNotNull($chained);

        $this->approvedNarration($current, withSample: true);
        $this->approvedNarration($direct, withSample: true);
        $this->approvedNarration($chained, withSample: false);

        $currentScript = app(ArticleAudioScript::class)->approved($current, 'ar', $modelId);
        $this->assertNotNull($currentScript);
        ArticleAudio::factory()->create([
            'article_key' => $current->key,
            'locale' => 'ar',
            'content_hash' => $currentScript->contentHash,
            'model_id' => $modelId,
        ]);

        $this->artisan('articles:queue-missing-audio', ['--locale' => 'ar'])
            ->expectsOutputToContain('Queued 1 full tracks and 1 sample-to-track pipelines')
            ->assertExitCode(0);

        Bus::assertDispatched(
            GenerateArticleAudio::class,
            fn (GenerateArticleAudio $job): bool => $job->articleKey === $direct->key && $job->locale === 'ar' && $job->modelId === $modelId,
        );
        Bus::assertNotDispatched(
            GenerateArticleAudio::class,
            fn (GenerateArticleAudio $job): bool => $job->articleKey === $current->key,
        );
        Bus::assertChained([
            GenerateArticleAudioSample::class,
            GenerateArticleAudio::class,
        ]);
    }

    private function approvedNarration(Article $article, bool $withSample): ArticleNarration
    {
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');
        $narration = ArticleNarration::factory()->approved()->create([
            'article_key' => $article->key,
            'locale' => 'ar',
            'source_hash' => hash('sha256', $source),
            'script' => $source,
        ]);

        if ($withSample) {
            $narration->updateSample('eleven_multilingual_v2', [
                'status' => 'ready',
                'script_hash' => $narration->scriptFingerprint(),
                'voice_id' => 'arabic-editorial-voice',
                'disk' => 'public',
                'path' => 'article-audio/samples/ar/'.$article->key.'.mp3',
            ]);
        }

        return $narration->fresh();
    }
}
