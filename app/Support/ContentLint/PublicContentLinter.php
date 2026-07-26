<?php

namespace App\Support\ContentLint;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PublicContentLinter
{
    /** @var list<string> */
    private const PUBLIC_VIEW_SOURCES = [
        'resources/views/website',
        'resources/views/livewire/website',
        'resources/views/auth',
        'resources/views/athar',
        'resources/views/errors',
        'resources/views/components/athar',
        'resources/views/components/partials',
        'resources/views/components/article-share.blade.php',
        'resources/views/components/media-placeholder.blade.php',
        'resources/views/components/reader-account-nav.blade.php',
        'resources/views/components/turnstile/widget.blade.php',
        'resources/views/components/layouts/front.blade.php',
        'resources/views/components/layouts/athar.blade.php',
    ];

    /** @var list<string> */
    private const PUBLIC_PHP_SOURCES = [
        'app/Http/Controllers/Website',
        'app/Livewire/Website',
        'app/Support/SiteContent.php',
        'app/Support/PortfolioAtlas.php',
        'app/Support/AtharPublicProof.php',
        'app/Support/Editorial/Article.php',
        'app/Support/Editorial/ArticleCatalog.php',
    ];

    /** @var list<string> */
    private const PUBLIC_TRANSLATION_GROUPS = [
        'articles',
        'athar',
        'community',
        'community_notifications',
        'legal',
        'reader_auth',
        'site',
        'validation',
    ];

    /** @var list<string> */
    private const SEMANTIC_SECTION_KEYS = [
        'adoption',
        'alt',
        'approach',
        'body',
        'confidentiality_note',
        'content',
        'context',
        'description',
        'engagement_note',
        'executive_summary',
        'heading',
        'kicker',
        'label',
        'lead',
        'lessons',
        'name',
        'outcome',
        'problem',
        'result',
        'seo_description',
        'seo_title',
        'solution',
        'summary',
        'title',
    ];

    /** @var array<string, array{0: int, 1: int|null}> */
    private const ARABIC_PLURAL_CATEGORIES = [
        'zero' => [0, 0],
        'one' => [1, 1],
        'two' => [2, 2],
        'few' => [3, 10],
        'many' => [11, 99],
        'other' => [100, null],
    ];

    public function __construct(private readonly Filesystem $files) {}

    public function inspectApplication(): PublicContentLintReport
    {
        $sources = $this->applicationSources();
        $translationKeys = $this->translationKeys($sources);

        return $this->inspect($sources, $this->translationsFor($translationKeys));
    }

    /**
     * @param  array<string, string>  $sources
     * @param  array{ar: array<string, mixed>, en: array<string, mixed>}  $translations
     */
    public function inspect(array $sources, array $translations): PublicContentLintReport
    {
        $findings = [];
        $translationKeys = $this->translationKeys($sources);
        $pluralKeys = $this->pluralTranslationKeys($sources);

        foreach ($sources as $source => $contents) {
            if (str_ends_with($source, '.blade.php')) {
                $findings = [...$findings, ...$this->lintText($this->visibleText($contents), $source)];
            }
        }

        foreach ($translationKeys as $key) {
            foreach (['ar', 'en'] as $locale) {
                if (! Arr::has($translations[$locale] ?? [], $key)) {
                    $findings[] = new PublicContentLintFinding(
                        'unresolved_translation_key',
                        $this->translationSource($locale, $key),
                        "Missing {$locale} translation for '{$key}'.",
                    );
                }
            }
        }

        foreach ($translations as $locale => $groups) {
            foreach ($groups as $group => $values) {
                if (! is_array($values)) {
                    $findings[] = new PublicContentLintFinding(
                        'unresolved_translation_key',
                        "lang/{$locale}/{$group}.php",
                        "The '{$group}' translation group must return an array.",
                    );

                    continue;
                }

                foreach ($this->translationLeaves($values, $group) as [$key, $value]) {
                    $source = $this->translationSource($locale, $key);

                    if ($this->isEmptySemanticSection($key, $value)) {
                        $findings[] = new PublicContentLintFinding(
                            'empty_semantic_section',
                            $source,
                            "Public semantic section '{$key}' is empty.",
                        );
                    }

                    if (is_string($value)) {
                        $findings = [...$findings, ...$this->lintText($value, $source, $locale)];
                    }
                }
            }
        }

        foreach ($pluralKeys as $key) {
            $arabicValue = Arr::get($translations['ar'] ?? [], $key);

            if (! is_string($arabicValue)) {
                $findings[] = new PublicContentLintFinding(
                    'missing_arabic_plural_category',
                    $this->translationSource('ar', $key),
                    "Arabic plural copy for '{$key}' is missing its interval value.",
                );

                continue;
            }

            foreach (self::ARABIC_PLURAL_CATEGORIES as $category => [$from, $to]) {
                if (! $this->hasArabicPluralCategory($arabicValue, $from, $to)) {
                    $findings[] = new PublicContentLintFinding(
                        'missing_arabic_plural_category',
                        $this->translationSource('ar', $key),
                        "Arabic plural copy for '{$key}' is missing the {$category} category.",
                    );
                }
            }
        }

        return new PublicContentLintReport(
            findings: $this->uniqueFindings($findings),
            sourceCount: count($sources),
            translationKeyCount: count($translationKeys),
            pluralKeyCount: count($pluralKeys),
        );
    }

