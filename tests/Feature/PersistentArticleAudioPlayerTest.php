<?php

namespace Tests\Feature;

use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleAudioScript;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersistentArticleAudioPlayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ArticleSeeder::class);
    }

    public function test_published_audio_exposes_a_localized_track_source_without_loading_it_in_initial_markup(): void
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

        $canonicalUrl = $catalog->url($article, 'ar');

        $this->get(parse_url($canonicalUrl, PHP_URL_PATH))
            ->assertOk()
            ->assertSee('data-article-audio-source', false)
            ->assertSee('data-audio-article-slug="'.$article->slug('ar').'"', false)
            ->assertSee('data-audio-article-url="'.$canonicalUrl.'"', false)
            ->assertSee('data-audio-locale="ar"', false)
            ->assertSee('data-audio-dir="rtl"', false)
            ->assertSee('data-site-audio-player', false)
            ->assertSee('data-site-audio-back', false)
            ->assertSee('data-site-audio-forward', false)
            ->assertSee('data-site-audio-article-link', false)
            ->assertSee('preload="none"', false)
            ->assertDontSee('<source ', false);
    }

    public function test_persistent_player_keeps_only_user_initiated_state_and_has_accessible_controls(): void
    {
        $player = $this->readProjectFile('resources/views/components/partials/article-audio-player.blade.php');
        $reader = $this->readProjectFile('resources/js/article-reader.js');
        $arabic = $this->readProjectFile('lang/ar/articles.php');
        $english = $this->readProjectFile('lang/en/articles.php');
        $css = $this->readProjectFile('resources/css/app.css');

        $this->assertStringContainsString('data-site-audio-player', $player);
        $this->assertStringContainsString('hidden', $player);
        $this->assertStringContainsString('data-site-audio-back', $player);
        $this->assertStringContainsString('data-site-audio-forward', $player);
        $this->assertStringContainsString('data-site-audio-article-link', $player);
        $this->assertStringContainsString('data-no-navigate', $player);
        $this->assertStringContainsString('data-article-audio-element preload="none"', $player);
        $this->assertStringContainsString('class="site-audio-player__time" aria-hidden="true"', $player);
        $this->assertStringContainsString('aria-live="polite"', $player);

        $this->assertStringContainsString('state.initiated !== true', $reader);
        $this->assertStringContainsString('initiated: true', $reader);
        $this->assertStringContainsString('initiatedByUser: true', $reader);
        $this->assertStringNotContainsString('activateSource(pageSource)', $reader);
        $this->assertStringContainsString("audio.removeAttribute('src')", $reader);
        $this->assertStringContainsString('safeStorage.remove(AUDIO_STATE_STORAGE_KEY)', $reader);
        $this->assertStringContainsString('safeStorage.remove(AUDIO_RATE_STORAGE_KEY)', $reader);
        $this->assertStringContainsString('applyTrackLanguage(audio, source)', $reader);
        $this->assertStringContainsString('audio_start', $reader);
        $this->assertStringContainsString('audio_complete', $reader);
        $this->assertStringContainsString('window.IbrahimAnalytics?.track', $reader);
        $this->assertStringNotContainsString('window.gtag', $reader);
        $this->assertStringContainsString('articleReaderController?.abort()', $reader);
        $this->assertStringContainsString('launchController?.abort()', $reader);
        $this->assertStringContainsString("document.addEventListener('livewire:navigating'", $reader);

        $this->assertStringContainsString("'back_15' => 'الرجوع 15 ثانية'", $arabic);
        $this->assertStringContainsString("'forward_15' => 'التقديم 15 ثانية'", $arabic);
        $this->assertStringContainsString("'unavailable' => 'الصوت غير متاح الآن.'", $arabic);
        $this->assertStringContainsString("'continue_reading' => 'العودة إلى المقال'", $arabic);
        $this->assertStringContainsString("'back_15' => 'Back 15 seconds'", $english);
        $this->assertStringContainsString("'forward_15' => 'Forward 15 seconds'", $english);
        $this->assertStringContainsString("'unavailable' => 'Audio is unavailable right now.'", $english);
        $this->assertStringContainsString("'continue_reading' => 'Return to article'", $english);

        $this->assertMatchesRegularExpression(
            '/\.site-audio-player__transport\s*\{[^}]*display:\s*flex;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.site-audio-player__toggle,\s*\.site-audio-player__seek\s*\{[^}]*width:\s*2\.75rem;[^}]*height:\s*2\.75rem;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/@media print\s*\{.*?\.site-audio-player,.*?\{\s*display:\s*none !important;/s',
            $css,
        );
    }

    private function readProjectFile(string $path): string
    {
        $contents = file_get_contents(base_path($path));

        $this->assertNotFalse($contents, "Unable to read {$path}.");

        return $contents;
    }
}
