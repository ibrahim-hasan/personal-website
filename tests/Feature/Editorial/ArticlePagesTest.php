<?php

namespace Tests\Feature\Editorial;

use App\Models\ArticleAudio;
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

        $this->get(parse_url($arabicUrl, PHP_URL_PATH))
            ->assertOk()
            ->assertSee($article->localized('ar')['title'], false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('hreflang="en" href="'.$englishUrl.'"', false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('data-reader-mode-toggle', false)
            ->assertDontSee('data-speech-play', false)
            ->assertDontSee('data-speech-rate', false)
            ->assertDontSee('data-article-audio-player', false);

        $this->get(parse_url($englishUrl, PHP_URL_PATH))
            ->assertOk()
            ->assertSee($article->localized('en')['title'], false)
            ->assertSee('hreflang="ar" href="'.$arabicUrl.'"', false)
            ->assertSee('data-article-lang="en"', false);
    }

    public function test_current_stored_audio_is_rendered_as_a_secure_mp3_player_and_audio_object(): void
    {
        Storage::fake('public');
        $catalog = app(ArticleCatalog::class);
        $article = $catalog->all()[0];
        $hash = app(ArticleNarrationScript::class)->fingerprint($article, 'ar');
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
            ->assertSee('data-site-audio-player', false)
            ->assertSee('data-article-audio-source', false)
            ->assertSee('data-article-audio-element', false)
            ->assertSee('data-audio-duration-seconds="1069"', false)
            ->assertSee('wire:navigate', false)
            ->assertSee('/storage/'.$path, false)
            ->assertSee('"@type":"AudioObject"', false)
            ->assertDontSee('ELEVENLABS_API_KEY', false)
            ->assertDontSee('server-only-secret', false);
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
    }

    public function test_article_slugs_are_explicitly_localized_and_unknown_slugs_return_not_found(): void
    {
        $catalog = app(ArticleCatalog::class);
        $article = $catalog->all()[0];

        $this->get('/writing/'.$article->slug('en'))->assertNotFound();
        $this->get('/en/writing/'.rawurlencode($article->slug('ar')))->assertNotFound();
        $this->get('/writing/not-a-real-article')->assertNotFound();
    }
}
