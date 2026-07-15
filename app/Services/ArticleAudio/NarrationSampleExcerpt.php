<?php

namespace App\Services\ArticleAudio;

class NarrationSampleExcerpt
{
    public function make(string $script, int $maxCharacters): string
    {
        $script = trim($script);
        $maxCharacters = max(200, $maxCharacters);

        if (mb_strlen($script) <= $maxCharacters) {
            return $script;
        }

        $prefix = mb_substr($script, 0, $maxCharacters);
        $boundary = 0;

        foreach (['.', '!', '?', '؟', '؛'] as $punctuation) {
            $position = mb_strrpos($prefix, $punctuation);

            if ($position !== false) {
                $boundary = max($boundary, $position + 1);
            }
        }

        if ($boundary >= (int) floor($maxCharacters * 0.55)) {
            return trim(mb_substr($prefix, 0, $boundary));
        }

        $space = mb_strrpos($prefix, ' ');

        return trim($space === false ? $prefix : mb_substr($prefix, 0, $space));
    }
}
