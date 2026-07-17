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
            ->assertSee('data-hero-video-finale', false)
            ->assertSee('data-hero-video-replay', false)
            ->assertSee('aria-hidden="true" inert', false)
            ->assertSee('data-back-to-top', false)
            ->assertSee('data-back-to-top-safe-zone', false)
            ->assertSee('aria-hidden="true" tabindex="-1"', false)
            ->assertSee('Back to top', false)
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
            ->assertSee('class="filter-bar-shell"', false)
            ->assertSee('class="filter-bar" role="toolbar"', false)
            ->assertSee('class="filter-bar__arrow"', false)
            ->assertSee('@click="scrollLenses(1)"', false)
            ->assertSee('Show more project lenses', false)
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
            ->assertSee('class="publication-row__meta"', false)
            ->assertSee('class="publication-row__kind"', false)
            ->assertSee('decoding="async"', false);
    }

    public function test_motion_preserves_content_and_responsive_controls_remain_contained(): void
    {
        $css = $this->readProjectFile('resources/css/app.css');
        $javascript = $this->readProjectFile('resources/js/app.js');

        $this->assertMatchesRegularExpression('/\.writing-section\s*\{[^}]*background:\s*#ded3ea;/s', $css);

        $this->assertMatchesRegularExpression(
            '/\.motion-capable \[data-reveal\]\s*\{[^}]*opacity:\s*1;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 63\.999rem\)\s*\{.*?\.precision-stage\s*\{[^}]*width:\s*min\(100%, 22\.5rem\);[^}]*overflow:\s*clip;/s',
            $css,
        );
        $this->assertMatchesRegularExpression('/\.filter-bar\s*\{[^}]*overflow-x:\s*auto;/s', $css);
        $this->assertStringContainsString('window.scrollTo({', $javascript);
        $this->assertStringContainsString("control.classList.toggle('is-visible', shouldShow)", $javascript);
        $this->assertMatchesRegularExpression('/\.filter-bar-shell\s*\{[^}]*grid-template-columns:\s*2\.75rem minmax\(0, 1fr\) 2\.75rem;/s', $css);
        $this->assertMatchesRegularExpression('/\.filter-bar__arrow\s*\{[^}]*width:\s*2\.75rem;[^}]*height:\s*2\.75rem;/s', $css);
        $this->assertMatchesRegularExpression('/\.publication-topics\s*\{[^}]*overflow-x:\s*auto;/s', $css);
        $this->assertMatchesRegularExpression(
            '/\.publication-topics\s*\{[^}]*align-items:\s*center;[^}]*padding-block:\s*0\.525rem;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.publication-row__meta\s*\{[^}]*align-items:\s*baseline;[^}]*gap:\s*0\.45rem 0\.7rem;/s',
            $css,
        );
        $this->assertStringContainsString('class="publication-row__read-time"', $this->readProjectFile('resources/views/website/writing.blade.php'));
        $this->assertMatchesRegularExpression('/\.publication-row\s*\{[^}]*padding-inline:\s*var\(--publication-row-inline\);/s', $css);
        $this->assertMatchesRegularExpression('/\.publication-row::before,\s*\.publication-row:last-child::after\s*\{[^}]*inset-inline:\s*var\(--publication-row-inline\);/s', $css);
        $this->assertDoesNotMatchRegularExpression('/\.publication-library__toolbar\s*\{[^}]*border-bottom:/s', $css);
        $this->assertStringContainsString(
            'data-reveal="editorial-row"',
            $this->readProjectFile('resources/views/website/writing.blade.php'),
        );
        $this->assertStringContainsString(
            'class="writing-row__content"',
            $this->readProjectFile('resources/views/website/home.blade.php'),
        );
        $this->assertMatchesRegularExpression(
            "/\[data-reveal='editorial-row'\]\s*\{[^}]*translate:\s*0;[^}]*transition:\s*none;/s",
            $css,
        );
        $this->assertMatchesRegularExpression(
            "/\[data-reveal='editorial-row'\] > \.publication-row__copy[^}]*\{[^}]*translate:\s*0 1\.5rem;/s",
            $css,
        );
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
            '/\.about-teaser__portrait\s*\{[^}]*padding-inline-end:\s*var\(--portrait-rail\);[^}]*ibrahim-geometric-pattern\.svg[^}]*background-repeat:\s*no-repeat;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.about-teaser__portrait > img\s*\{[^}]*translate:\s*none;[^}]*filter:\s*none;/s',
            $css,
        );
        $this->assertStringContainsString(
            'class="about-teaser__portrait" data-reveal="media"',
            $this->readProjectFile('resources/views/website/home.blade.php'),
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
        $this->assertMatchesRegularExpression(
            '/\.writing-row__arrow\s*\{[^}]*transition:\s*translate 260ms var\(--ease-out-quint\);/s',
            $css,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/writing-row--link:hover \.writing-row__arrow\s*\{[^}]*rotate:/s',
            $css,
        );
        $this->assertStringContainsString('scrollTopics(direction)', $javascript);
        $this->assertStringContainsString('scrollLenses(direction)', $javascript);
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 64rem\)\s*\{\s*\.case-study\s*\{[^}]*grid-template-columns:/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.case-study__story\s*\{[^}]*grid-column:\s*1 \/ -1;[^}]*grid-template-columns:\s*repeat\(3, minmax\(0, 1fr\)\);/s',
            $css,
        );
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
        $this->assertStringContainsString("window.sessionStorage.setItem(guestSeenKey, 'true')", $javascript);
        $this->assertStringContainsString('video.dataset.viewedUrl', $javascript);
        $this->assertStringContainsString("method: 'POST'", $javascript);
        $this->assertStringContainsString('video.play().catch(() => {})', $javascript);
        $this->assertStringContainsString("video.addEventListener('ended', () =>", $javascript);
        $this->assertStringContainsString("stage?.classList.add('is-complete')", $javascript);
        $this->assertStringContainsString("finale?.removeAttribute('inert')", $javascript);
        $this->assertStringContainsString('video.loop = false', $javascript);
        $this->assertStringContainsString('visibilityObserver.disconnect()', $javascript);
        $this->assertStringContainsString("document.querySelector('[data-site-audio-player]')", $javascript);
        $this->assertStringContainsString("document.querySelector('[data-back-to-top-safe-zone]')", $javascript);
        $this->assertStringContainsString('Math.max(audioOffset, footerOffset)', $javascript);
        $this->assertStringContainsString("control.style.setProperty('--floating-footer-offset'", $javascript);
        $this->assertStringContainsString('new ResizeObserver(updateFloatingOffset)', $javascript);
        $this->assertStringContainsString('new MutationObserver(updateFloatingOffset)', $javascript);
        $this->assertStringContainsString('@continue(current_locale() === $locale)', $this->readProjectFile('resources/views/components/partials/navbar.blade.php'));
        $this->assertStringContainsString('class="language-switch__action"', $this->readProjectFile('resources/views/components/partials/navbar.blade.php'));
        $this->assertMatchesRegularExpression('/\.button-primary,[^{]+\{[^}]*border-radius:\s*var\(--control-radius\);/s', $css);
        $this->assertStringNotContainsString('heroStage', $javascript);
        $this->assertStringNotContainsString('precision-stage__control', $css);
        $this->assertStringContainsString('clip-path: inset(-0.3em -0.08em -0.4em -0.08em)', $css);
        $this->assertMatchesRegularExpression(
            "/html\\[lang='en'\\] \\.decision-room__title\\s*\\{[^}]*max-width:\\s*100%;[^}]*font-size:\\s*clamp\\(2rem, 3vw, 3rem\\);/s",
            $css,
        );
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
            'resources/views/website/reader-account.blade.php',
            'resources/views/livewire/website/article-community.blade.php',
        ];

        foreach ($paths as $path) {
            $markup = $this->readProjectFile($path);

            $this->assertStringNotContainsString('teal-', $markup, $path);
            $this->assertStringNotContainsString('slate-', $markup, $path);
            $this->assertStringContainsString('violet-', $markup, $path);
            $this->assertStringContainsString('text-ink', $markup, $path);
        }

        foreach (array_slice($paths, 0, 7) as $path) {
            $this->assertStringNotContainsString('<main', $this->readProjectFile($path), $path);
        }

        $navbar = $this->readProjectFile('resources/views/components/partials/navbar.blade.php');
        $account = $this->readProjectFile('resources/views/website/reader-account.blade.php');

        $this->assertStringContainsString("localized_route('reader.login')", $navbar);
        $this->assertStringContainsString("localized_route('reader.library')", $navbar);
        $this->assertStringContainsString("localized_route('reader.account')", $navbar);
        $this->assertStringContainsString('aria-controls="reader-account-menu"', $navbar);
        $this->assertStringContainsString('class="site-nav__desktop-links"', $navbar);
        $this->assertStringContainsString('class="site-nav__utility-rail"', $navbar);
        $this->assertStringContainsString('class="site-nav__account-trigger site-nav__account-trigger--authenticated"', $navbar);
        $this->assertMatchesRegularExpression(
            '/\.site-nav__utility-rail\s*\{[^}]*height:\s*3rem;[^}]*border:\s*1px solid/s',
            $this->readProjectFile('resources/css/app.css'),
        );
        $this->assertMatchesRegularExpression(
            "/\.site-nav__account-trigger\[aria-expanded='true'\]\s*\{[^}]*background:\s*var\(--color-violet-100\);/s",
            $this->readProjectFile('resources/css/app.css'),
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 72rem\).*?\.site-nav__desktop-links,\s*\.site-nav__utility-rail\s*\{[^}]*display:\s*flex;/s',
            $this->readProjectFile('resources/css/app.css'),
        );
        $this->assertStringContainsString("localized_route('reader.account.destroy')", $account);
        $this->assertStringContainsString('autocomplete="current-password"', $account);
        $this->assertStringContainsString("@method('DELETE')", $account);
        $this->assertStringContainsString('robots="noindex, nofollow, noarchive, noimageindex"', $account);

        $community = $this->readProjectFile('resources/views/livewire/website/article-community.blade.php');

        $this->assertStringContainsString('min-h-11', $community);
        $this->assertStringContainsString('aria-describedby="report-comment-description"', $community);
        $this->assertStringContainsString('wire:key="comment-', $community);
        $this->assertStringContainsString('wire:key="reply-', $community);
    }

    public function test_article_sharing_is_progressively_enhanced_and_accessible(): void
    {
        $component = $this->readProjectFile('resources/views/components/article-share.blade.php');
        $application = $this->readProjectFile('resources/js/app.js');
        $sharing = $this->readProjectFile('resources/js/article-share.js');
        $css = $this->readProjectFile('resources/css/app.css');

        $this->assertStringContainsString('data-article-native-share', $component);
        $this->assertStringContainsString('data-article-copy-link', $component);
        $this->assertStringContainsString('aria-live="polite"', $component);
        $this->assertSame(3, substr_count($component, 'rel="noopener noreferrer"'));
        $this->assertStringContainsString("await import('./article-share')", $application);
        $this->assertStringContainsString("typeof navigator.share === 'function'", $sharing);
        $this->assertStringContainsString('navigator.clipboard?.writeText', $sharing);
        $this->assertStringContainsString("document.execCommand('copy')", $sharing);
        $this->assertStringContainsString('}, 2000);', $sharing);
        $this->assertStringContainsString('data-article-copy-success', $component);
        $this->assertStringContainsString('data-default-label=', $component);
        $this->assertStringNotContainsString('window.location.href', $sharing);
        $this->assertMatchesRegularExpression(
            '/\.article-share__action\s*\{[^}]*min-height:\s*2\.75rem;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.article-share\s*\{[^}]*grid-template-columns:\s*auto minmax\(0, 1fr\);/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.article-share-rail\s*\{[^}]*border-block:\s*1px solid/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.reader-panel__primary\s*\{[^}]*padding-block:\s*0;[^}]*\}/s',
            $css,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.reader-panel__primary\s*\{[^}]*border-block:/s',
            $css,
        );
    }

    public function test_scroll_work_is_frame_throttled_and_reveals_use_the_observer_after_the_initial_pass(): void
    {
        $javascript = $this->readProjectFile('resources/js/app.js');

        $this->assertStringContainsString('const initializeScrollProgress = (signal)', $javascript);
        $this->assertStringContainsString('scrollProgressFrame = window.requestAnimationFrame', $javascript);
        $this->assertStringContainsString("window.addEventListener('scroll', queueScrollProgress", $javascript);
        $this->assertStringContainsString("window.addEventListener('resize', queueScrollProgress", $javascript);
        $this->assertStringContainsString('window.cancelAnimationFrame(scrollProgressFrame)', $javascript);
        $this->assertStringNotContainsString("window.addEventListener('scroll', updateScrollProgress", $javascript);
        $this->assertStringContainsString('revealObserver.observe(element)', $javascript);
        $this->assertStringContainsString(
            'initialRevealFrame = window.requestAnimationFrame(revealVisibleElements)',
            $javascript,
        );
        $this->assertStringNotContainsString("window.addEventListener('scroll', queueVisibleReveal", $javascript);
        $this->assertStringNotContainsString("window.addEventListener('resize', queueVisibleReveal", $javascript);
    }

    private function readProjectFile(string $path): string
    {
        $contents = file_get_contents(base_path($path));

        $this->assertNotFalse($contents, "Unable to read {$path}.");

        return $contents;
    }
}
