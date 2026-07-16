<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;

class PublicInteractionAccessibilityTest extends TestCase
{
    public function test_public_interactions_render_keyboard_and_semantic_contracts(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('class="precision-stage hero-enter"', false)
            ->assertDontSee('precision-stage__note', false)
            ->assertSee('data-hero-video', false)
            ->assertSee('aria-hidden="true"', false)
            ->assertSee('type="video/webm"', false)
            ->assertSee('type="video/mp4"', false)
            ->assertSee('x-ref="menuToggle"', false)
            ->assertSee('@keydown.tab="trapFocus($event)"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('width="94"', false)
            ->assertSee('height="34"', false);

        $this->get('/en/services')
            ->assertOk()
            ->assertSee('role="tablist"', false)
            ->assertSee('@keydown="navigate($event)"', false)
            ->assertSee(':aria-selected=', false)
            ->assertSee('id="service-tab-', false)
            ->assertSee('aria-controls="service-panel"', false)
            ->assertSee('id="service-panel"', false)
            ->assertSee('role="tabpanel"', false)
            ->assertSee(':aria-labelledby="\'service-tab-\' + active"', false)
            ->assertDontSee('id="service-tab-"', false)
            ->assertDontSee('service-tab-null', false);

        $this->get('/en/work')
            ->assertOk()
            ->assertSee('class="filter-bar" role="toolbar"', false)
            ->assertSee(':aria-pressed=', false)
            ->assertSee(':tabindex=', false)
            ->assertSee('class="case-study__identity"', false)
            ->assertSee('class="case-study__brand"', false)
            ->assertDontSee('class="project-brand"', false)
            ->assertDontSee('<figcaption>', false);

        $this->get('/en/writing')
            ->assertOk()
            ->assertSee('class="publication-topics" role="toolbar"', false)
            ->assertSee(':aria-pressed=', false)
            ->assertSee('class="publication-topics__arrow"', false)
            ->assertSee('@click="scrollTopics(1)"', false)
            ->assertSee('Show more topics', false)
            ->assertDontSee('class="statement-band"', false)
            ->assertSee('decoding="async"', false);
    }

    public function test_motion_preserves_content_and_responsive_controls_remain_contained(): void
    {
        $css = $this->readProjectFile('resources/css/app.css');
        $javascript = $this->readProjectFile('resources/js/app.js');

        $this->assertMatchesRegularExpression(
            '/\.motion-capable \[data-reveal\]\s*\{[^}]*opacity:\s*1;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 63\.999rem\)\s*\{.*?\.precision-stage\s*\{[^}]*width:\s*min\(100%, 22\.5rem\);[^}]*overflow:\s*clip;/s',
            $css,
        );
        $this->assertMatchesRegularExpression('/\.filter-bar\s*\{[^}]*overflow-x:\s*auto;/s', $css);
        $this->assertMatchesRegularExpression('/\.publication-topics\s*\{[^}]*overflow-x:\s*auto;/s', $css);
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 72rem\)\s*\{\s*\.publication-intro__grid\s*\{[^}]*grid-template-columns:/s',
            $css,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/@media \(min-width: 48rem\)\s*\{\s*\.publication-intro__grid\s*\{/s',
            $css,
        );
        $this->assertMatchesRegularExpression('/\.featured-essay\s*\{[^}]*min-width:\s*0;/s', $css);
        $this->assertMatchesRegularExpression(
            '/\.case-study__brand\s*\{[^}]*width:\s*7rem;[^}]*height:\s*4rem;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.case-study__brand img\s*\{[^}]*max-width:\s*5\.5rem;[^}]*max-height:\s*2\.35rem;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.article-prose__closing > span\s*\{[^}]*width:\s*0\.85rem;[^}]*height:\s*0\.85rem;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 63\.999rem\)\s*\{\s*\.related-writing__heading\s*\{[^}]*flex-direction:\s*column;[^}]*align-items:\s*start;/s',
            $css,
        );
        $this->assertMatchesRegularExpression('/\.text-link\s*\{[^}]*display:\s*inline-flex;/s', $css);
        $this->assertStringContainsString('scrollTopics(direction)', $javascript);
        $this->assertMatchesRegularExpression(
            "/\[data-reveal='copy'\]\s*\{[^}]*translate:\s*0 1\.75rem;/s",
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.precision-stage__video\s*\{[^}]*object-fit:\s*cover;[^}]*object-position:\s*center;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 64rem\)\s*\{.*?\.precision-stage\s*\{[^}]*width:\s*min\(100%, 24rem\);/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.precision-hero__grid\s*\{[^}]*position:\s*relative;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.precision-hero__orbit\s*\{[^}]*inset-block-start:\s*5rem;[^}]*left:\s*-2\.25rem;/s',
            $css,
        );
        $this->assertStringNotContainsString('precision-stage__orbit', $css);
        $this->assertStringContainsString('navigator.connection?.saveData === true', $javascript);
        $this->assertStringContainsString("document.querySelectorAll('[data-hero-video]')", $javascript);
        $this->assertStringContainsString('video.play().catch(() => {})', $javascript);
        $this->assertStringContainsString('visibilityObserver.disconnect()', $javascript);
        $this->assertStringNotContainsString('heroStage', $javascript);
        $this->assertStringNotContainsString('precision-stage__control', $css);
        $this->assertStringContainsString('clip-path: inset(-0.3em -0.08em -0.4em -0.08em)', $css);
        $this->assertMatchesRegularExpression(
            '/\.hero-sequence__steps::before\s*\{[^}]*background:\s*linear-gradient/s',
            $css,
        );
        $this->assertStringContainsString('.atlas-chapter:not(:first-child)::before', $css);
        $this->assertMatchesRegularExpression(
            '/\.experience-stage::after\s*\{[^}]*inset-inline:\s*0;[^}]*width:\s*100%;[^}]*background-size:\s*0 1px, 100% 1px;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.experience-stage\.is-revealed::after\s*\{[^}]*background-size:\s*4\.5rem 1px, 100% 1px;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.site-nav\s*\{[^}]*border-bottom:\s*1px solid transparent;[^}]*transition:\s*border-color 220ms ease, background-color 220ms ease;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.site-nav--scrolled\s*\{[^}]*border-bottom-color:/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.atlas-chapter::before\s*\{[^}]*z-index:\s*3;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 63\.999rem\)\s*\{.*?\.manifesto-section__grid\s*\{[^}]*min-height:\s*auto;/s',
            $css,
        );
        $this->assertStringNotContainsString('%3Ccircle', $css);
        $this->assertGreaterThanOrEqual(
            2,
            substr_count($css, "url('../../public/images/brand/ibrahim-geometric-pattern.svg')"),
        );
        $this->assertStringNotContainsString("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='640'", $css);

        $this->assertStringContainsString('const moveCompositeFocus = (event)', $javascript);
        $this->assertStringContainsString('trapFocus(event)', $javascript);
        $this->assertStringContainsString('window.location.hash.slice(1)', $javascript);
        $this->assertStringContainsString("window.history.replaceState(null, '', `#\${id}`)", $javascript);
        $this->assertStringContainsString('frontEnhancementController?.abort()', $javascript);
        $this->assertStringContainsString('new AbortController()', $javascript);
        $this->assertStringContainsString('revealObserver?.disconnect()', $javascript);
        $this->assertStringContainsString('{ passive: true, signal }', $javascript);

        $this->assertStringNotContainsString('architectural-braid', $css);
        $this->assertStringNotContainsString('data-pattern-motion', $javascript);

        $navbar = $this->readProjectFile('resources/views/components/partials/navbar.blade.php');

        $this->assertSame(2, substr_count($navbar, 'data-no-navigate'));
        $this->assertDoesNotMatchRegularExpression('/localized_current_url\([^)]*\)[^>]*wire:navigate/', $navbar);
    }

