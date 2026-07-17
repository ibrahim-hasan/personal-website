<?php

namespace App\Services\ArticleAudio;

class NarrationMarkup
{
    public function forModel(string $script, string $modelId): string
    {
        $prepared = $modelId === 'eleven_multilingual_v2'
            ? str_replace(
                ['[thoughtful]', '[short pause]', '[long pause]', '[exhales]'],
                ['', '<break time="0.7s" />', '<break time="1.4s" />', ''],
                $script,
            )
            : $script;

        $prepared = preg_replace('/[ \t]+\R/u', "\n", $prepared) ?? $prepared;
        $prepared = preg_replace('/\R{3,}/u', "\n\n", $prepared) ?? $prepared;
        $prepared = preg_replace('/[ \t]{2,}/u', ' ', $prepared) ?? $prepared;

        return trim($prepared);
    }
}
