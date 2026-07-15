<?php

namespace App\Data\ArticleAudio;

final readonly class NarrationDraft
{
    /**
     * @param  list<string>  $notes
     * @param  list<string>  $pronunciationNotes
     */
    public function __construct(
        public string $script,
        public array $notes,
        public array $pronunciationNotes,
        public string $model,
        public string $promptVersion,
    ) {}
}
