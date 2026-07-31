<?php

namespace App\Services\ArticleAudio;

use App\Enums\ArticleAudioStatus;
use App\Models\ArticleAudio;
use App\Support\Editorial\Article;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StoreUploadedArticleAudio
{
    public function __construct(
        private readonly ArticleNarrationScript $source,
        private readonly Mp3Metadata $metadata,
    ) {}

    public function handle(
        Article $article,
        string $locale,
        UploadedFile $file,
        int|string|null $requestedByUserId,
    ): ?ArticleAudio {
        $audio = ArticleAudio::query()->firstOrNew([
            'article_key' => $article->key,
            'locale' => $locale,
        ]);

        if ($audio->exists && $audio->isGenerating()) {
            return null;
        }

        $inspectionPath = $this->copyToTemporaryPath($file);

        try {
            $durationSeconds = $this->metadata->durationSecondsFromPath($inspectionPath);
            $fileHash = hash_file('sha256', $inspectionPath);

            if ($fileHash === false) {
                throw new RuntimeException('The uploaded article audio could not be fingerprinted.');
            }

            $diskName = (string) config('services.elevenlabs.audio_disk', 'public');
            $extension = $this->extension($file);
            $path = sprintf(
                'article-audio/%s/%s-uploaded-%s.%s',
                $locale,
                $article->key,
                substr($fileHash, 0, 16),
                $extension,
            );
            $disk = Storage::disk($diskName);
            $storedPath = $file->storeAs(dirname($path), basename($path), $diskName);

            if ($storedPath === false || ! $disk->exists($path)) {
                throw new RuntimeException('The uploaded article audio could not be persisted.');
            }

            $previousDisk = $audio->disk;
            $previousPath = $audio->path;

            try {
                $audio->forceFill([
                    'requested_by_user_id' => $requestedByUserId,
                    'source_type' => ArticleAudio::SOURCE_UPLOADED,
                    'status' => ArticleAudioStatus::Ready,
                    'disk' => $diskName,
                    'path' => $path,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'file_size' => $disk->size($path),
                    'duration_seconds' => $durationSeconds,
                    'character_count' => null,
                    'segment_count' => null,
                    'content_hash' => $this->source->fingerprint($article, $locale),
                    'voice_id' => null,
                    'model_id' => null,
                    'output_format' => $extension,
                    'voice_settings' => null,
                    'request_ids' => null,
                    'queued_at' => null,
                    'generation_started_at' => null,
                    'generated_at' => now(),
                    'failed_at' => null,
                    'last_error' => null,
                ])->save();
            } catch (Throwable $exception) {
                if ($previousPath !== $path || $previousDisk !== $diskName) {
                    $disk->delete($path);
                }

                throw $exception;
            }

            if ($previousPath !== null && ($previousPath !== $path || $previousDisk !== $diskName)) {
                Storage::disk($previousDisk)->delete($previousPath);
            }

            return $audio;
        } finally {
            @unlink($inspectionPath);
        }
    }

    private function extension(UploadedFile $file): string
    {
        return match (strtolower((string) $file->getMimeType())) {
            'audio/mpeg' => 'mp3',
            'audio/wav', 'audio/x-wav', 'audio/wave' => 'wav',
            'audio/ogg' => 'ogg',
            'audio/mp4', 'audio/x-m4a' => 'm4a',
            'audio/webm' => 'webm',
            default => strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'mp3')),
        };
    }

    private function copyToTemporaryPath(UploadedFile $file): string
    {
        $input = method_exists($file, 'readStream')
            ? $file->readStream()
            : @fopen((string) $file->getRealPath(), 'rb');

        if (! is_resource($input)) {
            throw new RuntimeException('The uploaded article audio could not be read.');
        }

        $inspectionPath = tempnam(sys_get_temp_dir(), 'article-audio-upload-');

        if ($inspectionPath === false) {
            fclose($input);

            throw new RuntimeException('The uploaded article audio could not be prepared.');
        }

        $output = fopen($inspectionPath, 'wb');

        if ($output === false) {
            fclose($input);
            @unlink($inspectionPath);

            throw new RuntimeException('The uploaded article audio could not be prepared.');
        }

        try {
            $copied = stream_copy_to_stream($input, $output);
        } finally {
            fclose($input);
            fclose($output);
        }

        if ($copied === false) {
            @unlink($inspectionPath);

            throw new RuntimeException('The uploaded article audio could not be read.');
        }

        return $inspectionPath;
    }
}
