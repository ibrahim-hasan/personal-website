<?php

namespace App\Jobs;

use App\Enums\ArticleAudioStatus;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleAudioScript;
use App\Services\ArticleAudio\Mp3Metadata;
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

class GenerateArticleAudio implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public bool $failOnTimeout = true;

    public int $uniqueFor;

    public function __construct(
        public readonly string $articleKey,
        public readonly string $locale,
        public readonly ?string $modelId = null,
        public readonly bool $skipSampleRequirement = false,
    ) {
        $this->timeout = ElevenLabsExecutionBudget::fullJobTimeout();
        $this->uniqueFor = ElevenLabsExecutionBudget::uniqueFor($this->timeout);
        $this->onConnection((string) config('services.elevenlabs.queue_connection', 'database'));
        $this->onQueue((string) config('services.elevenlabs.queue', 'article-audio'));
    }

    public function uniqueId(): string
    {
        return $this->articleKey.':'.$this->locale.':'.($this->modelId ?? 'configured');
    }

    public function handle(
        ArticleCatalog $articles,
        ArticleAudioScript $scripts,
        Mp3Metadata $metadata,
        ElevenLabsTextToSpeech $speech,
    ): void {
        $article = $articles->findByKey($this->articleKey);

        if ($article === null || ! in_array($this->locale, ['ar', 'en'], true)) {
            throw new RuntimeException('The requested editorial article could not be resolved.');
        }

        $audio = ArticleAudio::query()->firstOrCreate(
            ['article_key' => $this->articleKey, 'locale' => $this->locale],
            ['status' => ArticleAudioStatus::Queued, 'queued_at' => now()],
        );
        $modelId = $this->modelId ?: $audio->model_id ?: (string) config('services.elevenlabs.model_id');
        $script = $scripts->approved($article, $this->locale, $modelId);

        if ($script === null) {
            throw new RuntimeException('An approved current narration script is required.');
        }

        $narration = ArticleNarration::query()->find($script->narrationId);

        if ($narration === null) {
            throw new RuntimeException('An approved current narration script is required.');
        }

        if (! $this->skipSampleRequirement && ! $narration->hasCurrentSample($modelId)) {
            throw new RuntimeException('A current approved sample is required before full generation.');
        }

        $contentHash = $script->contentHash;
        $previousDisk = $audio->disk;
        $previousPath = $audio->path;

        $audio->forceFill([
            'status' => ArticleAudioStatus::Processing,
            'content_hash' => $contentHash,
            'model_id' => $modelId,
            'generation_started_at' => now(),
            'failed_at' => null,
            'last_error' => null,
        ])->save();

        $diskName = (string) config('services.elevenlabs.audio_disk', 'public');
        $path = sprintf(
            'article-audio/%s/%s-%s.mp3',
            $this->locale,
            $this->articleKey,
            substr($contentHash, 0, 12),
        );
        $stored = false;

        try {
            $result = $speech->synthesize($script->text, $this->locale, $modelId);
            $disk = Storage::disk($diskName);
            $stored = $disk->put($path, $result->audio, ['visibility' => 'public']);

            if (! $stored || ! $disk->exists($path) || $disk->size($path) === 0) {
                throw new RuntimeException('The generated MP3 could not be persisted.');
            }

            $audio->forceFill([
                'status' => ArticleAudioStatus::Ready,
                'disk' => $diskName,
                'path' => $path,
                'mime_type' => 'audio/mpeg',
                'file_size' => $disk->size($path),
                'duration_seconds' => $metadata->durationSeconds($result->audio),
                'character_count' => $result->characterCount,
                'segment_count' => $result->segmentCount,
                'content_hash' => $contentHash,
                'voice_id' => $speech->voiceId(),
                'model_id' => $modelId,
                'output_format' => (string) config('services.elevenlabs.output_format'),
                'voice_settings' => $speech->profile($modelId)['voice_settings'],
                'request_ids' => $result->requestIds,
                'generated_at' => now(),
                'failed_at' => null,
                'last_error' => null,
            ])->save();
            $speech->forgetCheckpoint($result->checkpointKey);

            if ($previousPath !== null && ($previousPath !== $path || $previousDisk !== $diskName)) {
                Storage::disk($previousDisk)->delete($previousPath);
            }
        } catch (Throwable $exception) {
            if ($stored && $path !== $previousPath) {
                Storage::disk($diskName)->delete($path);
            }

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $apiKey = (string) config('services.elevenlabs.api_key');
        $message = $exception?->getMessage() ?: 'Article audio generation failed.';

        if ($apiKey !== '') {
            $message = str_replace($apiKey, '[redacted]', $message);
        }

        ArticleAudio::query()
            ->where('article_key', $this->articleKey)
            ->where('locale', $this->locale)
            ->first()
            ?->forceFill([
                'status' => ArticleAudioStatus::Failed,
                'failed_at' => now(),
                'last_error' => Str::limit($message, 1000),
            ])->save();
    }
}
