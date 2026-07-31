<?php

namespace App\Http\Controllers\Filament;

use App\Enums\ArticleAudioStatus;
use App\Filament\Pages\ManageArticleAudio;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadArticleAudioRequest;
use App\Models\ArticleAudio;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Services\ArticleAudio\Mp3Metadata;
use App\Support\Editorial\ArticleCatalog;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UploadArticleAudioController extends Controller
{
    public function __invoke(
        UploadArticleAudioRequest $request,
        string $article,
        string $locale,
        ArticleCatalog $articles,
        ArticleNarrationScript $source,
        Mp3Metadata $metadata,
    ): RedirectResponse {
        $resolvedArticle = $articles->findByKey($article);

        abort_if($resolvedArticle === null || ! in_array($locale, ['ar', 'en'], true), 404);

        $audio = ArticleAudio::query()->firstOrNew([
            'article_key' => $article,
            'locale' => $locale,
        ]);

        if ($audio->exists && $audio->isGenerating()) {
            Notification::make()
                ->title(__('article_audio.notifications.already_generating'))
                ->info()
                ->send();

            return redirect(ManageArticleAudio::getUrl());
        }

        /** @var UploadedFile $file */
        $file = $request->file('audio');
        $diskName = (string) config('services.elevenlabs.audio_disk', 'public');
        $fileHash = hash_file('sha256', (string) $file->getRealPath());
        $extension = $this->extension($file);
        $path = sprintf(
            'article-audio/%s/%s-uploaded-%s.%s',
            $locale,
            $article,
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
                'requested_by_user_id' => $request->user()?->getKey(),
                'source_type' => ArticleAudio::SOURCE_UPLOADED,
                'status' => ArticleAudioStatus::Ready,
                'disk' => $diskName,
                'path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'file_size' => $disk->size($path),
                'duration_seconds' => $metadata->durationSeconds(
                    file_get_contents((string) $file->getRealPath()) ?: '',
                ),
                'character_count' => null,
                'segment_count' => null,
                'content_hash' => $source->fingerprint($resolvedArticle, $locale),
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

        Notification::make()
            ->title(__('article_audio.notifications.uploaded'))
            ->success()
            ->send();

        return redirect(ManageArticleAudio::getUrl());
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
}