    /**
     * @return array<string, string>
     */
    private function applicationSources(): array
    {
        $sources = [];

        foreach (self::PUBLIC_VIEW_SOURCES as $relativePath) {
            $this->addSourcesAtPath($sources, base_path($relativePath), '.blade.php');
        }

        foreach (self::PUBLIC_PHP_SOURCES as $relativePath) {
            $this->addSourcesAtPath($sources, base_path($relativePath), '.php');
        }

        ksort($sources);

        return $sources;
    }

    /**
     * @param  array<string, string>  $sources
     */
    private function addSourcesAtPath(array &$sources, string $path, string $extension): void
    {
        if ($this->files->isFile($path)) {
            if (str_ends_with($path, $extension)) {
                $sources[$this->relativePath($path)] = $this->files->get($path);
            }

            return;
        }

        if (! $this->files->isDirectory($path)) {
            return;
        }

        foreach ($this->files->allFiles($path) as $file) {
            $filePath = $file->getPathname();

            if (str_ends_with($filePath, $extension)) {
                $sources[$this->relativePath($filePath)] = $this->files->get($filePath);
            }
        }
    }

    /**
     * @param  array<string, string>  $sources
     * @return list<string>
     */
    private function translationKeys(array $sources): array
    {
        $keys = [];

        foreach ($sources as $contents) {
            preg_match_all(
                '/(?:(?:__|trans|trans_choice|Lang::get)\s*\(\s*|@(?:lang|choice)\s*\(\s*)[\'\"]([A-Za-z][A-Za-z0-9_-]*(?:\.[A-Za-z0-9_-]+)+)[\'\"]/u',
                $contents,
                $matches,
            );

            foreach ($matches[1] as $key) {
                $keys[] = $key;
            }
        }

        return $this->uniqueStrings($keys);
    }

    /**
     * @param  array<string, string>  $sources
     * @return list<string>
     */
    private function pluralTranslationKeys(array $sources): array
    {
        $keys = [];

        foreach ($sources as $contents) {
            preg_match_all(
                '/(?:(?:trans_choice)\s*\(\s*|@choice\s*\(\s*)[\'\"]([A-Za-z][A-Za-z0-9_-]*(?:\.[A-Za-z0-9_-]+)+)[\'\"]/u',
                $contents,
                $matches,
            );

            foreach ($matches[1] as $key) {
                $keys[] = $key;
            }
        }

        return $this->uniqueStrings($keys);
    }

    /**
     * @param  list<string>  $translationKeys
     * @return array{ar: array<string, mixed>, en: array<string, mixed>}
     */
    private function translationsFor(array $translationKeys): array
    {
        $groups = array_values(array_filter(
            array_map(fn (string $key): string => Str::before($key, '.'), $translationKeys),
            fn (string $group): bool => in_array($group, self::PUBLIC_TRANSLATION_GROUPS, true),
        ));
        $groups = $this->uniqueStrings($groups);
        $translations = ['ar' => [], 'en' => []];

        foreach (['ar', 'en'] as $locale) {
            foreach ($groups as $group) {
                $path = lang_path("{$locale}/{$group}.php");
                $translations[$locale][$group] = $this->files->isFile($path)
                    ? $this->files->getRequire($path)
                    : [];
            }
        }

        return $translations;
    }

    /**
     * @return iterable<array{0: string, 1: mixed}>
     */
    private function translationLeaves(array $values, string $prefix): iterable
    {
        foreach ($values as $key => $value) {
            $path = "{$prefix}.{$key}";

            if (is_array($value)) {
                if ($value === []) {
                    yield [$path, $value];
                } else {
                    yield from $this->translationLeaves($value, $path);
                }

                continue;
            }

            yield [$path, $value];
        }
    }

