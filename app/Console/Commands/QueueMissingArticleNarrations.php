<?php

namespace App\Console\Commands;

use App\Enums\ArticleNarrationStatus;
use App\Jobs\PrepareArticleNarration;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('articles:queue-missing-narrations {--locale=* : Limit generation to one or more locales (ar, en)}')]
#[Description('Queue narration-text preparation for published articles that have no current narration script')]
class QueueMissingArticleNarrations extends Command
{
    public function handle(ArticleCatalog $articles, ArticleNarrationScript $scripts): int
    {
        $locales = $this->locales();

        if ($locales === null) {
            return self::FAILURE;
        }

        $queued = 0;
        $current = 0;
        $active = 0;

        foreach ($articles->all() as $article) {
            foreach ($locales as $locale) {
                $sourceHash = $scripts->fingerprint($article, $locale);
                $narration = ArticleNarration::query()
                    ->where('article_key', $article->key)
                    ->where('locale', $locale)
                    ->first();

                if ($narration?->isPreparing()) {
                    $active++;

                    continue;
                }

                if ($this->hasCurrentScript($narration, $sourceHash)) {
                    $current++;

                    continue;
                }

                $narration ??= new ArticleNarration([
                    'article_key' => $article->key,
                    'locale' => $locale,
                ]);

                $narration->forceFill([
                    'status' => ArticleNarrationStatus::Queued,
                    'source_hash' => $sourceHash,
                    'preparation_started_at' => null,
                    'failed_at' => null,
                    'last_error' => null,
                ])->save();

                PrepareArticleNarration::dispatch($article->key, $locale);
                $queued++;
            }
        }

        $this->components->info("Queued {$queued} narration preparations; {$current} already current; {$active} already in progress.");

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

    private function hasCurrentScript(?ArticleNarration $narration, string $sourceHash): bool
    {
        return $narration !== null
            && filled($narration->script)
            && in_array($narration->status, [ArticleNarrationStatus::Draft, ArticleNarrationStatus::Approved], true)
            && hash_equals((string) $narration->source_hash, $sourceHash);
    }
}
