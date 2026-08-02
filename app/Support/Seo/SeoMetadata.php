<?php

namespace App\Support\Seo;

use App\Support\PortfolioAtlas;
use App\Support\SiteContent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

final readonly class SeoMetadata
{
    /**
     * @param  array<string, string>  $alternateUrls
     * @param  array<string, string>  $alternateLocales
     * @param  list<string>  $articleTags
     * @param  array<string, mixed>  $structuredData
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonicalUrl,
        public array $alternateUrls,
        public string $xDefaultUrl,
        public string $locale,
        public string $regionalLocale,
        public array $alternateLocales,
        public string $robots,
        public string $ogType,
        public string $imageUrl,
        public string $imageSecureUrl,
        public string $imageAlt,
        public ?int $imageWidth,
        public ?int $imageHeight,
        public string $imageType,
        public ?string $publishedAt,
        public ?string $modifiedAt,
        public ?string $articleAuthorUrl,
        public ?string $articleSection,
        public array $articleTags,
        public array $structuredData,
    ) {}

    /**
     * @param  array<string, string>  $alternateUrls
     * @param  list<string>  $articleTags
     * @param  array<string, mixed>|list<array<string, mixed>>|null  $structuredData
     */
    public static function fromLayout(
        ?string $title,
        ?string $description,
        ?string $canonicalUrl,
        array $alternateUrls,
        string $robots,
        string $ogType,
        ?string $ogImage,
        ?string $ogImageAlt,
        ?int $ogImageWidth,
        ?int $ogImageHeight,
        ?string $ogImageType,
        ?string $publishedAt,
        ?string $modifiedAt,
        ?string $articleAuthorUrl,
        ?string $articleSection,
        array $articleTags,
        string $schemaType,
        ?array $structuredData,
    ): self {
        $title = self::decodeHtmlEntities($title);
        $description = self::decodeHtmlEntities($description);
        $ogImageAlt = self::decodeHtmlEntities($ogImageAlt);
        $locale = current_locale();
        $siteName = (string) __('site.brand.name');
        $resolvedTitle = self::title($title, $siteName);
        $resolvedDescription = $description ?: (string) __('site.meta.default_description');
        $resolvedCanonicalUrl = self::cleanUrl($canonicalUrl ?: url()->current());
        $resolvedAlternateUrls = self::alternateUrls($alternateUrls);
        $imageUrl = filled($ogImage) ? $ogImage : self::defaultSocialImageUrl($locale);
        [$resolvedImageWidth, $resolvedImageHeight] = self::imageDimensions($imageUrl);
        $imageWidth = $ogImageWidth ?? $resolvedImageWidth;
        $imageHeight = $ogImageHeight ?? $resolvedImageHeight;
        $imageType = $ogImageType ?: self::imageMimeType($imageUrl);
        $nodes = self::baseNodes(
            title: $resolvedTitle,
            description: $resolvedDescription,
            canonicalUrl: $resolvedCanonicalUrl,
            locale: $locale,
            schemaType: $schemaType,
        );
        $extraNodes = self::normalizeNodes($structuredData);
        $articleNode = Arr::first(
            $extraNodes,
            fn (array $node): bool => in_array($node['@type'] ?? null, ['Article', 'BlogPosting', 'NewsArticle'], true),
        );
        $breadcrumbNode = Arr::first(
            $extraNodes,
            fn (array $node): bool => ($node['@type'] ?? null) === 'BreadcrumbList',
        );

        if ($breadcrumbNode === null && ! Str::contains(Str::lower($robots), 'noindex')) {
            $breadcrumbNode = self::defaultBreadcrumbNode(
                canonicalUrl: $resolvedCanonicalUrl,
                locale: $locale,
                pageName: $title ?: $siteName,
            );

            if ($breadcrumbNode !== null) {
                $extraNodes[] = $breadcrumbNode;
            }
        }

        $pageNodeIndex = array_key_last($nodes);

        if ($articleNode !== null && is_string($articleNode['@id'] ?? null)) {
            $nodes[$pageNodeIndex]['mainEntity'] = ['@id' => $articleNode['@id']];
        }

        if ($breadcrumbNode !== null && is_string($breadcrumbNode['@id'] ?? null)) {
            $nodes[$pageNodeIndex]['breadcrumb'] = ['@id' => $breadcrumbNode['@id']];
        }

        $resolvedTags = array_values(array_unique(array_filter(
            $articleTags,
            fn (mixed $tag): bool => is_string($tag) && trim($tag) !== '',
        )));

        return new self(
            title: $resolvedTitle,
            description: $resolvedDescription,
            canonicalUrl: $resolvedCanonicalUrl,
            alternateUrls: $resolvedAlternateUrls,
            xDefaultUrl: $resolvedAlternateUrls[default_locale()] ?? $resolvedCanonicalUrl,
            locale: $locale,
            regionalLocale: self::regionalLocale($locale),
            alternateLocales: collect($resolvedAlternateUrls)
                ->keys()
                ->reject(fn (string $alternateLocale): bool => $alternateLocale === $locale)
                ->mapWithKeys(fn (string $alternateLocale): array => [
                    $alternateLocale => self::regionalLocale($alternateLocale),
                ])
                ->all(),
            robots: $robots,
            ogType: $ogType,
            imageUrl: $imageUrl,
            imageSecureUrl: self::secureUrl($imageUrl),
            imageAlt: $ogImageAlt ?: (filled($ogImage)
                ? ($title ?: $siteName)
                : self::defaultSocialImageAlt($locale)),
            imageWidth: $imageWidth,
            imageHeight: $imageHeight,
            imageType: $imageType,
            publishedAt: $publishedAt,
            modifiedAt: $modifiedAt ?: $publishedAt,
            articleAuthorUrl: $articleAuthorUrl,
            articleSection: $articleSection,
            articleTags: $resolvedTags,
            structuredData: [
                '@context' => 'https://schema.org',
                '@graph' => [...$nodes, ...$extraNodes],
            ],
        );
    }

    public static function websiteId(): string
    {
        return self::siteUrl().'#website';
    }

    public static function personId(): string
    {
        return self::cleanUrl(localized_route('about', locale: default_locale())).'#person';
    }

    public static function pageId(string $canonicalUrl): string
    {
        return self::cleanUrl($canonicalUrl).'#webpage';
    }

    public static function defaultSocialImageUrl(?string $locale = null): string
    {
        $locale ??= current_locale();

        return asset(match ($locale) {
            'en' => 'images/social/ibrahim-hasan-share-en-v1.jpg',
            default => 'images/social/ibrahim-hasan-share-ar-v1.jpg',
        });
    }

    private static function title(?string $title, string $siteName): string
    {
        $title = trim((string) $title);

        if ($title === '') {
            $title = trim((string) __('site.meta.default_title'));
        }

        if (Str::contains(Str::lower($title), Str::lower($siteName))) {
            return $title;
        }

        return "{$title} | {$siteName}";
    }

    private static function defaultSocialImageAlt(string $locale): string
    {
        return (string) Lang::get('site.meta.social_image_alt', [], $locale);
    }

    private static function decodeHtmlEntities(?string $value): ?string
    {
        return $value === null
            ? null
            : htmlspecialchars_decode($value, ENT_QUOTES | ENT_HTML5);
    }

    /**
     * @param  array<string, string>  $alternateUrls
     * @return array<string, string>
     */
    private static function alternateUrls(array $alternateUrls): array
    {
        return collect(array_keys(config('laravellocalization.supportedLocales', [])))
            ->mapWithKeys(fn (string $locale): array => [
                $locale => self::cleanUrl($alternateUrls[$locale] ?? localized_current_url($locale)),
            ])
            ->all();
    }

    private static function cleanUrl(string $url): string
    {
        $cleanUrl = (string) Str::of($url)->before('#')->before('?');
        $path = parse_url($cleanUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '' || $path === '/') {
            return self::siteUrl();
        }

        return self::siteUrl().'/'.ltrim($path, '/');
    }

    private static function siteUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    private static function secureUrl(string $url): string
    {
        if (str_starts_with($url, 'http://')) {
            return 'https://'.Str::after($url, 'http://');
        }

        return str_starts_with($url, '//') ? "https:{$url}" : $url;
    }

    private static function regionalLocale(string $locale): string
    {
        return (string) config("laravellocalization.supportedLocales.{$locale}.regional", $locale);
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private static function imageDimensions(string $imageUrl): array
    {
        $path = parse_url($imageUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return [null, null];
        }

        $absolutePath = public_path(ltrim(rawurldecode($path), '/'));

        if (! is_file($absolutePath)) {
            return [null, null];
        }

        $dimensions = getimagesize($absolutePath);

        if (! is_array($dimensions)) {
            return [null, null];
        }

        return [(int) $dimensions[0], (int) $dimensions[1]];
    }

    private static function imageMimeType(string $imageUrl): string
    {
        $path = (string) parse_url($imageUrl, PHP_URL_PATH);

        return match (Str::lower(pathinfo($path, PATHINFO_EXTENSION))) {
            'avif' => 'image/avif',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function baseNodes(
        string $title,
        string $description,
        string $canonicalUrl,
        string $locale,
        string $schemaType,
    ): array {
        $personId = self::personId();
        $personUrl = self::cleanUrl(localized_route('about', locale: default_locale()));
        $siteNames = self::siteNames();
        $organizationNodes = self::organizationNodes($personId);
        $websiteNode = [
            '@type' => 'WebSite',
            '@id' => self::websiteId(),
            'url' => self::siteUrl(),
            'name' => $siteNames[0] ?? (string) __('site.brand.name'),
            'inLanguage' => array_keys(config('laravellocalization.supportedLocales', [])),
        ];

        if (count($siteNames) > 1) {
            $websiteNode['alternateName'] = array_slice($siteNames, 1);
        }

        $pageNode = [
            '@type' => $schemaType,
            '@id' => self::pageId($canonicalUrl),
            'url' => $canonicalUrl,
            'name' => $title,
            'description' => $description,
            'inLanguage' => $locale,
            'isPartOf' => ['@id' => self::websiteId()],
        ];

        if ($schemaType === 'ProfilePage') {
            $pageNode['mainEntity'] = ['@id' => $personId];
        }

        $personNode = [
            '@type' => 'Person',
            '@id' => $personId,
            'name' => $siteNames[0] ?? (string) __('site.brand.name'),
            'url' => $personUrl,
            'image' => asset('images/ibrahim/ibrahim-formal-portrait.webp'),
            'description' => (string) Lang::get('site.entity.description', [], $locale),
            'jobTitle' => (string) Lang::get('site.entity.job_title', [], $locale),
            'knowsAbout' => self::localizedStringList('site.entity.knows_about', $locale),
        ];

        if (count($siteNames) > 1) {
            $personNode['alternateName'] = array_slice($siteNames, 1);
        }

        $sameAs = collect(SiteContent::socialProfiles())
            ->pluck('href')
            ->filter(fn (mixed $url): bool => is_string($url) && trim($url) !== '')
            ->values()
            ->all();

        if ($sameAs !== []) {
            $personNode['sameAs'] = $sameAs;
        }

        if ($organizationNodes !== []) {
            $personNode['worksFor'] = collect($organizationNodes)
                ->map(fn (array $organization): array => ['@id' => $organization['@id']])
                ->all();
        }

        return [
            $websiteNode,
            $personNode,
            ...$organizationNodes,
            $pageNode,
        ];
    }

    /**
     * @return list<array{'@type': string, '@id': string, name: string, url: string, founder: array{'@id': string}}>
     */
    private static function organizationNodes(string $personId): array
    {
        return collect(PortfolioAtlas::companies())
            ->filter(fn (array $company): bool => in_array(
                $company['id'] ?? null,
                ['from-scratch', 'code-moments'],
                true,
            ))
            ->map(function (array $company) use ($personId): ?array {
                $organizationUrl = $company['action']['url'] ?? null;

                if (! is_string($organizationUrl) || ! filter_var($organizationUrl, FILTER_VALIDATE_URL)) {
                    return null;
                }

                return [
                    '@type' => 'Organization',
                    '@id' => rtrim($organizationUrl, '/').'#organization',
                    'name' => (string) $company['name'],
                    'url' => rtrim($organizationUrl, '/'),
                    'founder' => ['@id' => $personId],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private static function localizedStringList(string $key, string $locale): array
    {
        $values = Lang::get($key, [], $locale);

        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private static function siteNames(): array
    {
        return collect(array_keys(config('laravellocalization.supportedLocales', [])))
            ->map(fn (string $locale): string => (string) Lang::get('site.brand.name', [], $locale))
            ->map(fn (string $name): string => (string) Str::of($name)->trim())
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function defaultBreadcrumbNode(string $canonicalUrl, string $locale, string $pageName): ?array
    {
        $homeUrl = self::cleanUrl(localized_route('home', locale: $locale));

        if ($canonicalUrl === $homeUrl) {
            return null;
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $canonicalUrl.'#breadcrumb',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => (string) Lang::get('site.nav.home', [], $locale),
                    'item' => $homeUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $pageName,
                    'item' => $canonicalUrl,
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|list<array<string, mixed>>|null  $structuredData
     * @return list<array<string, mixed>>
     */
    private static function normalizeNodes(?array $structuredData): array
    {
        if ($structuredData === null || $structuredData === []) {
            return [];
        }

        if (isset($structuredData['@graph']) && is_array($structuredData['@graph'])) {
            return array_values(array_filter(
                $structuredData['@graph'],
                fn (mixed $node): bool => is_array($node),
            ));
        }

        if (array_is_list($structuredData)) {
            return array_values(array_filter(
                $structuredData,
                fn (mixed $node): bool => is_array($node),
            ));
        }

        unset($structuredData['@context']);

        return [$structuredData];
    }
}
