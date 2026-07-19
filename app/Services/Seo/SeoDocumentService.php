<?php

namespace App\Services\Seo;

use App\Support\Editorial\Article;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\HttpFoundation\Response;

class SeoDocumentService
{
    /** @var list<string> */
    private const LOCALES = ['ar', 'en'];

    /** @var list<string> */
    private const PUBLIC_ROUTE_NAMES = [
        'home',
        'services',
        'work',
        'writing',
        'about',
        'contact',
        'privacy',
        'cookies',
        'terms',
    ];

    public function __construct(private readonly ArticleCatalog $articles) {}

    public function sitemapResponse(Request $request): Response
    {
        return $this->withRevalidation(
            $request,
            $this->sitemap()->toResponse($request),
        );
    }

    public function robotsResponse(Request $request): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /reader',
            'Disallow: /en/reader',
            '',
            'Sitemap: '.$this->canonicalUrl('/sitemap.xml'),
            '',
        ]);

        return $this->withRevalidation(
            $request,
            response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']),
        );
    }

    public function sitemap(): Sitemap
    {
        $sitemap = Sitemap::create();

        foreach (self::PUBLIC_ROUTE_NAMES as $routeName) {
            $localizedUrls = $this->localizedRouteUrls($routeName);

            foreach (self::LOCALES as $locale) {
                $sitemap->add($this->localizedTag($localizedUrls, $locale));
            }
        }

        foreach ($this->articles->all() as $article) {
            $this->addArticle($sitemap, $article);
        }

        return $sitemap;
    }

    private function addArticle(Sitemap $sitemap, Article $article): void
    {
        $localizedUrls = [];

        foreach (self::LOCALES as $locale) {
            $localizedUrls[$locale] = $this->canonicalUrl(
                $this->articles->url($article, $locale, false),
            );
        }

        foreach (self::LOCALES as $locale) {
            $localized = $article->localized($locale);
            $tag = $this->localizedTag($localizedUrls, $locale)
                ->setLastModificationDate(Carbon::parse($article->modifiedAt));
            $imageUrl = $this->canonicalAssetUrl($article->image);

            if ($imageUrl !== null) {
                $tag->addImage($imageUrl, (string) $localized['title']);
            }

            $sitemap->add($tag);
        }
    }

    /**
     * @param  array{ar: string, en: string}  $localizedUrls
     */
    private function localizedTag(array $localizedUrls, string $locale): Url
    {
        $tag = Url::create($localizedUrls[$locale]);

        foreach (self::LOCALES as $alternateLocale) {
            $tag->addAlternate($localizedUrls[$alternateLocale], $alternateLocale);
        }

        return $tag->addAlternate($localizedUrls['ar'], 'x-default');
    }

    /**
     * @return array{ar: string, en: string}
     */
    private function localizedRouteUrls(string $routeName): array
    {
        return [
            'ar' => $this->canonicalUrl(localized_route($routeName, absolute: false, locale: 'ar')),
            'en' => $this->canonicalUrl(localized_route($routeName, absolute: false, locale: 'en')),
        ];
    }

    private function canonicalAssetUrl(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return $this->canonicalUrl($path);
    }

    private function canonicalUrl(string $path): string
    {
        return Str::of((string) config('app.url'))
            ->rtrim('/')
            ->append('/')
            ->append(Str::ltrim($path, '/'))
            ->toString();
    }

    private function withRevalidation(Request $request, Response $response): Response
    {
        $response->setEtag(hash('sha256', (string) $response->getContent()));
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);
        $response->headers->addCacheControlDirective('must-revalidate');
        $response->isNotModified($request);

        return $response;
    }
}
