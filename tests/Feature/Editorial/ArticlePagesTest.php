<?php

namespace Tests\Feature\Editorial;

use App\Models\Article as ArticleRecord;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleAudioScript;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticlePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ArticleSeeder::class);
    }

    public function test_writing_index_presents_the_complete_arabic_and_english_libraries(): void
    {
        $catalog = app(ArticleCatalog::class);

        $arabicResponse = $this->get('/writing')->assertOk();
        $englishResponse = $this->get('/en/writing')->assertOk();

        foreach ($catalog->localized('ar') as $article) {
            $arabicResponse
                ->assertSee($article['title'], false)
                ->assertSee($article['url'], false);
        }

        foreach ($catalog->localized('en') as $article) {
            $englishResponse
                ->assertSee($article['title'], false)
                ->assertSee($article['url'], false);
        }
    }

    public function test_article_page_has_localized_metadata_and_reader_mode_without_browser_tts(): void
    {
        $catalog = app(ArticleCatalog::class);
        $article = $catalog->all()[0];
        $arabicUrl = $catalog->url($article, 'ar');
        $englishUrl = $catalog->url($article, 'en');

        $arabicArticle = $article->localized('ar');
        $firstContentsLabel = preg_replace('/^\s*[\d٠-٩]+[.)-]\s*/u', '', $arabicArticle['sections'][0]['heading']);

        $this->get(parse_url($arabicUrl, PHP_URL_PATH))
            ->assertOk()
            ->assertSee($arabicArticle['title'], false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('hreflang="en" href="'.$englishUrl.'"', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('data-reader-mode-toggle', false)
            ->assertSee('<span class="article-contents__number">01</span>', false)
            ->assertSee('<span class="article-contents__label">'.$firstContentsLabel.'</span>', false)
            ->assertDontSee('class="article-consultation"', false)
            ->assertDontSee('data-speech-play', false)
            ->assertDontSee('data-speech-rate', false)
            ->assertDontSee('data-article-audio-player', false);

        $this->get(parse_url($englishUrl, PHP_URL_PATH))
            ->assertOk()
            ->assertSee($article->localized('en')['title'], false)
            ->assertSee('hreflang="ar" href="'.$arabicUrl.'"', false)
            ->assertSee('data-article-lang="en"', false);
    }

    public function test_article_sharing_uses_the_localized_canonical_url_and_safe_intents(): void
    {
        $catalog = app(ArticleCatalog::class);
        $article = $catalog->all()[0];
        $arabicArticle = $article->localized('ar');
        $arabicUrl = $catalog->url($article, 'ar');
        $englishUrl = $catalog->url($article, 'en');
        $qabilahUrl = 'https://qabilah.com/discover/following?'.http_build_query(['title' => $arabicArticle['title'], 'text' => $arabicArticle['summary'], 'url' => $arabicUrl], '', '&amp;', PHP_QUERY_RFC3986).'#write-post';

        $this->get(parse_url($arabicUrl, PHP_URL_PATH).'?utm_source=ignored')
            ->assertOk()
            ->assertSee('class="article-share"', false)
            ->assertSee('class="site-container article-share-rail"', false)
            ->assertDontSee('IBRAHIM HASAN / FIELD NOTES', false)
            ->assertSee('data-share-url="'.$arabicUrl.'"', false)
            ->assertDontSee('data-share-url="'.$arabicUrl.'?utm_source=ignored"', false)
            ->assertSee('شارك المقال', false)
            ->assertSee('نسخ الرابط', false)
            ->assertSee('https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($arabicUrl), false)
            ->assertSee('https://wa.me/?text='.rawurlencode($arabicArticle['title']."\n".$arabicUrl), false)
            ->assertSee($qabilahUrl, false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('width="1600"', false)
            ->assertSee('height="900"', false)
            ->assertSee('alt="'.$arabicArticle['title'].'"', false);

        $this->get(parse_url($englishUrl, PHP_URL_PATH))
            ->assertOk()
            ->assertSee('data-share-url="'.$englishUrl.'"', false)
            ->assertSee('Share article', false)
            ->assertSee('Share on Qabilah', false)
            ->assertSee('Copy link', false);
    }

    public function test_writing_featured_image_reserves_its_real_aspect_ratio_and_has_localized_alt_text(): void
    {
        $catalog = app(ArticleCatalog::class);
        $featuredArticle = $catalog->localized('en')[0];

        $this->get('/en/writing')
            ->assertOk()
            ->assertSee('width="1600"', false)
            ->assertSee('height="900"', false)
            ->assertSee('alt="'.$featuredArticle['title'].'"', false);
    }

    public function test_homepage_uses_a_precise_library_link_when_no_featured_article_has_audio(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('استكشف مكتبة الأفكار', false)
            ->assertDontSee('class="writing-audio-feature"', false);

        $this->get('/en')
            ->assertOk()
            ->assertSee('Explore the field notes', false)
            ->assertDontSee('class="writing-audio-feature"', false);
    }

    public function test_article_metadata_connects_social_tags_to_a_complete_schema_graph(): void
    {
        $catalog = app(ArticleCatalog::class);
        $article = $catalog->all()[0];
        $localizedArticle = $article->localized('ar');
        $canonicalUrl = $catalog->url($article, 'ar');
        $authorUrl = localized_route('about', locale: 'ar');
        $response = $this->get(parse_url($canonicalUrl, PHP_URL_PATH));

        $response
            ->assertOk()
            ->assertSee('<meta property="og:locale" content="ar_AE">', false)
            ->assertSee('<meta property="og:locale:alternate" content="en_US">', false)
            ->assertSee('<meta property="og:image:type" content="image/webp">', false)
            ->assertSee('<meta property="og:image:width" content="1122">', false)
            ->assertSee('<meta property="og:image:height" content="1402">', false)
            ->assertSee('<meta property="og:image:alt" content="'.$localizedArticle['title'].'">', false)
            ->assertSee('<meta name="twitter:image:alt" content="'.$localizedArticle['title'].'">', false)
            ->assertSee('<meta property="article:author" content="'.$authorUrl.'">', false)
            ->assertSee('<meta property="article:section" content="'.$localizedArticle['type'].'">', false);

        foreach ($localizedArticle['topics'] as $topic) {
            $response->assertSee('<meta property="article:tag" content="'.$topic.'">', false);
        }

        $schema = $this->structuredData($response->getContent());
        $graph = collect($schema['@graph']);
        $articleNode = $graph->firstWhere('@type', 'BlogPosting');
        $breadcrumbNode = $graph->firstWhere('@type', 'BreadcrumbList');
        $pageNode = $graph->firstWhere('@type', 'WebPage');
        $personNode = $graph->firstWhere('@type', 'Person');

        $this->assertIsArray($articleNode);
        $this->assertIsArray($breadcrumbNode);
        $this->assertIsArray($pageNode);
        $this->assertIsArray($personNode);
        $this->assertSame($canonicalUrl, $articleNode['url']);
        $this->assertSame($localizedArticle['published_at'], $articleNode['datePublished']);
        $this->assertSame($localizedArticle['modified_at'], $articleNode['dateModified']);
        $this->assertSame($localizedArticle['type'], $articleNode['articleSection']);
        $this->assertSame($localizedArticle['topics'], $articleNode['keywords']);
        $this->assertSame($personNode['@id'], $articleNode['author']['@id']);
        $this->assertSame($authorUrl, $personNode['url']);
        $this->assertSame($pageNode['@id'], $articleNode['mainEntityOfPage']['@id']);
        $this->assertSame($articleNode['@id'], $pageNode['mainEntity']['@id']);
        $this->assertSame($breadcrumbNode['@id'], $pageNode['breadcrumb']['@id']);
        $this->assertCount(3, $breadcrumbNode['itemListElement']);
    }

    public function test_article_json_ld_cannot_be_broken_out_of_its_script_element(): void
    {
        $maliciousTitle = '</script><script>alert("metadata")</script>';
        $record = ArticleRecord::query()->where('key', 'ai-value')->firstOrFail();
        $record->setTranslation('title', 'ar', $maliciousTitle);
        $record->setTranslation('seo_title', 'ar', $maliciousTitle);
        $record->save();

        $catalog = app(ArticleCatalog::class);
        $article = $catalog->findByKey('ai-value');

        $this->assertNotNull($article);

        $response = $this->get(parse_url($catalog->url($article, 'ar'), PHP_URL_PATH))->assertOk();
        $schema = $this->structuredData($response->getContent());
        $articleNode = collect($schema['@graph'])->firstWhere('@type', 'BlogPosting');

        $this->assertStringNotContainsString('</script><script>', $response->getContent());
        $this->assertSame($maliciousTitle, $articleNode['headline']);
    }

    public function test_current_stored_audio_is_rendered_as_a_secure_mp3_player_and_audio_object(): void
    {
        Storage::fake('public');
        $catalog = app(ArticleCatalog::class);
        $article = $catalog->all()[0];
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');
        ArticleNarration::factory()->create([
            'article_key' => $article->key,
            'locale' => 'ar',
            'source_hash' => hash('sha256', $source),
            'script' => $source,
        ]);
        $hash = app(ArticleAudioScript::class)->approved($article, 'ar', 'eleven_multilingual_v2', allowCurrentDraft: true)?->contentHash;
        $this->assertNotNull($hash);
        $path = 'article-audio/ar/'.$article->key.'-'.$hash.'.mp3';
        Storage::disk('public')->put($path, 'mp3-content');

        ArticleAudio::factory()->create([
            'article_key' => $article->key,
            'locale' => 'ar',
            'content_hash' => $hash,
            'path' => $path,
            'file_size' => 17106274,
            'output_format' => 'mp3_44100_128',
        ]);

        $response = $this->get(parse_url($catalog->url($article, 'ar'), PHP_URL_PATH));

        $response
            ->assertOk()
            ->assertSee('id="article-audio"', false)
            ->assertSee('data-site-audio-player', false)
            ->assertSee('data-article-audio-source', false)
            ->assertSee('data-article-audio-element', false)
            ->assertSee('data-audio-duration-seconds="1069"', false)
            ->assertSee('wire:navigate', false)
            ->assertSee('/storage/'.$path, false)
            ->assertSee('"@type":"AudioObject"', false)
            ->assertDontSee('ELEVENLABS_API_KEY', false)
            ->assertDontSee('server-only-secret', false);

        $this->get('/')
            ->assertOk()
            ->assertSee('class="writing-audio-feature"', false)
            ->assertSee('نسخة صوتية متاحة', false)
            ->assertSee('استمع إلى الفكرة كاملة.', false)
            ->assertSee('href="'.$catalog->url($article, 'ar').'#article-audio"', false)
            ->assertSee($article->localized('ar')['title'], false);
    }

    public function test_stale_audio_is_not_exposed_after_article_content_changes(): void
    {
        Storage::fake('public');
        $catalog = app(ArticleCatalog::class);
        $article = $catalog->all()[0];
        $path = 'article-audio/ar/stale.mp3';
        Storage::disk('public')->put($path, 'stale-content');

        ArticleAudio::factory()->create([
            'article_key' => $article->key,
            'locale' => 'ar',
            'content_hash' => str_repeat('0', 64),
            'path' => $path,
        ]);

        $this->get(parse_url($catalog->url($article, 'ar'), PHP_URL_PATH))
            ->assertOk()
            ->assertDontSee('data-article-audio-player', false)
            ->assertDontSee(Storage::disk('public')->url($path), false);

        $this->get('/')
            ->assertOk()
            ->assertSee('استكشف مكتبة الأفكار', false)
            ->assertDontSee('class="writing-audio-feature"', false);
    }

    public function test_article_slugs_are_explicitly_localized_and_unknown_slugs_return_not_found(): void
    {
        $catalog = app(ArticleCatalog::class);
        $article = $catalog->all()[0];

        $this->get('/writing/'.$article->slug('en'))->assertNotFound();
        $this->get('/en/writing/'.rawurlencode($article->slug('ar')))->assertNotFound();
        $this->get('/writing/not-a-real-article')->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function structuredData(string $html): array
    {
        $matched = preg_match(
            '/<script type="application\/ld\+json">(.+?)<\/script>/s',
            $html,
            $matches,
        );

        $this->assertSame(1, $matched);

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }
}
