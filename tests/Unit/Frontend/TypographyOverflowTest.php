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

    public function test_company_logo_cards_share_one_visual_measure(): void
    {
        $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

        $this->assertNotFalse($css);
        $this->assertMatchesRegularExpression(
            '/\.atlas-chapter__brand\s*\{[^}]*width:\s*min\(100%, 14rem\);[^}]*height:\s*7rem;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.atlas-chapter__brand img\s*\{[^}]*width:\s*auto;[^}]*height:\s*4\.5rem;/s',
            $css,
        );
    }
}
