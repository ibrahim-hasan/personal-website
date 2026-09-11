<?php

namespace Tests\Unit\Frontend;

use PHPUnit\Framework\TestCase;

class TypographyOverflowTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const DISPLAY_SECTIONS = [
        '.precision-hero',
        '.manifesto-section',
        '.page-intro',
        '.atlas-section',
        '.experience-trajectory',
        '.site-footer',
        '.site-footer__cta',
    ];

    public function test_display_sections_allow_vertical_glyph_overflow(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression('/body\s*\{[^}]*overflow-x:\s*clip;/s', $css);

        foreach (self::DISPLAY_SECTIONS as $selector) {
            $pattern = '/'.preg_quote($selector, '/').'\s*\{[^}]*overflow:\s*visible;/s';

            $this->assertMatchesRegularExpression(
                $pattern,
                $css,
                "{$selector} must not clip Thmanyah display glyphs vertically.",
            );
        }
    }

    public function test_rtl_display_type_reserves_space_for_arabic_glyphs(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression(
            "/html\\[dir='rtl'\\] \\.display-hero,\\s*html\\[dir='rtl'\\] \\.display-page,\\s*html\\[dir='rtl'\\] \\.display-section\\s*\\{[^}]*padding-block:\\s*0\\.04em 0\\.18em;[^}]*overflow:\\s*visible;/s",
            $css,
            'RTL display headings must reserve vertical space for Arabic dots and descenders.',
        );
    }

    public function test_supplied_brand_assets_preserve_their_surface_specific_geometry(): void
    {
        $brandRoot = dirname(__DIR__, 3).'/public/images/brand/';

        foreach ([
            'ibrahim-wordmark-horizontal-on-dark.svg' => 'viewBox="0 0 474.44 151.68"',
            'ibrahim-wordmark-horizontal-on-light.svg' => 'viewBox="0 0 474.44 151.68"',
            'ibrahim-wordmark-stacked-on-dark.svg' => 'viewBox="0 0 222.9398 259.0214"',
            'ibrahim-wordmark-stacked-on-light.svg' => 'viewBox="0 0 222.75 258.61"',
            'ibrahim-pattern-ink-violet.svg' => 'viewBox="0 0 732.4688 780.8245"',
            'ibrahim-pattern-ink.svg' => 'viewBox="0 0 732.4688 780.8245"',
            'ibrahim-pattern-white.svg' => 'viewBox="0 0 732.4688 780.8245"',
        ] as $asset => $viewBox) {
            $contents = file_get_contents($brandRoot.$asset);

            $this->assertNotFalse($contents);
            $this->assertStringContainsString($viewBox, $contents);
        }

        $this->assertStringContainsString('#745aa5', (string) file_get_contents($brandRoot.'ibrahim-pattern-ink-violet.svg'));
        $this->assertStringContainsString('#14151d', (string) file_get_contents($brandRoot.'ibrahim-pattern-ink.svg'));
        $this->assertStringContainsString('#fff', (string) file_get_contents($brandRoot.'ibrahim-pattern-white.svg'));
    }

    public function test_navigation_wordmark_has_a_confident_responsive_scale(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertSame(1, preg_match(
            '/\.site-nav \.brand-mark__logo-image\s*\{[^}]*inline-size:\s*clamp\(([\d.]+)rem,\s*[\d.]+vw,\s*([\d.]+)rem\);[^}]*block-size:\s*auto;/s',
            $css,
            $wordmarkScale,
        ));
        $previousMaximumInlineSize = 3.1 * 474.44 / 151.68;

        $this->assertGreaterThan($previousMaximumInlineSize, (float) $wordmarkScale[1], 'The complete wordmark must be larger than its previous size.');
        $this->assertGreaterThanOrEqual((float) $wordmarkScale[1], (float) $wordmarkScale[2]);
    }

    public function test_brand_patterns_are_controlled_and_low_contrast(): void
    {
        $publicCss = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');
        $adminCss = file_get_contents(dirname(__DIR__, 3).'/resources/css/filament/admin/ibrahim.css');

        $this->assertNotFalse($publicCss);
        $this->assertNotFalse($adminCss);
        $this->assertStringContainsString("url('../../public/images/brand/ibrahim-pattern-ink-violet.svg')", $publicCss);
        $this->assertStringContainsString("url('../../public/images/brand/ibrahim-pattern-ink.svg')", $publicCss);
        $this->assertStringContainsString("url('../../public/images/brand/ibrahim-pattern-white.svg')", $publicCss);
        $this->assertStringContainsString("url('../../../../public/images/brand/ibrahim-pattern-white.svg')", $adminCss);
        $this->assertStringNotContainsString('ibrahim-geometric-pattern.svg', $publicCss);
        $this->assertStringNotContainsString('ibrahim-geometric-pattern.svg', $adminCss);
        $this->assertStringNotContainsString('ibrahim-mono-pattern.svg', $publicCss);
        $this->assertStringNotContainsString('ibrahim-mono-pattern.svg', $adminCss);
        $this->assertStringNotContainsString('background-repeat: repeat-y;', $publicCss);
        $this->assertStringNotContainsString('background-repeat: repeat-y;', $adminCss);

        preg_match_all('/--brand-pattern-opacity:\s*([\d.]+);/', $publicCss, $patternOpacities);
        $this->assertNotEmpty($patternOpacities[1]);

        foreach ($patternOpacities[1] as $opacity) {
            $this->assertGreaterThanOrEqual(0.05, (float) $opacity);
            $this->assertLessThanOrEqual(0.1, (float) $opacity);
        }

        foreach (['.precision-hero::before', '.decision-room__rail::before', '.athar-shell::after', '.about-teaser__portrait::before', '.about-journey::before', '.site-footer__cta::before', '.atlas-section::before'] as $selector) {
            $this->assertPatternOpacityInRange($selector, $publicCss);
        }

        $this->assertPatternOpacityInRange('.fi-auth-shell__context::before', $adminCss);
        $this->assertSame(1, preg_match(
            '/\.precision-hero::before,\s*[^{}]+\{([^}]+)\}/s',
            $publicCss,
            $sharedPattern,
        ));
        $this->assertMatchesRegularExpression('/background-image:\s*var\(--brand-pattern-images\);/', $sharedPattern[1]);
        $this->assertMatchesRegularExpression('/background-repeat:\s*no-repeat(?:\s*,\s*no-repeat)*;/', $sharedPattern[1]);
        $this->assertSame(1, preg_match(
            '/@media \(max-width:\s*[\d.]+rem\)\s*\{\s*\.precision-hero::before,\s*[^{}]+\{([^}]+)\}/s',
            $publicCss,
            $mobilePattern,
        ));
        $this->assertMatchesRegularExpression('/background-image:\s*var\(--brand-pattern-mobile-images,/', $mobilePattern[1]);
        $this->assertSame(1, preg_match(
            '/\.decision-room__rail::before\s*\{([^}]+)\}/s',
            $publicCss,
            $decisionPattern,
        ));
        $this->assertSparsePatternLayers($decisionPattern[1]);
        $this->assertMatchesRegularExpression('/background-repeat:\s*no-repeat(?:\s*,\s*no-repeat)*;/', $decisionPattern[1]);
        $this->assertMatchesRegularExpression(
            '/\.decision-room__rail\s*\{[^}]*min-width:\s*0;/s',
            $publicCss,
        );
        $this->assertStringNotContainsString('.experience-trajectory)::after', $publicCss);
        $this->assertStringNotContainsString('.experience-trajectory) > .site-container', $publicCss);
    }

    public function test_brand_pattern_compositions_are_surface_specific(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);

        $desktopCompositions = [];
        $mobileCompositions = [];

        foreach ([
            '.precision-hero::before',
            '.manifesto-section',
            '.page-intro--violet',
            '.atlas-section::before',
            '.about-journey',
            '.site-footer__cta',
        ] as $selector) {
            $this->assertSame(1, preg_match(
                '/'.preg_quote($selector, '/').'\s*\{([^}]+)\}/s',
                $css,
                $surfaceRule,
            ));
            $this->assertSame(1, preg_match('/--brand-pattern-images:\s*([^;]+);/s', $surfaceRule[1], $images));
            $this->assertSame(1, preg_match('/--brand-pattern-sizes:\s*([^;]+);/s', $surfaceRule[1], $sizes));
            $this->assertSame(1, preg_match('/--brand-pattern-positions:\s*([^;]+);/s', $surfaceRule[1], $positions));
            $this->assertSame(1, preg_match('/--brand-pattern-mobile-sizes:\s*([^;]+);/s', $surfaceRule[1], $mobileSizes));
            $this->assertSame(1, preg_match('/--brand-pattern-mobile-positions:\s*([^;]+);/s', $surfaceRule[1], $mobilePositions));

            $layerCount = substr_count($images[1], 'var(--brand-pattern-image,');

            $this->assertGreaterThanOrEqual(2, $layerCount, "{$selector} must keep the pattern sparse.");
            $this->assertLessThanOrEqual(4, $layerCount, "{$selector} must keep the pattern sparse.");

            if ($selector === '.manifesto-section') {
                $this->assertSame(4, $layerCount, 'The manifesto should use four visible, deliberate motifs on wider screens.');
                $this->assertSame(4, substr_count($positions[1], ',') + 1);
                $this->assertSame(1, preg_match('/--brand-pattern-mobile-images:\s*([^;]+);/s', $surfaceRule[1], $mobileImages));
                $this->assertSame(3, substr_count($mobileImages[1], 'var(--brand-pattern-image,'));
                $this->assertSame(3, $this->countTopLevelCssValues($mobileSizes[1]));
                $this->assertSame(3, $this->countTopLevelCssValues($mobilePositions[1]));
            }

            $desktopCompositions[] = trim($positions[1]).'|'.trim($sizes[1]);
            $mobileCompositions[] = trim($mobilePositions[1]).'|'.trim($mobileSizes[1]);
        }

        $this->assertSame(count($desktopCompositions), count(array_unique($desktopCompositions)));
        $this->assertSame(count($mobileCompositions), count(array_unique($mobileCompositions)));
    }

    private function assertPatternOpacityInRange(string $selector, string $css): void
    {
        $this->assertSame(1, preg_match(
            '/'.preg_quote($selector, '/').'\s*\{[^}]*opacity:\s*([\d.]+);/s',
            $css,
            $patternOpacity,
        ), "{$selector} must have a visible, low-contrast pattern.");
        $this->assertGreaterThanOrEqual(0.05, (float) $patternOpacity[1]);
        $this->assertLessThanOrEqual(0.1, (float) $patternOpacity[1]);
    }

    private function assertSparsePatternLayers(string $rule): void
    {
        $this->assertSame(1, preg_match('/background-image:\s*([^;]+);/s', $rule, $backgroundImages));
        $layerCount = preg_match_all('/var\(--brand-pattern-[\w-]+(?:,\s*var\(--brand-pattern-[\w-]+\))?\)/', $backgroundImages[1]);

        $this->assertGreaterThanOrEqual(2, $layerCount, 'A pattern composition must contain multiple deliberate motifs.');
        $this->assertLessThanOrEqual(5, $layerCount, 'A pattern composition must retain generous space between motifs.');
        $this->assertDoesNotMatchRegularExpression('/background-repeat:\s*repeat(?:-x|-y)?\b/', $rule);
        $this->assertDoesNotMatchRegularExpression('/(?:display|content|background-image):\s*none;/', $rule);
    }

    private function countTopLevelCssValues(string $value): int
    {
        $parenthesisDepth = 0;
        $valueCount = 1;

        foreach (str_split($value) as $character) {
            if ($character === '(') {
                $parenthesisDepth++;
            }

            if ($character === ')') {
                $parenthesisDepth--;
            }

            if ($character === ',' && $parenthesisDepth === 0) {
                $valueCount++;
            }
        }

        return $valueCount;
    }

    public function test_writing_sidebar_scales_display_type_to_its_column(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression(
            '/\.writing-section \.editorial-sidebar__intro \.display-section\s*\{[^}]*max-width:\s*100%;[^}]*font-size:\s*clamp\(2\.35rem, 3\.55vw, 3\.9rem\);/s',
            $css,
        );
    }

    public function test_revealed_headlines_do_not_clip_display_glyphs(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression(
            '/\.motion-capable \[data-reveal=\'headline\'\],\s*\.motion-capable \[data-reveal=\'headline\'\]\.is-revealed\s*\{[^}]*clip-path:\s*none;/s',
            $css,
        );
    }

    public function test_writing_rows_reserve_logical_end_space_for_the_arrow(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression(
            '/\.writing-row--link\s*\{[^}]*padding-inline:\s*0\.75rem;[^}]*padding-inline-end:\s*3\.25rem;/s',
            $css,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/html\[dir=\'rtl\'\] \.writing-row--link\s*\{[^}]*padding-inline:\s*3\.25rem 0\.75rem;/s',
            $css,
        );
    }

    public function test_experience_heading_reserves_space_for_arabic_descenders(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression(
            '/\.experience-trajectory__title\s*\{[^}]*padding-block-end:\s*0\.18em;[^}]*overflow:\s*visible;/s',
            $css,
        );
    }

    public function test_full_desktop_navigation_waits_for_a_safe_bilingual_width(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertStringContainsString('@media (min-width: 90rem)', $css);
        $this->assertMatchesRegularExpression(
            '/\.site-nav > \.site-container\s*\{[^}]*display:\s*grid;[^}]*grid-template-columns:\s*minmax\(14rem, 1fr\) auto minmax\(18rem, 1fr\);/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.site-nav__desktop-links\s*\{[^}]*min-width:\s*max-content;[^}]*justify-self:\s*center;[^}]*white-space:\s*nowrap;/s',
            $css,
        );
    }

    public function test_english_type_uses_explicit_ui_body_and_display_font_stacks(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression(
            "/html\\[lang='en'\\]\\s*\\{[^}]*--family-ui:\\s*'Noto Sans',[^}]*--family-body:\\s*'Noto Sans',[^}]*--family-display:\\s*'Thmanyah Display',/s",
            $css,
        );
    }

    public function test_company_logo_cards_share_one_visual_measure(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression(
            '/\.atlas-chapter__brand\s*\{[^}]*height:\s*7rem;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.atlas-chapter__brand img\s*\{[^}]*height:\s*6rem;/s',
            $css,
        );
    }

    public function test_practice_atlas_progresses_from_one_to_two_to_three_peer_cards(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 48rem\)\s*\{.*?\.atlas-constellation\s*\{[^}]*grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);[^}]*align-items:\s*stretch;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.atlas-chapter--featured\s*\{[^}]*display:\s*grid;[^}]*grid-column:\s*1 \/ -1;[^}]*grid-template-columns:\s*minmax\(0, 1\.1fr\) minmax\(14rem, 0\.9fr\);[^}]*align-items:\s*center;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 80rem\)\s*\{.*?\.atlas-constellation\s*\{[^}]*grid-template-columns:\s*repeat\(3, minmax\(0, 1fr\)\);/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 80rem\)\s*\{.*?\.atlas-chapter--featured\s*\{[^}]*display:\s*flex;[^}]*grid-column:\s*auto;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 80rem\)\s*\{.*?\.atlas-chapter--featured \.atlas-chapter__content\s*\{[^}]*display:\s*flex;[^}]*gap:\s*0;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/@media \(min-width: 80rem\)\s*\{.*?\.atlas-chapter--featured \.atlas-chapter__focus\s*\{[^}]*margin-top:\s*1\.5rem;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.atlas-chapter:not\(\.atlas-chapter--featured\)\s*\{[^}]*min-height:\s*clamp\(31rem, 42vw, 36rem\);/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.atlas-chapter:not\(\.atlas-chapter--featured\) \.atlas-chapter__action\s*\{[^}]*margin-top:\s*auto;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.atlas-chapter:not\(\.atlas-chapter--featured\) \.atlas-chapter__copy\s*\{[^}]*margin-block-end:\s*clamp\(2\.25rem, 3\.6vw, 3\.5rem\);/s',
            $css,
        );
    }
}
