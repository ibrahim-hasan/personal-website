<?php

namespace App\Support\ContentLint;

final readonly class PublicContentLintFinding
{
    public function __construct(
        public string $rule,
        public string $source,
        public string $message,
    ) {}
}
