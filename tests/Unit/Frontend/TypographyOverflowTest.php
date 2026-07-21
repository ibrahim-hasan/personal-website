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

    public function test_admin_wordmark_viewbox_contains_full_thmanyah_descenders(): void
    {
        $wordmark = file_get_contents(dirname(__DIR__, 3).'/public/images/brand/ibrahim-admin-wordmark.svg');

        $this->assertNotFalse($wordmark);
        $this->assertStringContainsString('viewBox="0 0 5433 1380"', $wordmark);
        $this->assertStringContainsString('preserveAspectRatio="xMidYMid meet"', $wordmark);
    }

    public function test_brand_pattern_preserves_the_source_vector_geometry(): void
    {
        $pattern = file_get_contents(dirname(__DIR__, 3).'/public/images/brand/ibrahim-geometric-pattern.svg');

        $this->assertNotFalse($pattern);
        $this->assertStringContainsString('viewBox="0 0 9228.16 4323.66"', $pattern);
        $this->assertStringContainsString('preserveAspectRatio="xMidYMid slice"', $pattern);
        $this->assertSame(768, substr_count($pattern, '<path '));
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
