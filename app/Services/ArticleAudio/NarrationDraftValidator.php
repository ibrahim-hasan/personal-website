<?php

namespace App\Services\ArticleAudio;

use UnexpectedValueException;

class NarrationDraftValidator
{
    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'thoughtful',
        'short pause',
        'long pause',
    ];

    public function validate(string $script, string $source): void
    {
        $script = trim($script);
        $source = trim($source);

        if ($script === '') {
            throw new UnexpectedValueException('The narration draft is empty.');
        }

        if (str_contains($script, '```') || preg_match('/<[^>]+>/u', $script) === 1) {
            throw new UnexpectedValueException('The narration draft contains unsupported markup.');
        }

        preg_match_all('/\[([^\]]+)\]/u', $script, $matches);

        foreach ($matches[1] ?? [] as $tag) {
            if (! in_array(mb_strtolower(trim((string) $tag)), self::ALLOWED_TAGS, true)) {
                throw new UnexpectedValueException('The narration draft contains an unsupported audio tag.');
            }
        }

        $plainScript = preg_replace('/\[[^\]]+\]/u', '', $script) ?? $script;
        $sourceLength = mb_strlen($source);
        $scriptLength = mb_strlen(trim($plainScript));

        if ($sourceLength >= 200) {
            $ratio = $scriptLength / $sourceLength;

            if ($ratio < 0.65 || $ratio > 1.65) {
                throw new UnexpectedValueException('The narration draft changed the source length too substantially.');
            }
        }
    }
}
