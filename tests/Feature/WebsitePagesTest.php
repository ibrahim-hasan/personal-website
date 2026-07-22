<?php

namespace Tests\Feature;

use Tests\TestCase;

class WebsitePagesTest extends TestCase
{
    public function test_public_layout_uses_the_violet_diamond_favicon(): void
    {
        $this->assertFileExists(public_path('favicon.svg'));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $this->assertStringContainsString('#7d53cd', file_get_contents(public_path('favicon.svg')));
        $this->assertStringContainsString("->favicon(asset('favicon.svg'))", file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php')));

        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.asset('favicon.svg').'" type="image/svg+xml"', false)
            ->assertSee('href="'.asset('apple-touch-icon.png').'"', false)
            ->assertDontSee('rel="icon" href="'.asset('images/ibrahim/ibrahim-systems-portrait-compact.webp').'"', false);
    }

    public function test_public_pages_render_the_ibrahim_site(): void
    {
        $pages = [
            '/' => ['إبراهيم حسن', 'إلى أثرٍ يُقاس', 'الخدمات', 'أربعة مجالات للعمل. منهج واحد لا يفصل العمل عن التقنية.'],
            '/services' => ['الخدمات', 'مساعدة مركزة حيث يلتقي العمل بالتقنية', 'استراتيجية التحول الرقمي'],
            '/work' => ['أعمال مختارة', 'ما الذي تغيّر، ولماذا؟', 'حالات مختارة عبر قطاعات مختلفة. في كل حالة: السياق التشغيلي، والتحدي، وما تغيّر، والأثر العملي.', 'الموسوعة الرقمية'],
            '/writing' => ['التقنية بلغة الأعمال', 'من تجربة الذكاء الاصطناعي إلى تحقيق القيمة'],
            '/about' => ['أبني أنظمة رقمية يُعتمد عليها.', 'كيف بدأ المسار.', 'بدأتُ في هندسة الميكاترونيكس', 'مشروع تخرجي طائرة رباعية من دون طيار', 'ما أعمل عليه اليوم.', 'كود مومنتس'],
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

    public function test_service_information_architecture_uses_clear_localized_labels(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('Services', false)
            ->assertSee('Four areas of work. One mindset that never separates business from technology.', false);

        $this->get('/en/services')
            ->assertOk()
            ->assertSee('Services', false)
            ->assertSee('Service areas', false);
    }

    public function test_services_method_uses_distinct_geometric_step_icons(): void
    {
        $this->get('/services')
            ->assertOk()
            ->assertSee('method-band__icon--focus', false)
            ->assertSee('method-band__icon--map', false)
            ->assertSee('method-band__icon--prioritize', false)
            ->assertSee('method-band__icon--measure', false)
            ->assertSee('aria-hidden="true"', false);
    }

    public function test_athar_proof_cards_keep_their_own_language_direction(): void
    {
        $arabicPage = view('components.athar.proof', [
            'cards' => [['text' => 'An English endorsement.', 'context' => '', 'name' => '', 'locale' => 'en']],
        ])->render();
        $englishPage = view('components.athar.proof', [
            'cards' => [['text' => 'شهادة عربية.', 'context' => '', 'name' => '', 'locale' => 'ar']],
        ])->render();

        $this->assertStringContainsString('dir="ltr"', $arabicPage);
        $this->assertStringContainsString('lang="en"', $arabicPage);
        $this->assertStringContainsString('dir="rtl"', $englishPage);
        $this->assertStringContainsString('lang="ar"', $englishPage);
    }

    public function test_homepage_uses_optimized_hero_video_and_project_evidence_without_placeholders(): void
    {
        $this->assertFileExists(public_path('videos/hero/ibrahim-hero.mp4'));
        $this->assertFileExists(public_path('videos/hero/ibrahim-hero.webm'));
        $this->assertFileExists(public_path('videos/hero/ibrahim-hero-689778bf.mp4'));
        $this->assertFileExists(public_path('videos/hero/ibrahim-hero-0ab509e4.webm'));
        $this->assertFileExists(public_path('images/ibrahim/ibrahim-speaking-editorial.webp'));
        $this->assertFileExists(public_path('images/ibrahim/ibrahim-formal-portrait.webp'));
        $this->assertFileExists(public_path('images/ibrahim/ibrahim-conference-event.webp'));
        $this->assertFileExists(public_path('images/ibrahim/ibrahim-candid-session.webp'));

        $this->get('/')
            ->assertOk()
            ->assertSee('data-hero-video', false)
            ->assertSee('rel="preload"', false)
            ->assertSee('as="image"', false)
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('data-webm-src-high', false)
            ->assertSee('data-mp4-src-high', false)
            ->assertSee('data-webm-src-compact', false)
            ->assertSee('data-mp4-src-compact', false)
            ->assertSee('videos/hero/ibrahim-hero.webm', false)
            ->assertSee('videos/hero/ibrahim-hero.mp4', false)
            ->assertSee('videos/hero/ibrahim-hero-0ab509e4.webm', false)
            ->assertSee('videos/hero/ibrahim-hero-689778bf.mp4', false)
            ->assertSee('images/ibrahim/ibrahim-speaking-editorial.webp', false)
            ->assertSee('width="720"', false)
            ->assertSee('height="1280"', false)
            ->assertSee('muted', false)
            ->assertDontSee(' loop', false)
            ->assertSee('playsinline', false)
            ->assertSee('preload="metadata"', false)
            ->assertSee('data-hero-video-finale', false)
            ->assertSee('data-hero-video-replay', false)
            ->assertSee('ابدأ من المشكلة', false)
            ->assertDontSee('precision-stage__note', false)
            ->assertDontSee('heroStage(', false)
            ->assertDontSee('precision-stage__control', false)
            ->assertSee('كود مومنتس', false)
            ->assertSee('فروم سكراتش', false)
            ->assertSee('images/ibrahim/ibrahim-speaking-editorial.webp', false)
            ->assertSee('images/projects/atlas/digi-pedia-ai-learning.webp', false)
            ->assertSee('images/brands/projects/digi-pedia.webp', false)
            ->assertSee('images/brands/companies/code-moments-on-light.svg', false)
            ->assertSee('images/brands/companies/from-scratch-on-light.svg', false)
            ->assertSee('atlas-chapter__brand--code-moments', false)
            ->assertSee('طيفك', false)
            ->assertSee('أدواري اليوم', false)
            ->assertSee('تأسيس', false)
            ->assertSee('قيادة', false)
            ->assertSee('استراتيجية', false)
            ->assertSee('ما أعمل عليه مع العملاء', false)
            ->assertDontSee('ما أتحمل مسؤوليته', false)
            ->assertSee('تعرّف على فروم سكراتش', false)
            ->assertSee('تعرّف على كود مومنتس', false)
            ->assertSee('اطلب استشارة مجانية', false)
            ->assertSee('https://fromscratch-solutions.com', false)
            ->assertSee('https://codemoments.com', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertDontSee('خبرات انتقلت إلى الممارسة الحالية', false)
            ->assertDontSee('data-now', false)
            ->assertDontSee('images/brands/companies/code-moments-on-dark.svg', false)
            ->assertSee('الموسوعة الرقمية', false)
            ->assertDontSee('<h3>From Scratch</h3>', false)
            ->assertDontSee('From Scratch Solutions', false)
            ->assertDontSee('IBRAHIM HASAN / IN PRACTICE', false)
            ->assertDontSee('مساحة الصورة الشخصية — تُبنى لاحقاً', false)
            ->assertDontSee('مساحة المشهد — تُبنى لاحقاً', false)
            ->assertDontSee('Strategy / Systems / AI', false)
            ->assertDontSee('images/ibrahim/ibrahim-hasan-portrait.png', false);

        $this->get('/en/about')
            ->assertOk()
            ->assertSee('images/ibrahim/ibrahim-formal-portrait.webp', false)
            ->assertSee('Professional portrait of Ibrahim Hasan', false)
            ->assertSee('Building dependable digital systems.', false)
            ->assertSee('Where the path began.', false)
            ->assertSee('I began in mechatronics engineering', false)
            ->assertSee('My graduation project was a quadcopter', false)
            ->assertSee('Where I work today.', false)
            ->assertSee('Code Moments', false)
            ->assertSee('From Scratch', false)
            ->assertSee('Independent strategic practice', false)
            ->assertDontSee('The method shows up in the real work.', false)
            ->assertDontSee('Four lenses for one integrated decision.', false)
            ->assertDontSee('How I prefer to handle serious work.', false)
            ->assertDontSee('images/ibrahim/ibrahim-conference-event.webp', false)
            ->assertDontSee('images/ibrahim/ibrahim-candid-session.webp', false)
            ->assertDontSee('Portrait space — to be art-directed later', false)
            ->assertDontSee('IBRAHIM HASAN / STRATEGY IN PRACTICE', false)
            ->assertDontSee('images/ibrahim/ibrahim-hasan-portrait.png', false);

        $this->get('/about')
            ->assertOk()
            ->assertSee('صورة مهنية لإبراهيم حسن', false)
            ->assertSee('أبني أنظمة رقمية يُعتمد عليها.', false)
            ->assertSee('كيف بدأ المسار.', false)
            ->assertSee('ما أعمل عليه اليوم.', false)
            ->assertSee('فروم سكراتش', false)
            ->assertSee('كود مومنتس', false)
            ->assertSee('عمل استشاري مستقل', false)
            ->assertDontSee('المنهج يظهر في أرض الواقع.', false)
            ->assertDontSee('أربع عدسات لقرار واحد متكامل.', false)
            ->assertDontSee('كيف أفضل التعامل مع العمل الجاد.', false)
            ->assertDontSee('images/ibrahim/ibrahim-conference-event.webp', false)
            ->assertDontSee('images/ibrahim/ibrahim-candid-session.webp', false);
    }

    public function test_writing_library_uses_precise_taxonomy_and_one_footer_cta(): void
    {
        $this->get('/writing')
            ->assertOk()
            ->assertSee('دليل تصميم سير العمل', false)
            ->assertDontSee('مقال تصميمي', false)
            ->assertDontSee('الكتابة الجيدة لا تعرض المعرفة فقط', false)
            ->assertSee('site-footer__cta', false)
            ->assertSee('site-footer__cta-layout', false)
            ->assertSee('site-footer__cta-action', false);
    }

    public function test_homepage_includes_the_precision_motion_composition(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('precision-hero', false)
            ->assertSee('precision-stage', false)
            ->assertSee('data-webm-src-high', false)
            ->assertSee('data-webm-src-compact', false)
            ->assertSee('images/ibrahim/ibrahim-speaking-editorial.webp', false)
            ->assertSee('manifesto-section', false)
            ->assertSee('atlas-constellation', false)
            ->assertSee('decision-room', false)
            ->assertSee('My roles today', false)
            ->assertSee('I build. I lead. I solve problems with technology.', false)
            ->assertDontSee('PRECISION / PRACTICE', false)
            ->assertDontSee('precision-stage__note', false)
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
            '/en/work' => ['Selected work', 'What changed—and why.', 'Selected cases across distinct sectors. Each sets out the operating context, challenge, what changed, and practical impact.', 'Digi Pedia'],
            '/en/writing' => ['Technology in the language of business', 'From AI Experiment to Business Value'],
            '/en/about' => ['Building dependable digital systems', 'Code Moments'],
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
