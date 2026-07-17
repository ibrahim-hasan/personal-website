<?php

namespace App\Data\ElevenLabs;

final readonly class SpeechSynthesisResult
{
    /** @param list<string> $requestIds */
    public function __construct(
        public string $audio,
        public array $requestIds,
        public int $segmentCount,
        public int $characterCount,
        public string $checkpointKey,
    ) {}
}
