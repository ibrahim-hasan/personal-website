<?php

namespace App\Jobs;

use App\Enums\ArticleNarrationStatus;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Services\ArticleAudio\NarrationMarkup;
use App\Services\ArticleAudio\NarrationSampleExcerpt;
use App\Services\ElevenLabs\ElevenLabsTextToSpeech;
use App\Support\Ai\ElevenLabsExecutionBudget;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GenerateArticleAudioSample implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public int $uniqueFor;

    public function __construct(
        public readonly string $articleKey,
        public readonly string $locale,
        public readonly string $modelId,
    ) {
        $this->timeout = ElevenLabsExecutionBudget::sampleJobTimeout($this->modelId);
        $this->uniqueFor = ElevenLabsExecutionBudget::uniqueFor($this->timeout);
        $this->onConnection((string) config('services.elevenlabs.queue_connection', 'database'));
        $this->onQueue((string) config('services.elevenlabs.queue', 'article-audio'));
    }

    public function uniqueId(): string
    {
        return $this->articleKey.':'.$this->locale.':'.$this->modelId;
    }

    public function handle(
        ArticleCatalog $articles,
        ArticleNarrationScript $source,
        NarrationMarkup $markup,
        NarrationSampleExcerpt $excerpt,
        ElevenLabsTextToSpeech $speech,
    ): void {
        $article = $articles->findByKey($this->articleKey);

        if ($article === null || ! in_array($this->locale, ['ar', 'en'], true)) {
            throw new RuntimeException('The requested editorial article could not be resolved.');
        }

        $sourceHash = $source->fingerprint($article, $this->locale);
        $narration = ArticleNarration::query()
            ->where('article_key', $this->articleKey)
            ->where('locale', $this->locale)
            ->whereIn('status', [ArticleNarrationStatus::Draft, ArticleNarrationStatus::Approved])
            ->where('source_hash', $sourceHash)
            ->whereNotNull('script')
            ->first();

        if ($narration === null || blank($narration->script)) {
            throw new RuntimeException('A current narration draft is required before generating a sample.');
        }

        $previousSample = $narration->sample($this->modelId);
        $scriptHash = $narration->scriptFingerprint();
        $narration->updateSample($this->modelId, [
            'status' => 'processing',
            'script_hash' => $scriptHash,
            'started_at' => now()->toIso8601String(),
            'failed_at' => null,
            'last_error' => null,
        ]);

        $prepared = $markup->forModel((string) $narration->script, $this->modelId);
        $sampleText = $excerpt->make($prepared, (int) config('services.elevenlabs.sample_characters', 650));
        $diskName = (string) config('services.elevenlabs.audio_disk', 'public');
        $path = sprintf(
            'article-audio/samples/%s/%s-%s-%s-%s.mp3',
            $this->locale,
            $this->articleKey,
            Str::slug($this->modelId),
            substr($scriptHash, 0, 12),
            Str::lower((string) Str::uuid()),
        );
        $disk = Storage::disk($diskName);
        $samplePersisted = false;

        try {
            $result = $speech->synthesize($sampleText, $this->locale, $this->modelId);
            $stored = $disk->put($path, $result->audio, ['visibility' => 'public']);

            if (! $stored || ! $disk->exists($path) || $disk->size($path) === 0) {
                throw new RuntimeException('The generated sample MP3 could not be persisted.');
            }

            $narration->updateSample($this->modelId, [
                'status' => 'ready',
                'disk' => $diskName,
                'path' => $path,
                'script_hash' => $scriptHash,
                'voice_id' => $speech->voiceId(),
                'character_count' => $result->characterCount,
                'segment_count' => $result->segmentCount,
                'request_ids' => $result->requestIds,
                'generated_at' => now()->toIso8601String(),
                'failed_at' => null,
                'last_error' => null,
            ]);
            $samplePersisted = true;
        } catch (Throwable $exception) {
            if (! $samplePersisted && $disk->exists($path)) {
                $disk->delete($path);
            }

            throw $exception;
        }

        $previousPath = is_array($previousSample) ? data_get($previousSample, 'path') : null;
        $previousDisk = is_array($previousSample) ? data_get($previousSample, 'disk', 'public') : null;

        if (is_string($previousPath) && $previousPath !== '' && ($previousPath !== $path || $previousDisk !== $diskName)) {
            Storage::disk((string) $previousDisk)->delete($previousPath);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $narration = ArticleNarration::query()
            ->where('article_key', $this->articleKey)
            ->where('locale', $this->locale)
            ->first();

        if ($narration === null) {
            return;
        }

        $apiKey = (string) config('services.elevenlabs.api_key');
        $message = $exception?->getMessage() ?: 'Article narration sample generation failed.';

        if ($apiKey !== '') {
            $message = str_replace($apiKey, '[redacted]', $message);
        }

        $narration->failSampleGeneration($this->modelId, [
            'status' => 'failed',
            'failed_at' => now()->toIso8601String(),
            'last_error' => Str::limit($message, 1000),
        ]);
    }
}
