<?php

namespace App\Data\ArticleAudio;

final readonly class ResolvedArticleAudioScript
{
    public function __construct(
        public string $text,
        public string $contentHash,
        public string $sourceHash,
        public string $modelId,
        public int $narrationId,
    ) {}
}
