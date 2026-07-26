<?php

namespace App\Support\ContentLint;

final readonly class PublicContentLintReport
{
    /**
     * @param  list<PublicContentLintFinding>  $findings
     */
    public function __construct(
        public array $findings,
        public int $sourceCount,
        public int $translationKeyCount,
        public int $pluralKeyCount,
    ) {}

    public function hasFailures(): bool
    {
        return $this->findings !== [];
    }
}
