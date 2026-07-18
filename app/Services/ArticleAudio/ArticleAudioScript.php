<?php

namespace App\Services\ArticleAudio;

use App\Data\ArticleAudio\ResolvedArticleAudioScript;
use App\Enums\ArticleNarrationStatus;
use App\Models\ArticleNarration;
use App\Support\Editorial\Article;

class ArticleAudioScript
{
    public function __construct(
        private readonly ArticleNarrationScript $source,
        private readonly NarrationMarkup $markup,
    ) {}

    public function approved(Article $article, string $locale, string $modelId, bool $allowCurrentDraft = false): ?ResolvedArticleAudioScript
    {
        $sourceHash = $this->source->fingerprint($article, $locale);
        $narration = ArticleNarration::query()
            ->where('article_key', $article->key)
            ->where('locale', $locale)
            ->when($allowCurrentDraft, fn ($query) => $query->whereIn('status', [ArticleNarrationStatus::Draft->value, ArticleNarrationStatus::Approved->value]), fn ($query) => $query->whereNotNull('approved_at'))
            ->where('source_hash', $sourceHash)
            ->whereNotNull('script')
            ->first();

        if ($narration === null || blank($narration->script)) {
            return null;
        }

        $text = $this->markup->forModel((string) $narration->script, $modelId);

        return new ResolvedArticleAudioScript(
            text: $text,
            contentHash: hash('sha256', $modelId."\0".(string) config('services.elevenlabs.voice_id')."\0".$text),
            sourceHash: $sourceHash,
            modelId: $modelId,
            narrationId: $narration->getKey(),
        );
    }

    public function publicFingerprint(Article $article, string $locale, string $modelId): string
    {
        return $this->approved($article, $locale, $modelId, allowCurrentDraft: true)?->contentHash
            ?? $this->source->fingerprint($article, $locale);
    }
}
