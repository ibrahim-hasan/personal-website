<?php

namespace App\Support\Ai;

use App\Support\Editorial\ArticleBody;

final class ArticleSeoSource
{
    public function __construct(private readonly ArticleBody $articleBody) {}

    /**
     * @param  array{summary?: mixed, body?: mixed, lead?: mixed, sections?: mixed, closing?: mixed}  $state
     */
    public function fromState(array $state): string
    {
        $parts = [];

        $this->appendText($state['summary'] ?? null, $parts);

        if (filled($state['body'] ?? null)) {
            $this->appendText($this->articleBody->text($state['body']), $parts);

            return trim(implode("\n\n", array_values(array_unique($parts))));
        }

        foreach (['lead', 'sections', 'closing'] as $field) {
            $this->appendText($state[$field] ?? null, $parts);
        }

        return trim(implode("\n\n", array_values(array_unique($parts))));
    }

    /** @param list<string> $parts */
    private function appendText(mixed $value, array &$parts): void
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                $this->appendText($item, $parts);
            }

            return;
        }

        if (! is_scalar($value)) {
            return;
        }

        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '');

        if ($text !== '') {
            $parts[] = $text;
        }
    }
}
