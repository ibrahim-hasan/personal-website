<?php

namespace App\Services\ArticleAudio;

use UnexpectedValueException;

class NarrationDraftValidator
{
    private const MINIMUM_ARABIC_WORD_COVERAGE = 0.50;

    private const ARABIC_DIACRITICS_PATTERN = '/[\x{064B}-\x{0652}\x{0670}]/u';

    private const ARABIC_WORD_PATTERN = '/[\x{0621}-\x{064A}\x{066E}-\x{06D3}][\x{0621}-\x{064A}\x{066E}-\x{06D3}\x{064B}-\x{0652}\x{0670}]*/u';

    private const LEXICAL_WORD_PATTERN = '/[\p{L}\p{M}]+/u';

    /** @var list<string> */
    private const ALLOWED_AUDIO_TAGS = [
        '[thoughtful]',
        '[short pause]',
        '[long pause]',
        '[exhales]',
    ];

    public function validate(string $script, string $source): void
    {
        $script = trim($script);
        $source = trim($source);

        if ($script === '') {
            throw new UnexpectedValueException('The narration draft is empty.');
        }

        if (! hash_equals($this->withoutArabicDiacritics($source), $this->withoutArabicDiacritics($this->withoutAllowedAudioTags($script)))) {
            throw new UnexpectedValueException('The narration draft may only add Arabic diacritics and approved audio tags to the source text.');
        }
    }

    public function validateGenerated(string $script, string $source, string $locale): void
    {
        $this->validate($script, $source);

        if ($locale === 'ar') {
            $this->validateArabicLanguage($this->withoutAllowedAudioTags($script));
        }
    }

    private function validateArabicLanguage(string $script): void
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
    }

    private function withoutArabicDiacritics(string $value): string
    {
        return preg_replace(self::ARABIC_DIACRITICS_PATTERN, '', $value) ?? $value;
    }

    private function withoutAllowedAudioTags(string $value): string
    {
        return str_replace(self::ALLOWED_AUDIO_TAGS, '', $value);
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
