<?php

namespace App\Services\ArticleAudio;

class NarrationTextChunker
{
    /**
     * @return list<string>
     */
    public function split(string $text, int $maxCharacters): array
    {
        $maxCharacters = max(100, $maxCharacters);
        $paragraphs = preg_split('/\R{2,}/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            foreach ($this->splitOversizedParagraph(trim($paragraph), $maxCharacters) as $part) {
                $candidate = $current === '' ? $part : $current."\n\n".$part;

                if (mb_strlen($candidate) <= $maxCharacters) {
                    $current = $candidate;

                    continue;
                }

                if ($current !== '') {
                    $chunks[] = $current;
                }

                $current = $part;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /** @return list<string> */
    private function splitOversizedParagraph(string $paragraph, int $maxCharacters): array
    {
        if (mb_strlen($paragraph) <= $maxCharacters) {
            return [$paragraph];
        }

        $sentences = preg_split('/(?<=[.!?؟؛])\s+/u', $paragraph, -1, PREG_SPLIT_NO_EMPTY) ?: [$paragraph];
        $parts = [];
        $current = '';

        foreach ($sentences as $sentence) {
            foreach ($this->splitOversizedSentence(trim($sentence), $maxCharacters) as $piece) {
                $candidate = $current === '' ? $piece : $current.' '.$piece;

                if (mb_strlen($candidate) <= $maxCharacters) {
                    $current = $candidate;

                    continue;
                }

                if ($current !== '') {
                    $parts[] = $current;
                }

                $current = $piece;
            }
        }

        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    /** @return list<string> */
    private function splitOversizedSentence(string $sentence, int $maxCharacters): array
    {
        if (mb_strlen($sentence) <= $maxCharacters) {
            return [$sentence];
        }

        $words = preg_split('/\s+/u', $sentence, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = [];
        $current = '';

        foreach ($words as $word) {
            while (mb_strlen($word) > $maxCharacters) {
                if ($current !== '') {
                    $parts[] = $current;
                    $current = '';
                }

                $parts[] = mb_substr($word, 0, $maxCharacters);
                $word = mb_substr($word, $maxCharacters);
            }

            $candidate = $current === '' ? $word : $current.' '.$word;

            if (mb_strlen($candidate) <= $maxCharacters) {
                $current = $candidate;
            } else {
                $parts[] = $current;
                $current = $word;
            }
        }

        if ($current !== '') {
            $parts[] = $current;
        }

        return $parts;
    }
}
