<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebsitePagesTest extends TestCase
{
    public function test_public_pages_render_the_ibrahim_site(): void
    {
        $pages = [
            '/' => ['إبراهيم حسن', 'إلى أثرٍ يُقاس'],
            '/services' => ['مساعدة مركزة حيث يلتقي العمل بالتقنية', 'استراتيجية التحول الرقمي'],
            '/work' => ['أعمال مختارة', 'ديجي بيديا'],
            '/writing' => ['التقنية بلغة الأعمال', 'من تجربة الذكاء الاصطناعي إلى تحقيق القيمة'],
            '/about' => ['أعمل حيث يلتقي العمل بالتقنية', 'كود مومنتس'],
            '/contact' => ['أخبرني بالمشكلة التي تريد حلّها', 'أرسل طلب الاستشارة'],
        ];

        foreach ($pages as $path => $expectations) {
            $response = $this->get($path);

            $response->assertOk();

            foreach ($expectations as $text) {
                $response->assertSee($text, false);
            }
        }
    }

    public function test_homepage_uses_real_portraits_and_project_evidence_without_placeholders(): void
    {
        $this->assertFileExists(public_path('images/ibrahim/ibrahim-systems-portrait.webp'));
        $this->assertFileExists(public_path('images/ibrahim/ibrahim-systems-portrait-compact.webp'));

        $this->get('/')
            ->assertOk()
            ->assertSee('<picture class="precision-stage__slide"', false)
            ->assertSee('images/ibrahim/ibrahim-systems-portrait.webp', false)
            ->assertSee('images/ibrahim/ibrahim-systems-portrait-compact.webp', false)
            ->assertSee('إبراهيم حسن يراجع مخططاً لنظام رقمي', false)
            ->assertSee('أفهم النظام قبل أن أبني الأداة.', false)
            ->assertSee('من القرار إلى نظامٍ يعمل.', false)
            ->assertSee('images/ibrahim/ibrahim-speaking-hero.webp', false)
            ->assertSee('كود مومنتس', false)
            ->assertSee('فروم سكراتش', false)
            ->assertSee('images/ibrahim/ibrahim-speaking-hero.webp', false)
            ->assertSee('images/projects/atlas/digi-pedia-ai-learning.webp', false)
            ->assertSee('images/brands/projects/digi-pedia.webp', false)
            ->assertSee('images/brands/companies/code-moments-on-light.svg', false)
            ->assertSee('images/brands/companies/from-scratch-on-light.svg', false)
            ->assertSee('atlas-chapter__brand--code-moments', false)
            ->assertSee('طيفك', false)
            ->assertSee('ما الذي رسّخه هذا الفصل', false)
            ->assertDontSee('images/brands/companies/code-moments-on-dark.svg', false)
            ->assertSee('ديجي بيديا', false)
            ->assertDontSee('<h3>From Scratch</h3>', false)
            ->assertDontSee('From Scratch Solutions', false)
            ->assertDontSee('IBRAHIM HASAN / IN PRACTICE', false)
            ->assertDontSee('مساحة الصورة الشخصية — تُبنى لاحقاً', false)
            ->assertDontSee('مساحة المشهد — تُبنى لاحقاً', false)
            ->assertDontSee('Strategy / Systems / AI', false)
            ->assertDontSee('images/ibrahim/ibrahim-hasan-portrait.png', false);

        $this->get('/en/about')
            ->assertOk()
            ->assertSee('images/ibrahim/ibrahim-speaking-hero.webp', false)
            ->assertSee('images/brands/companies/code-moments-on-dark.svg', false)
            ->assertSee('images/brands/companies/from-scratch-on-dark.svg', false)
            ->assertDontSee('images/brands/companies/code-moments-on-light.svg', false)
            ->assertSee('Code Moments', false)
            ->assertDontSee('Toolkit', false)
            ->assertDontSee('Portrait space — to be art-directed later', false)
            ->assertDontSee('images/ibrahim/ibrahim-hasan-portrait.png', false);
    }

    public function test_writing_library_uses_precise_taxonomy_and_one_footer_cta(): void
    {
        $this->get('/writing')
            ->assertOk()
            ->assertSee('دليل تصميم سير العمل', false)
            ->assertDontSee('مقال تصميمي', false)
            ->assertDontSee('الكتابة الجيدة لا تعرض المعرفة فقط', false)
            ->assertSee('site-footer__cta', false);
    }

    public function test_homepage_includes_the_precision_motion_composition(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('precision-hero', false)
            ->assertSee('precision-stage', false)
            ->assertSee('manifesto-section', false)
            ->assertSee('atlas-constellation', false)
            ->assertSee('decision-room', false)
            ->assertDontSee('PRECISION / PRACTICE', false)
            ->assertSee('Ibrahim Hasan reviewing a digital system map', false)
            ->assertSee('Understand the system before building the tool.', false)
            ->assertSee('From decision to a system that works.', false)
            ->assertSee('Request a free consultation', false);
    }

    public function test_homepage_presents_a_consulting_partnership_without_employment_or_stack_copy(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('شراكة تبدأ من المشكلة وتنتهي بأثرٍ قابل للقياس', false)
            ->assertSee('hello@ibrahimhasan.net', false)
            ->assertSee('https://sa.linkedin.com/in/i-hasan', false)
            ->assertSee('ابدأ بالمشكلة أو العملية أو الهدف، لا بالأداة.', false)
            ->assertDontSee('في السعودية والخليج', false)
            ->assertDontSee('متاح للعمل', false)
            ->assertDontSee('الرياض / عن بُعد', false)
            ->assertDontSee('مبني على Laravel وLivewire وTailwind', false)
            ->assertDontSee('RTL ↔ LTR', false)
            ->assertDontSee('site-footer__signature', false);
    }

    public function test_footer_renders_each_configured_personal_social_profile(): void
    {
        config()->set('services.social.facebook', 'https://facebook.example/ibrahim');
        config()->set('services.social.twitter', 'https://x.example/ibrahim');
        config()->set('services.social.instagram', 'https://instagram.example/ibrahim');

        $this->get('/')
            ->assertOk()
            ->assertSee('LinkedIn', false)
            ->assertSee('Facebook', false)
            ->assertSee('>X<', false)
            ->assertSee('Instagram', false)
            ->assertSee('https://facebook.example/ibrahim', false)
            ->assertSee('https://x.example/ibrahim', false)
            ->assertSee('https://instagram.example/ibrahim', false);
    }

    public function test_english_pages_render_under_english_locale_prefix(): void
    {
        $pages = [
            '/en' => ['Ibrahim Hasan', 'to impact you can measure'],
            '/en/services' => ['Focused help where business', 'Digital Transformation Strategy'],
            '/en/work' => ['Selected work', 'Digi-Pedia'],
            '/en/writing' => ['Technology in the language of business', 'From AI Experiment to Business Value'],
            '/en/about' => ['I work where business meets technology', 'Code Moments'],
            '/en/contact' => ['Tell me the problem you want to solve', 'Send consultation request'],
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
}
