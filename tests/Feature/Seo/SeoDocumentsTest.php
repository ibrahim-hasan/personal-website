<?php

namespace Tests\Feature\Seo;

use App\Models\Article;
use Database\Seeders\ArticleSeeder;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SeoDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private const CANONICAL_ROOT = 'https://ibrahimhasan.net';

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

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => self::CANONICAL_ROOT]);
    }

    public function test_sitemap_contains_only_public_bilingual_pages_with_reciprocal_alternates(): void
    {
        $this->seed(ArticleSeeder::class);

        $draft = Article::factory()->create([
            'key' => 'private-draft',
            'slug' => ['ar' => 'مسودة-خاصة', 'en' => 'private-draft'],
            'is_published' => false,
        ]);
        $future = Article::factory()->create([
            'key' => 'future-article',
            'slug' => ['ar' => 'مقال-مستقبلي', 'en' => 'future-article'],
            'published_at' => today()->addDay(),
        ]);
        $deleted = Article::factory()->create([
            'key' => 'deleted-article',
            'slug' => ['ar' => 'مقال-محذوف', 'en' => 'deleted-article'],
        ]);
        $deleted->delete();

        $response = $this->get('/sitemap.xml')->assertOk();
        $contentType = (string) $response->headers->get('Content-Type');

        $this->assertStringStartsWith('text/xml', $contentType);
        $this->assertFalse($response->headers->has('Set-Cookie'));

        [$document, $xpath] = $this->parseSitemap((string) $response->getContent());
        $expectedUrlCount = (count(self::PUBLIC_ROUTE_NAMES) + Article::query()->published()->count()) * 2;

        $this->assertSame(
            $expectedUrlCount,
            $xpath->query('/sm:urlset/sm:url')->count(),
        );

        foreach (self::PUBLIC_ROUTE_NAMES as $routeName) {
            $arUrl = $this->canonicalUrl(localized_route($routeName, absolute: false, locale: 'ar'));
            $enUrl = $this->canonicalUrl(localized_route($routeName, absolute: false, locale: 'en'));

            $this->assertAlternates($xpath, $this->urlNode($xpath, $arUrl), $arUrl, $enUrl);
            $this->assertAlternates($xpath, $this->urlNode($xpath, $enUrl), $arUrl, $enUrl);
        }

        $article = Article::query()->where('key', 'ai-value')->firstOrFail();
        $arUrl = $this->articleUrl($article, 'ar');
        $enUrl = $this->articleUrl($article, 'en');
        $arNode = $this->urlNode($xpath, $arUrl);
        $enNode = $this->urlNode($xpath, $enUrl);

        $this->assertAlternates($xpath, $arNode, $arUrl, $enUrl);
        $this->assertAlternates($xpath, $enNode, $arUrl, $enUrl);
        $this->assertSame(
            $article->modified_at->toDateString(),
            Carbon::parse($xpath->query('./sm:lastmod', $arNode)->item(0)->textContent)->toDateString(),
        );
        $this->assertSame(
            $this->canonicalUrl($article->imageUrl()),
            $xpath->query('./image:image/image:loc', $arNode)->item(0)->textContent,
        );
        $this->assertSame(
            $article->getTranslation('title', 'ar'),
            $xpath->query('./image:image/image:caption', $arNode)->item(0)->textContent,
        );

        foreach ([$draft, $future, $deleted] as $excludedArticle) {
            $this->assertNull($this->findUrlNode($xpath, $this->articleUrl($excludedArticle, 'ar')));
            $this->assertNull($this->findUrlNode($xpath, $this->articleUrl($excludedArticle, 'en')));
        }

        $this->assertStringNotContainsString('/admin', $document->saveXML());
        $this->assertStringNotContainsString('/reader', $document->saveXML());
    }

    public function test_sitemap_supports_conditional_cache_revalidation(): void
    {
        $this->seed(ArticleSeeder::class);

        $response = $this->get('/sitemap.xml')->assertOk();
        $etag = (string) $response->headers->get('ETag');
        $cacheControl = (string) $response->headers->get('Cache-Control');

        $this->assertNotSame('', $etag);
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=3600', $cacheControl);

        $this->withHeader('If-None-Match', $etag)
            ->get('/sitemap.xml')
            ->assertStatus(304);
    }

    public function test_robots_declares_the_canonical_sitemap_and_excludes_private_surfaces(): void
    {
        $response = $this->get('/robots.txt')->assertOk();
        $content = (string) $response->getContent();

        $this->assertStringStartsWith('text/plain', (string) $response->headers->get('Content-Type'));
        $this->assertFalse($response->headers->has('Set-Cookie'));
        $this->assertStringContainsString("User-agent: *\n", $content);
        $this->assertStringContainsString("Allow: /\n", $content);
        $this->assertStringContainsString("Disallow: /admin\n", $content);
        $this->assertStringContainsString("Disallow: /reader\n", $content);
        $this->assertStringContainsString("Disallow: /en/reader\n", $content);
        $this->assertStringContainsString(
            'Sitemap: '.self::CANONICAL_ROOT.'/sitemap.xml',
            $content,
        );
        $this->assertSame($content, file_get_contents(public_path('robots.txt')));
        $this->assertNotSame('', (string) $response->headers->get('ETag'));

        $this->get('/en/sitemap.xml')->assertNotFound();
        $this->get('/en/robots.txt')->assertNotFound();
    }

    /**
     * @return array{DOMDocument, DOMXPath}
     */
    private function parseSitemap(string $xml): array
    {
        $document = new DOMDocument;

        $this->assertTrue($document->loadXML($xml));

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $xpath->registerNamespace('xhtml', 'http://www.w3.org/1999/xhtml');
        $xpath->registerNamespace('image', 'http://www.google.com/schemas/sitemap-image/1.1');

        return [$document, $xpath];
    }

    private function assertAlternates(
        DOMXPath $xpath,
        DOMElement $node,
        string $arUrl,
        string $enUrl,
    ): void {
        $this->assertSame(
            $arUrl,
            $xpath->query('./xhtml:link[@hreflang="ar"]', $node)->item(0)->getAttribute('href'),
        );
        $this->assertSame(
            $enUrl,
            $xpath->query('./xhtml:link[@hreflang="en"]', $node)->item(0)->getAttribute('href'),
        );
        $this->assertSame(
            $arUrl,
            $xpath->query('./xhtml:link[@hreflang="x-default"]', $node)->item(0)->getAttribute('href'),
        );
    }

    private function urlNode(DOMXPath $xpath, string $url): DOMElement
    {
        $node = $this->findUrlNode($xpath, $url);

        $this->assertInstanceOf(DOMElement::class, $node, "Missing sitemap URL: {$url}");

        return $node;
    }

    private function findUrlNode(DOMXPath $xpath, string $url): ?DOMElement
    {
        foreach ($xpath->query('/sm:urlset/sm:url') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if ($xpath->query('./sm:loc', $node)->item(0)?->textContent === $url) {
                return $node;
            }
        }

        return null;
    }

    private function articleUrl(Article $article, string $locale): string
    {
        return $this->canonicalUrl(localized_route(
            'writing.show',
            ['article' => $article->getTranslation('slug', $locale)],
            false,
            $locale,
        ));
    }

    private function canonicalUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return self::CANONICAL_ROOT.'/'.ltrim($path, '/');
    }
}
