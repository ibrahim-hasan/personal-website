<?php

namespace App\Console\Commands;

use App\Enums\ArticleAudioStatus;
use App\Jobs\GenerateArticleAudio;
use App\Jobs\GenerateArticleAudioSample;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleAudioScript;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

#[Signature('articles:queue-missing-audio {--locale=* : Limit generation to one or more locales (ar, en)} {--model= : The configured ElevenLabs model ID to use} {--skip-samples : Generate full tracks directly without requiring or creating samples} {--skip-approval : Generate full tracks from current narration drafts without editorial approval}')]
#[Description('Queue full audio for published articles without a current voiced track')]
class QueueMissingArticleAudio extends Command
{
    public function handle(ArticleCatalog $articles, ArticleAudioScript $scripts): int
    {
        $locales = $this->locales();
        $modelId = trim((string) ($this->option('model') ?: config('services.elevenlabs.model_id')));
        $skipSamples = (bool) $this->option('skip-samples');
        $skipApproval = (bool) $this->option('skip-approval');

        if ($locales === null || ! $this->hasValidModel($modelId)) {
            return self::FAILURE;
        }

        $queued = 0;
        $samplePipelines = 0;
        $ready = 0;
        $active = 0;
        $awaitingNarration = 0;

        foreach ($articles->all() as $article) {
            foreach ($locales as $locale) {
                $script = $scripts->approved($article, $locale, $modelId, $skipApproval);

                if ($script === null) {
                    $awaitingNarration++;

                    continue;
                }

                $audio = ArticleAudio::query()
                    ->where('article_key', $article->key)
                    ->where('locale', $locale)
                    ->first();

                if ($audio?->isGenerating()) {
                    $active++;

                    continue;
                }

                if ($audio?->isReady() && ! $audio->isStale($script->contentHash)) {
                    $ready++;

                    continue;
                }

                $narration = ArticleNarration::query()->find($script->narrationId);

                if ($narration === null) {
                    $awaitingNarration++;

                    continue;
                }

                if (! $skipSamples && $narration->sampleIsGenerating($modelId)) {
                    $active++;

                    continue;
                }

                if (! $skipSamples && ! $narration->hasCurrentSample($modelId)) {
                    $narration->updateSample($modelId, [
                        'status' => 'queued',
                        'script_hash' => $narration->scriptFingerprint(),
                        'queued_at' => now()->toIso8601String(),
                        'failed_at' => null,
                        'last_error' => null,
                    ]);

                    Bus::chain([
                        new GenerateArticleAudioSample($article->key, $locale, $modelId),
                        new GenerateArticleAudio($article->key, $locale, $modelId),
                    ])
                        ->onConnection((string) config('services.elevenlabs.queue_connection', 'database'))
                        ->onQueue((string) config('services.elevenlabs.queue', 'article-audio'))
                        ->dispatch();
                    $samplePipelines++;

                    continue;
                }

                $audio ??= new ArticleAudio([
                    'article_key' => $article->key,
                    'locale' => $locale,
                ]);
                $audio->forceFill([
                    'status' => ArticleAudioStatus::Queued,
                    'content_hash' => $script->contentHash,
                    'model_id' => $modelId,
                    'queued_at' => now(),
                    'generation_started_at' => null,
                    'failed_at' => null,
                    'last_error' => null,
                ])->save();

                GenerateArticleAudio::dispatch($article->key, $locale, $modelId, $skipSamples, $skipApproval);
                $queued++;
            }
        }

        $this->components->info("Queued {$queued} full tracks and {$samplePipelines} sample-to-track pipelines; {$ready} already current; {$active} already in progress; {$awaitingNarration} awaiting an approved narration.");

        return self::SUCCESS;
    }

    /** @return list<string>|null */
    private function locales(): ?array
    {
        $requested = array_values(array_unique(array_filter(
            array_map('strval', (array) $this->option('locale')),
        )));
        $locales = $requested === [] ? ['ar', 'en'] : $requested;
        $invalid = array_diff($locales, ['ar', 'en']);

        if ($invalid !== []) {
            $this->components->error('Unsupported locale(s): '.implode(', ', $invalid).'. Use ar or en.');

            return null;
        }

        return $locales;
    }

    private function hasValidModel(string $modelId): bool
    {
        if (in_array($modelId, array_map('strval', array_keys((array) config('services.elevenlabs.models', []))), true)) {
            return true;
        }

        $this->components->error("Unsupported ElevenLabs model [{$modelId}].");

        return false;
    }
}
