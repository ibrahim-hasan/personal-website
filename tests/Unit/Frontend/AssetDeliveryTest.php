<?php

namespace Tests\Unit\Frontend;

use Tests\TestCase;

class AssetDeliveryTest extends TestCase
{
    public function test_fonts_are_emitted_as_cacheable_assets_instead_of_inline_css(): void
    {
        $viteConfig = file_get_contents(base_path('vite.config.js'));
        $stylesheet = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('assetsInlineLimit: 0', $viteConfig);
        $this->assertStringNotContainsString('assetsInlineLimit: 100000', $viteConfig);
        $this->assertGreaterThanOrEqual(10, substr_count($stylesheet, 'font-display: swap'));
        $this->assertStringNotContainsString('data:font/', $stylesheet);
    }

    public function test_static_assets_have_explicit_compression_and_cache_rules(): void
    {
        $serverRules = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('mod_deflate.c', $serverRules);
        $this->assertStringContainsString('^/build/assets/', $serverRules);
        $this->assertStringContainsString('max-age=31536000, immutable', $serverRules);
        $this->assertStringContainsString('stale-while-revalidate=86400', $serverRules);
    }

    public function test_hero_video_adapts_between_high_quality_and_compact_delivery(): void
    {
        $this->assertLessThan(3.6 * 1024 * 1024, filesize(public_path('videos/hero/ibrahim-hero.mp4')));
        $this->assertLessThan(4.5 * 1024 * 1024, filesize(public_path('videos/hero/ibrahim-hero.webm')));
        $this->assertLessThan(2.1 * 1024 * 1024, filesize(public_path('videos/hero/ibrahim-hero-689778bf.mp4')));
        $this->assertLessThan(1.8 * 1024 * 1024, filesize(public_path('videos/hero/ibrahim-hero-0ab509e4.webm')));
        $this->assertLessThan(50 * 1024, filesize(public_path('images/ibrahim/ibrahim-hero-video-poster.webp')));
    }

    public function test_google_analytics_loader_is_part_of_the_frontend_bundle(): void
    {
        $entrypoint = file_get_contents(resource_path('js/app.js'));
        $loader = file_get_contents(resource_path('js/google-analytics.js'));

        $this->assertStringContainsString("import './google-analytics';", $entrypoint);
        $this->assertStringContainsString('meta[name="google-analytics-id"]', $loader);
        $this->assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=', $loader);
        $this->assertStringContainsString("window.gtag('config', measurementId, {", $loader);
        $this->assertStringContainsString('send_page_view: false', $loader);
    }
}