    /**
     * @return list<PublicContentLintFinding>
     */
    private function lintText(string $text, string $source, ?string $locale = null): array
    {
        $findings = [];

        $checks = [
            [
                'banned_sales_phrase',
                '/جلسة\s+تشخيصية/u',
                'Use the approved consultation wording instead of the banned sales phrase.',
            ],
            [
                'banned_discussion_label',
                '/بعد\s+الفقرة\s+الأخيرة/u',
                'Use the approved Arabic discussion label.',
            ],
            [
                'banned_discussion_label',
                '/After\s+the\s+last\s+paragraph/ui',
                'Use the approved English discussion label.',
            ],
            [
                'placeholder_marker',
                '/(?:\[\[(?:todo|tbd|placeholder|draft|internal)\]\]|\[(?:todo|tbd|placeholder|draft|internal)\]|\b(?:todo|tbd|fixme|lorem\s+ipsum|placeholder\s+text|coming\s+soon)\b|مساحة\s+(?:الصورة|المشهد).{0,100}?(?:لاحقاً|قيد\s+الإعداد))/ui',
                'Remove placeholder copy before public publication.',
            ],
            [
                'editorial_annotation',
                '/(?:\[(?:editorial\s+note|internal\s+only|for\s+review|do\s+not\s+publish|draft\s+copy|ملاحظة\s+تحريرية|للمراجعة\s+الداخلية|لا\s+تنشر)\]|(?:EDITORIAL\s+NOTE|INTERNAL\s+ONLY|DO\s+NOT\s+PUBLISH|DRAFT\s+COPY)\s*:)/ui',
                'Remove editorial-only annotations from public copy.',
            ],
        ];

        if ($locale !== null) {
            $checks[] = [
                'unresolved_translation_key',
                '/\b(?:site|articles|community|community_notifications|legal|reader_auth|athar|validation)\.[a-z][a-z0-9_.-]*\b/ui',
                'Rendered copy looks like an unresolved translation key.',
            ];
        }

        foreach ($checks as [$rule, $pattern, $message]) {
            if (preg_match($pattern, $text) === 1) {
                $findings[] = new PublicContentLintFinding($rule, $source, $message);
            }
        }

        if ($locale === 'ar' && preg_match('/(?:^|[\r\n])\s*(?:Step|Card|Role)\b(?:\s*(?:\d+|[:\-–—])|\s+[A-Z])/um', $text) === 1) {
            $findings[] = new PublicContentLintFinding(
                'english_ui_prefix',
                $source,
                'Arabic public copy starts with an English Step, Card, or Role prefix.',
            );
        }

        return $findings;
    }

    private function visibleText(string $contents): string
    {
        $contents = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $contents) ?? $contents;
        $contents = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $contents) ?? $contents;
        $contents = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}/s', '', $contents) ?? $contents;

        return html_entity_decode(strip_tags($contents), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function isEmptySemanticSection(string $key, mixed $value): bool
    {
        if (! in_array(Str::afterLast($key, '.'), self::SEMANTIC_SECTION_KEYS, true)) {
            return false;
        }

        return $value === null || (is_string($value) && trim($value) === '') || (is_array($value) && $value === []);
    }

    private function hasArabicPluralCategory(string $value, int $expectedFrom, ?int $expectedTo): bool
    {
        foreach (explode('|', $value) as $segment) {
            if (preg_match('/^\s*\{(\d+)\}/u', $segment, $exact) === 1) {
                if ($expectedTo !== null && (int) $exact[1] === $expectedFrom && $expectedFrom === $expectedTo) {
                    return true;
                }

                continue;
            }

            if (preg_match('/^\s*\[(\d+),(\d+|\*)\]/u', $segment, $range) !== 1) {
                continue;
            }

            $from = (int) $range[1];
            $to = $range[2] === '*' ? null : (int) $range[2];

            if ($from === $expectedFrom && $to === $expectedTo) {
                return true;
            }
        }

        return false;
    }

    private function translationSource(string $locale, string $key): string
    {
        return 'lang/'.$locale.'/'.Str::before($key, '.').'.php#'.Str::after($key, '.');
    }

    private function relativePath(string $path): string
    {
        return ltrim(Str::after($path, base_path()), DIRECTORY_SEPARATOR);
    }

    /**
     * @param  list<PublicContentLintFinding>  $findings
     * @return list<PublicContentLintFinding>
     */
    private function uniqueFindings(array $findings): array
    {
        $unique = [];

        foreach ($findings as $finding) {
            $unique["{$finding->rule}|{$finding->source}|{$finding->message}"] = $finding;
        }

        return array_values($unique);
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function uniqueStrings(array $values): array
    {
        return array_values(array_unique($values));
    }
}
