<?php

namespace App\Services\ElevenLabs;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use JsonException;
use RuntimeException;

final class ElevenLabsSegmentCheckpointStore
{
    /**
     * @param  array<string, mixed>  $generationContext
     */
    public function key(array $generationContext): string
    {
        try {
            $encoded = json_encode($generationContext, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new RuntimeException('The ElevenLabs checkpoint context could not be encoded.', previous: $exception);
        }

        return hash('sha256', $encoded);
    }

    /**
     * @return array{audio: string, request_id: string}|null
     */
    public function restore(string $key, int $index, string $textHash): ?array
    {
        $disk = $this->disk();
        $audioPath = $this->audioPath($key, $index);
        $metadataPath = $this->metadataPath($key, $index);

        if (! $disk->exists($audioPath) || ! $disk->exists($metadataPath)) {
            return null;
        }

        try {
            $audio = $disk->get($audioPath);
            $metadata = json_decode($disk->get($metadataPath), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            $disk->delete([$audioPath, $metadataPath]);

            return null;
        }

        if (
            $audio === ''
            || ! is_array($metadata)
            || ! hash_equals($textHash, (string) ($metadata['text_hash'] ?? ''))
        ) {
            $disk->delete([$audioPath, $metadataPath]);

            return null;
        }

        return [
            'audio' => $audio,
            'request_id' => (string) ($metadata['request_id'] ?? 'unavailable'),
        ];
    }

    public function persist(string $key, int $index, string $textHash, string $audio, string $requestId): void
    {
        if ($audio === '') {
            throw new RuntimeException('An empty ElevenLabs segment cannot be checkpointed.');
        }

        $disk = $this->disk();
        $audioPath = $this->audioPath($key, $index);
        $metadataPath = $this->metadataPath($key, $index);
        $metadata = json_encode([
            'text_hash' => $textHash,
            'request_id' => $requestId,
            'completed_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR);

        $audioStored = $disk->put($audioPath, $audio);
        $metadataStored = $disk->put($metadataPath, $metadata);

        if (
            ! $audioStored
            || ! $metadataStored
            || ! $disk->exists($audioPath)
            || $disk->size($audioPath) === 0
        ) {
            $disk->delete([$audioPath, $metadataPath]);

            throw new RuntimeException('The generated ElevenLabs segment checkpoint could not be persisted.');
        }
    }

    public function forget(string $key): void
    {
        try {
            $this->disk()->deleteDirectory($this->directory($key));
        } catch (\Throwable $exception) {
            Log::warning('Unable to remove completed ElevenLabs segment checkpoints.', [
                'checkpoint_key' => $key,
                'exception' => $exception,
            ]);
        }
    }

    private function disk(): Filesystem
    {
        return Storage::disk((string) config('services.elevenlabs.checkpoint_disk', 'local'));
    }

    private function audioPath(string $key, int $index): string
    {
        return $this->directory($key).'/'.$this->segmentName($index).'.mp3';
    }

    private function metadataPath(string $key, int $index): string
    {
        return $this->directory($key).'/'.$this->segmentName($index).'.json';
    }

    private function directory(string $key): string
    {
        return 'article-audio/checkpoints/'.substr($key, 0, 2).'/'.$key;
    }

    private function segmentName(int $index): string
    {
        return str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
    }
}
