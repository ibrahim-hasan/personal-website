<?php

namespace Tests\Feature\Seo;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_default_social_card_is_localized_complete_and_does_not_repeat_the_site_name(): void
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $secureSiteUrl = preg_replace('/^http:/', 'https:', $siteUrl);
        $arabicTitle = $this->escapedTranslation('site.home.title', 'ar');
        $arabicDescription = $this->escapedTranslation('site.home.description', 'ar');
        $arabicSiteName = $this->escapedTranslation('site.brand.name', 'ar');

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>'.$arabicTitle.'</title>', false)
            ->assertDontSee('<title>'.$arabicTitle.' | '.$arabicSiteName.'</title>', false)
            ->assertSee('<meta property="og:title" content="'.$arabicTitle.'">', false)
            ->assertSee('<meta property="og:description" content="'.$arabicDescription.'">', false)
            ->assertSee('<meta property="og:image" content="'.$siteUrl.'/images/social/ibrahim-hasan-share-ar-v1.jpg">', false)
            ->assertSee('<meta property="og:image:secure_url" content="'.$secureSiteUrl.'/images/social/ibrahim-hasan-share-ar-v1.jpg">', false)
            ->assertSee('<meta property="og:image:type" content="image/jpeg">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta property="og:image:height" content="630">', false)
            ->assertSee('<meta property="og:image:alt" content="'.$this->escapedTranslation('site.meta.social_image_alt', 'ar').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta name="twitter:title" content="'.$arabicTitle.'">', false)
            ->assertSee('<meta name="twitter:description" content="'.$arabicDescription.'">', false)
            ->assertSee('<meta name="twitter:image" content="'.$siteUrl.'/images/social/ibrahim-hasan-share-ar-v1.jpg">', false)
            ->assertSee('<meta name="twitter:image:alt" content="'.$this->escapedTranslation('site.meta.social_image_alt', 'ar').'">', false);

        $englishTitle = $this->escapedTranslation('site.home.title', 'en');
        $englishDescription = $this->escapedTranslation('site.home.description', 'en');
        $englishSiteName = $this->escapedTranslation('site.brand.name', 'en');

        $this->get('/en')
            ->assertOk()
            ->assertSee('<title>'.$englishTitle.'</title>', false)
            ->assertDontSee('<title>'.$englishTitle.' | '.$englishSiteName.'</title>', false)
            ->assertSee('<meta property="og:title" content="'.$englishTitle.'">', false)
            ->assertSee('<meta property="og:description" content="'.$englishDescription.'">', false)
            ->assertSee('<meta property="og:image" content="'.$siteUrl.'/images/social/ibrahim-hasan-share-en-v1.jpg">', false)
            ->assertSee('<meta property="og:image:secure_url" content="'.$secureSiteUrl.'/images/social/ibrahim-hasan-share-en-v1.jpg">', false)
            ->assertSee('<meta property="og:image:type" content="image/jpeg">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta property="og:image:height" content="630">', false)
            ->assertSee('<meta property="og:image:alt" content="'.$this->escapedTranslation('site.meta.social_image_alt', 'en').'">', false)
            ->assertSee('<meta name="twitter:image" content="'.$siteUrl.'/images/social/ibrahim-hasan-share-en-v1.jpg">', false)
            ->assertSee('<meta name="twitter:image:alt" content="'.$this->escapedTranslation('site.meta.social_image_alt', 'en').'">', false);
    }

    public function test_article_metadata_uses_a_dedicated_open_graph_conversion_when_media_is_available(): void
    {
        Storage::fake('public');

        $article = Article::factory()->create([
            'key' => 'open-graph-media',
            'slug' => ['ar' => 'بطاقة-مشاركة', 'en' => 'social-card'],
            'image' => null,
        ]);
        $article
            ->addMedia(UploadedFile::fake()->image('social-card.jpg', 1800, 1200))
            ->toMediaCollection(Article::IMAGE_COLLECTION);

        $openGraphImage = $article->fresh()->openGraphImage();
        $response = $this->get(localized_route('writing.show', ['article' => $article], locale: 'ar'));
        $secureImageUrl = preg_replace('/^http:/', 'https:', $openGraphImage['src']);

        $response
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.$openGraphImage['src'].'">', false)
            ->assertSee('<meta property="og:image:secure_url" content="'.$secureImageUrl.'">', false)
            ->assertSee('<meta property="og:image:type" content="image/jpeg">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta property="og:image:height" content="630">', false)
            ->assertSee('<meta name="twitter:image" content="'.$openGraphImage['src'].'">', false);
    }

    public function test_article_metadata_falls_back_to_its_legacy_image_when_no_managed_media_exists(): void
    {
        $article = Article::factory()->create([
            'key' => 'open-graph-fallback',
            'slug' => ['ar' => 'صورة-مقال-بديلة', 'en' => 'article-image-fallback'],
            'image' => 'images/ibrahim/hero-workspace.png',
        ]);
        $fallbackImage = $article->openGraphImage();

        $this->get(localized_route('writing.show', ['article' => $article], locale: 'ar'))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.$fallbackImage['src'].'">', false)
            ->assertSee('<meta property="og:image:type" content="image/png">', false)
            ->assertSee('<meta property="og:image:width" content="1586">', false)
            ->assertSee('<meta property="og:image:height" content="992">', false);
    }

    private function escapedTranslation(string $key, string $locale): string
    {
        return htmlspecialchars((string) Lang::get($key, [], $locale), ENT_QUOTES, 'UTF-8');
    }
}
