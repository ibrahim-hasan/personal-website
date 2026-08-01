<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReaderStylesheetRegressionTest extends TestCase
{
    public function test_reader_authentication_markup_has_its_required_component_styles(): void
    {
        $this->get('/reader/login')
            ->assertOk()
            ->assertSee('reader-auth-card', false)
            ->assertSee('reader-form-control', false)
            ->assertSee('reader-checkbox', false);

        $css = file_get_contents(base_path('resources/css/app.css'));

        $this->assertIsString($css);
        $this->assertMatchesRegularExpression(
            '/\.reader-auth-card\s*\{[^}]*box-shadow:/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.reader-form-control\s*\{[^}]*border:\s*1px solid[^}]*background:\s*var\(--color-canvas\);/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.reader-form-control:focus-visible\s*\{[^}]*border-color:\s*var\(--color-violet-700\);[^}]*box-shadow:/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.reader-checkbox:focus-visible\s*\{[^}]*outline:\s*2px solid var\(--color-violet-700\);/s',
            $css,
        );
    }
}