    public function test_reader_surfaces_use_the_site_palette_and_valid_landmarks(): void
    {
        $paths = [
            'resources/views/auth/reader-login.blade.php',
            'resources/views/auth/reader-register.blade.php',
            'resources/views/auth/reader-verify-email.blade.php',
            'resources/views/auth/reader-forgot-password.blade.php',
            'resources/views/auth/reader-reset-password.blade.php',
            'resources/views/website/reader-library.blade.php',
            'resources/views/livewire/website/article-community.blade.php',
        ];

        foreach ($paths as $path) {
            $markup = $this->readProjectFile($path);

            $this->assertStringNotContainsString('teal-', $markup, $path);
            $this->assertStringNotContainsString('slate-', $markup, $path);
            $this->assertStringContainsString('violet-', $markup, $path);
            $this->assertStringContainsString('text-ink', $markup, $path);
        }

        foreach (array_slice($paths, 0, 6) as $path) {
            $this->assertStringNotContainsString('<main', $this->readProjectFile($path), $path);
        }

        $community = $this->readProjectFile('resources/views/livewire/website/article-community.blade.php');

        $this->assertStringContainsString('min-h-11', $community);
        $this->assertStringContainsString('aria-describedby="report-comment-description"', $community);
        $this->assertStringContainsString('wire:key="comment-', $community);
        $this->assertStringContainsString('wire:key="reply-', $community);
    }

    private function readProjectFile(string $path): string
    {
        $contents = file_get_contents(base_path($path));

        $this->assertNotFalse($contents, "Unable to read {$path}.");

        return $contents;
    }
}
