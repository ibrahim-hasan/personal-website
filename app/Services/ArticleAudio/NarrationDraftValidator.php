<?php

namespace App\Services\ArticleAudio;

use UnexpectedValueException;

class NarrationDraftValidator
{
    private const MINIMUM_ARABIC_DIACRITIC_COVERAGE = 0.60;

    private const MINIMUM_ARABIC_WORD_COVERAGE = 0.50;

    private const ARABIC_DIACRITICS_PATTERN = '/[\x{064B}-\x{0652}\x{0670}]/u';

    private const ARABIC_WORD_PATTERN = '/[\x{0621}-\x{064A}\x{066E}-\x{06D3}][\x{0621}-\x{064A}\x{066E}-\x{06D3}\x{064B}-\x{0652}\x{0670}]*/u';

    private const LEXICAL_WORD_PATTERN = '/[\p{L}\p{M}]+/u';

    /** @var list<string> */
    private const ALLOWED_TAGS = [
        'thoughtful',
        'short pause',
        'long pause',
        'exhales',
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

        $sourceLength = mb_strlen($this->withoutArabicDiacritics($source));
        $scriptLength = mb_strlen(trim($this->withoutArabicDiacritics($plainScript)));

        if ($sourceLength >= 200) {
            $ratio = $scriptLength / $sourceLength;

            if ($ratio < 0.65 || $ratio > 1.65) {
                throw new UnexpectedValueException('The narration draft changed the source length too substantially.');
            }
        }
    }

    public function validateGenerated(string $script, string $source, string $locale): void
    {
        $this->validate($script, $source);

        if ($locale === 'ar') {
            $plainScript = preg_replace('/\[[^\]]+\]/u', '', $script) ?? $script;

            $this->validateArabicDiacriticCoverage($plainScript);
        }
    }

    private function validateArabicDiacriticCoverage(string $script): void
    {
        preg_match_all(self::LEXICAL_WORD_PATTERN, $script, $lexicalMatches);
        preg_match_all(self::ARABIC_WORD_PATTERN, $script, $matches);

        $lexicalWords = $this->wordsWithAtLeastTwoLetters($lexicalMatches[0] ?? []);
        $words = array_values(array_filter(
            $matches[0] ?? [],
            function (string $word): bool {
                $baseLetters = preg_replace(self::ARABIC_DIACRITICS_PATTERN, '', $word) ?? $word;

                return mb_strlen($baseLetters) >= 2;
            },
        ));

        if ($lexicalWords === [] || ($words === [] || (count($words) / count($lexicalWords)) < self::MINIMUM_ARABIC_WORD_COVERAGE)) {
            throw new UnexpectedValueException('The Arabic narration draft is not predominantly Arabic.');
        }

        $diacritizedWords = count(array_filter(
            $words,
            fn (string $word): bool => preg_match(self::ARABIC_DIACRITICS_PATTERN, $word) === 1,
        ));

        if (($diacritizedWords / count($words)) < self::MINIMUM_ARABIC_DIACRITIC_COVERAGE) {
            throw new UnexpectedValueException('The Arabic narration draft does not contain enough pronunciation-focused diacritics.');
        }
    }

    private function withoutArabicDiacritics(string $value): string
    {
        return preg_replace(self::ARABIC_DIACRITICS_PATTERN, '', $value) ?? $value;
    }

    /**
     * @param  list<string>  $words
     * @return list<string>
     */
    private function wordsWithAtLeastTwoLetters(array $words): array
    {
        return array_values(array_filter(
            $words,
            function (string $word): bool {
                $letters = preg_replace('/\p{M}/u', '', $word) ?? $word;

                return mb_strlen($letters) >= 2;
            },
        ));
    }
}
