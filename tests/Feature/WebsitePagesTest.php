<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebsitePagesTest extends TestCase
{
    public function test_public_pages_render_the_ibrahim_site(): void
    {
        $pages = [
            '/' => ['إبراهيم حسن', 'منصات Laravel وDjango'],
            '/services' => ['مساعدة مركزة', 'هندسة منتجات الذكاء الاصطناعي'],
            '/work' => ['أعمال مختارة', 'مساعد دعم عربي'],
            '/writing' => ['ملاحظات من عمل منتج حقيقي', 'لوحات الإدارة يجب أن تشبه طاولة العمل'],
            '/about' => ['أعمل حيث تلتقي طموحات المنتج', 'الأدوات'],
            '/contact' => ['أخبرني ما الذي يجب أن يعمل', 'أسرع سياق مفيد'],
        ];

        foreach ($pages as $path => $expectations) {
            $response = $this->get($path);

            $response->assertOk();

            foreach ($expectations as $text) {
                $response->assertSee($text, false);
            }
        }
    }

    public function test_public_pages_use_the_ibrahim_portrait_asset(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('images/ibrahim/ibrahim-hasan-portrait.png', false)
            ->assertSee('صورة شخصية لإبراهيم حسن', false);

        $this->get('/en/about')
            ->assertOk()
            ->assertSee('images/ibrahim/ibrahim-hasan-portrait.png', false)
            ->assertSee('Ibrahim Hasan portrait', false);
    }

    public function test_english_pages_render_under_english_locale_prefix(): void
    {
        $pages = [
            '/en' => ['Ibrahim Hasan', 'AI-enabled platforms'],
            '/en/services' => ['Focused help', 'AI Product Engineering'],
            '/en/work' => ['Selected work', 'Arabic Support Assistant'],
            '/en/writing' => ['Notes from real product work', 'Admin Panels Should Feel Like Workbenches'],
            '/en/about' => ['I work where product ambition', 'Toolchain'],
            '/en/contact' => ['Tell me what needs to work', 'Fastest useful context'],
        ];

        foreach ($pages as $path => $expectations) {
            $response = $this->get($path);

            $response->assertOk();
            $response->assertSee('lang="en"', false);
            $response->assertSee('dir="ltr"', false);

            foreach ($expectations as $text) {
                $response->assertSee($text, false);
            }
        }
    }

    public function test_legacy_public_urls_redirect_to_new_sections(): void
    {
        $this->get('/intellectual-biography')->assertRedirect('/about');
        $this->get('/knowledge-library')->assertRedirect('/writing');
        $this->get('/contact-us')->assertRedirect('/contact');

        $this->get('/en/intellectual-biography')->assertRedirect('/en/about');
        $this->get('/en/knowledge-library')->assertRedirect('/en/writing');
        $this->get('/en/contact-us')->assertRedirect('/en/contact');
    }
}
