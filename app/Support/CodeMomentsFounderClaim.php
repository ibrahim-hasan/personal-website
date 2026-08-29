<?php

namespace App\Support;

final class CodeMomentsFounderClaim
{
    public static function isDirect(string $biography): bool
    {
        $normalized = self::normalize($biography);

        if ($normalized === '') {
            return false;
        }

        return self::matchesEnglish($normalized) || self::matchesArabic($normalized);
    }

    private static function matchesEnglish(string $biography): bool
    {
        $target = '(?:the\s+)?(?:company\s+)?code\s+moments';
        $founder = '(?:co[- ]?founder|founder)';
        $executive = '(?:chief\s+executive\s+officer|ceo)';

        if (preg_match(
            '/\b'.$founder.'(?:\s*(?:&|and)\s*'.$executive.')?\s*(?:of|at)?\s+'.$target.'\b/iu',
            $biography,
        ) === 1) {
            return true;
        }

        return preg_match(
            '/\b(?:i|ibrahim(?:\s+hasan)?)\s+(?:co[- ]?founded|founded)\s+(?:the\s+)?(?:company\s+)?code\s+moments\b/iu',
            $biography,
        ) === 1;
    }

    private static function matchesArabic(string $biography): bool
    {
        $target = '(?:ل|ب)?(?:شركة\s+)?(?:كود\s+مومنتس|code\s+moments)';
        $founder = '(?:(?:ال)?شريك\s+(?:ال)?مؤسس|(?:ال)?مؤسس(?:\s+(?:ال)?مشارك)?)';
        $executive = '(?:(?:و)?(?:ال)?رئيس\s+(?:ال)?تنفيذي|(?:و)?(?:ال)?مدير\s+(?:ال)?تنفيذي)';
        $companyPrefix = '(?:(?:في\s+)?(?:شركة\s+)?)';

        if (preg_match(
            '/(?:^|\s)'.$founder.'(?:\s+'.$executive.')?\s+'.$companyPrefix.$target.'(?:\s|$)/iu',
            $biography,
        ) === 1) {
            return true;
        }

        return preg_match(
            '/(?:^|\s)(?:اسست|أسست|اسس|أسس|شاركت\s+في\s+تاسيس|شاركت\s+في\s+تأسيس)\s+(?:شركة\s+)?'.$target.'(?:\s|$)/iu',
            $biography,
        ) === 1;
    }

    private static function normalize(string $biography): string
    {
        $normalized = html_entity_decode(strip_tags($biography), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^\p{L}\p{N}&-]+/u', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }
}
