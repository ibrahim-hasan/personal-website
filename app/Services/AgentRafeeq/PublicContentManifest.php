<?php

namespace App\Services\AgentRafeeq;

use InvalidArgumentException;

final class PublicContentManifest
{
    public const TYPES = [
        ['key' => 'work', 'label_ar' => 'أعمال', 'label_en' => 'Work'],
        ['key' => 'service', 'label_ar' => 'خدمات', 'label_en' => 'Services'],
        ['key' => 'article', 'label_ar' => 'مقالات', 'label_en' => 'Articles'],
    ];

    /**
     * Translation presence is an eligibility check, never a source of public copy.
     *
     * @param  array<string, mixed>  $presented
     * @param  array<string, array<string, mixed>>  $translations
     * @return array<string, mixed>|null
     */
    public static function eligibleProject(array $presented, array $translations, string $locale): ?array
    {
        foreach (['title', 'summary'] as $field) {
            $value = $translations[$field][$locale] ?? null;

            if (! is_string($value) || trim($value) === '') {
                return null;
            }
        }

        return $presented;
    }

    /**
     * Input contains only records returned by the source's public presenters.
     *
     * @param  array<string, array<string, list<array<string, mixed>>>>  $content
     * @return array<string, mixed>
     */
    public static function build(string $sourceUrl, array $content): array
    {
        $items = [];

        foreach (['ar', 'en'] as $locale) {
            foreach (self::TYPES as $type) {
                foreach ($content[$locale][$type['key']] ?? [] as $record) {
                    $key = trim((string) ($record['key'] ?? ''));
                    $localized = self::localize($type['key'], $record, $locale, $sourceUrl);

                    if ($key === '' || $localized === null) {
                        continue;
                    }

                    $externalId = 'ibrahim-website:'.$type['key'].':'.$key;

                    if (strlen($externalId) > 255) {
                        throw new InvalidArgumentException('A public content key exceeds the sync limit.');
                    }

                    if (isset($items[$externalId]['metadata']['localizations'][$locale])) {
                        throw new InvalidArgumentException('Duplicate public content key within one locale.');
                    }

                    $items[$externalId] ??= [
                        'external_id' => $externalId,
                        'type' => $type['key'],
                        ...array_diff_key($localized, ['public_anchor' => true]),
                        'lang' => $locale,
                        'metadata' => ['localizations' => []],
                    ];
                    $items[$externalId]['metadata']['localizations'][$locale] = $localized;
                }
            }
        }

        ksort($items);

        return [
            'source' => ['key' => 'ibrahim-website', 'name' => 'Ibrahim Hasan Website', 'url' => rtrim($sourceUrl, '/')],
            'types' => self::TYPES,
            'items' => array_values($items),
        ];
    }

    /** @param array<string, mixed> $record */
    private static function localize(string $type, array $record, string $locale, string $sourceUrl): ?array
    {
        $title = self::text((string) ($record[$type === 'service' ? 'name' : 'title'] ?? ''));
        $summary = self::text((string) ($record['summary'] ?? ''));
        $url = self::url((string) ($record['url'] ?? ''), $sourceUrl);

        if ($title === '' || $summary === '' || $url === null) {
            return null;
        }

        $sections = match ($type) {
            'work' => ['sector', 'challenge', 'response', 'outcome', 'tags'],
            'service' => ['problem', 'approach', 'deliverables', 'result', 'fit_signals', 'engagement_note'],
            default => [],
        };
        $labels = $locale === 'ar'
            ? ['sector' => 'القطاع', 'challenge' => 'التحدي', 'response' => 'التنفيذ', 'outcome' => 'النتيجة', 'tags' => 'المجالات', 'problem' => 'الاحتياج', 'approach' => 'المنهج', 'deliverables' => 'المخرجات', 'result' => 'النتيجة', 'fit_signals' => 'متى تناسبك', 'engagement_note' => 'بداية العمل']
            : ['sector' => 'Sector', 'challenge' => 'Challenge', 'response' => 'Delivery', 'outcome' => 'Outcome', 'tags' => 'Areas', 'problem' => 'Need', 'approach' => 'Approach', 'deliverables' => 'Deliverables', 'result' => 'Result', 'fit_signals' => 'Good fit', 'engagement_note' => 'Starting engagement'];
        $paragraphs = [];

        foreach ($sections as $field) {
            $value = $record[$field] ?? '';
            $value = is_array($value) ? implode("\n", array_filter($value, is_string(...))) : (is_string($value) ? $value : '');
            $text = self::text($value);

            if ($text !== '') {
                $paragraphs[] = $labels[$field].":\n".$text;
            }
        }

        $body = $type === 'article' ? self::text((string) ($record['body_html'] ?? '')) : implode("\n\n", $paragraphs);
        $image = match ($type) {
            'work' => $record['image_media']['src'] ?? $record['image'] ?? '',
            'article' => $record['card_image']['src'] ?? $record['image_url'] ?? '',
            default => '',
        };

        if ($type === 'article' && $body === '') {
            return null;
        }

        $localized = [
            'title' => $title,
            'summary' => $summary,
            'body' => $body,
            'url' => $url,
            'image_url' => self::url(is_string($image) ? $image : '', $sourceUrl),
        ];
        $anchor = parse_url($url, PHP_URL_FRAGMENT);
        $prefix = ['work' => 'project-', 'service' => 'service-'][$type] ?? null;

        if ($prefix !== null && is_string($anchor) && strlen($anchor) <= 180 && str_starts_with($anchor, $prefix)
            && preg_match('/\A(?:project|service)-[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $anchor) === 1) {
            $localized['public_anchor'] = $anchor;
        }

        return $localized;
    }

    public static function text(string $html): string
    {
        $html = preg_replace('~<(script|style|noscript|template)\b[^>]*>.*?</\1\s*>~is', '', $html) ?? '';
        $html = preg_replace('~<br\s*/?>|</(?:p|div|h[1-6]|li|tr|blockquote|section)>~i', "\n", $html) ?? '';
        $html = preg_replace('~</(?:td|th)>~i', "\t", $html) ?? '';
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[^\S\n]+/u', ' ', $text) ?? '';
        $text = preg_replace('/ *\n */u', "\n", $text) ?? '';

        return trim(preg_replace('/\n{3,}/u', "\n\n", $text) ?? '');
    }

    private static function url(string $value, string $base): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '//')) {
            $value = parse_url($base, PHP_URL_SCHEME).':'.$value;
        } elseif (! preg_match('/^[a-z][a-z0-9+.-]*:/i', $value)) {
            $value = rtrim($base, '/').'/'.ltrim($value, '/');
        }

        $parts = parse_url($value);

        return is_array($parts) && in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            && isset($parts['host']) && ! isset($parts['user']) && ! isset($parts['pass'])
            && filter_var($value, FILTER_VALIDATE_URL) !== false ? $value : null;
    }
}
