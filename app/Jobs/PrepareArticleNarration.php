<?php

namespace App\Jobs;

use App\Contracts\ArticleAudio\NarrationEditor;
use App\Enums\ArticleNarrationStatus;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PrepareArticleNarration implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 240;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 600;

    public function __construct(
        public readonly string $articleKey,
        public readonly string $locale,
    ) {
        $this->onConnection((string) config('services.openai.queue_connection', 'database'));
        $this->onQueue((string) config('services.openai.queue', 'article-audio'));
    }

    public function uniqueId(): string
    {
        return $this->articleKey.':'.$this->locale;
    }

    public function handle(
        ArticleCatalog $articles,
        ArticleNarrationScript $source,
        NarrationEditor $editor,
    ): void {
        $article = $articles->findByKey($this->articleKey);

        if ($article === null || ! in_array($this->locale, ['ar', 'en'], true)) {
            throw new RuntimeException('The requested editorial article could not be resolved.');
        }

        $sourceText = $source->build($article, $this->locale);
        $sourceHash = hash('sha256', $sourceText);
        $narration = ArticleNarration::query()->firstOrCreate(
            ['article_key' => $this->articleKey, 'locale' => $this->locale],
            ['status' => ArticleNarrationStatus::Queued, 'source_hash' => $sourceHash],
        );

        $narration->forceFill([
            'status' => ArticleNarrationStatus::Preparing,
            'preparation_started_at' => now(),
            'failed_at' => null,
            'last_error' => null,
        ])->save();

        $draft = $editor->prepare($sourceText, $this->locale);

        $narration->forceFill([
            'status' => ArticleNarrationStatus::Draft,
            'source_hash' => $sourceHash,
            'script' => $draft->script,
            'preparation_model' => $draft->model,
            'prompt_version' => $draft->promptVersion,
            'preparation_notes' => $draft->notes,
            'pronunciation_notes' => $draft->pronunciationNotes,
            'samples' => [],
            'prepared_at' => now(),
            'approved_at' => null,
            'failed_at' => null,
            'last_error' => null,
        ])->save();
    }

    public function failed(?Throwable $exception): void
    {
        $apiKey = (string) config('services.openai.api_key');
        $message = $exception?->getMessage() ?: 'Article narration preparation failed.';

        if ($apiKey !== '') {
            $message = str_replace($apiKey, '[redacted]', $message);
        }

        ArticleNarration::query()
            ->where('article_key', $this->articleKey)
            ->where('locale', $this->locale)
            ->first()
            ?->forceFill([
                'status' => ArticleNarrationStatus::Failed,
                'failed_at' => now(),
                'last_error' => Str::limit($message, 1000),
            ])->save();
    }
}
