<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_cookies_and_terms_are_complete_in_arabic_and_english(): void
    {
        Setting::setValue('contact_email', 'privacy@example.com', 'contact');

        $this->get('/privacy')
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'strict-origin')
            ->assertSee('الخصوصية والبيانات الشخصية', false)
            ->assertSee('مشغّل الموقع والتواصل', false)
            ->assertSee('طلبات الاستشارة', false)
            ->assertSee('التحليلات الاختيارية', false)
            ->assertSee('privacy@example.com', false)
            ->assertSee('href="'.localized_route('cookies').'"', false)
            ->assertDontSee('aria-label="المعلومات القانونية"', false)
            ->assertDontSee('سوريا', false);

        $this->get('/cookies')
            ->assertOk()
            ->assertSee('ملفات الارتباط وتخزين المتصفح', false)
            ->assertSee('data-cookie-consent-auto-open="false"', false)
            ->assertSee('اقرأ سياسة ملفات الارتباط', false)
            ->assertSee('السماح بالتحليلات', false)
            ->assertSee('استخدام الضرورية فقط', false)
            ->assertSee('لن يُحفظ قرار', false)
            ->assertSee('XSRF-TOKEN', false)
            ->assertSee('ibrahim-hasan-session', false)
            ->assertSee('ibrahimhasan_analytics_consent', false)
            ->assertSee('_ga_', false)
            ->assertSee('180 يوماً', false);

        $this->get('/terms')
            ->assertOk()
            ->assertSee('شروط استخدام الموقع والمجتمع', false)
            ->assertSee('البلاغات الخاصة ليست مساهمات عامة', false)
            ->assertSee('لا ينشئ إرسال استفسار', false);

        $this->get('/en/privacy?source=sensitive-query')
            ->assertOk()
            ->assertSee('Privacy and personal data', false)
            ->assertSee('Who operates the site', false)
            ->assertSee('Consultation requests', false)
            ->assertSee('Former reader', false)
            ->assertSee('privacy@example.com', false)
            ->assertSee('<link rel="canonical" href="'.url('/en/privacy').'">', false)
            ->assertSee('<link rel="alternate" hreflang="ar" href="'.url('/privacy').'">', false)
            ->assertDontSee('aria-label="Legal documents"', false)
            ->assertDontSee('source=sensitive-query', false)
            ->assertDontSee('Syria', false);

        $this->get('/en/cookies')
            ->assertOk()
            ->assertSee('Cookies and browser storage', false)
            ->assertSee('data-cookie-consent-auto-open="false"', false)
            ->assertSee('Read the cookie policy', false)
            ->assertSee('No choice is stored', false)
            ->assertSee('Analytics stays off and Google is not contacted', false)
            ->assertSee('remember_web_*', false)
            ->assertSee('_ga_', false)
            ->assertSee('Allow analytics', false)
            ->assertSee('Use essential cookies only', false);

        $this->get('/en/terms')
            ->assertOk()
            ->assertSee('Terms for using the site and community', false)
            ->assertSee('Private reports are not public contributions', false)
            ->assertSee('Sending or answering an inquiry does not create', false);
    }

    public function test_cookie_controls_default_to_privacy_and_support_accessible_withdrawal(): void
    {
        $analytics = file_get_contents(resource_path('js/google-analytics.js'));
        $consent = file_get_contents(resource_path('js/cookie-consent.js'));

        $this->assertSame(43_200, config('auth.guards.web.remember'));
        $this->assertStringContainsString("gtag('consent', 'default'", $analytics);
        $this->assertStringContainsString("analytics_storage: 'denied'", $analytics);
        $this->assertStringContainsString("ad_personalization: 'denied'", $analytics);
        $this->assertStringContainsString('ga-disable-${measurementId}', $analytics);
        $this->assertStringContainsString('clearAnalyticsCookies()', $analytics);
        $this->assertStringContainsString('allow_google_signals: false', $analytics);
        $this->assertStringContainsString('allow_ad_personalization_signals: false', $analytics);
        $this->assertStringContainsString('`${window.location.origin}${window.location.pathname}`', $analytics);
        $this->assertStringNotContainsString('window.location.href', $analytics);
        $this->assertStringContainsString('validConsentValues', $consent);
        $this->assertStringContainsString('${value}.${consentVersion}.${Date.now()}', $consent);
        $this->assertStringContainsString('trapFocus(event)', $consent);
        $this->assertStringContainsString('restoreFocus()', $consent);
        $this->assertStringContainsString('dataset.cookieConsentInerted', $consent);
        $this->assertStringContainsString('parts.length !== 3', $consent);
        $this->assertStringContainsString('Number.isInteger(timestamp)', $consent);
        $this->assertStringContainsString("currentConsent() === 'accepted'", $analytics);
        $this->assertStringNotContainsString('startsWith(`accepted.', $analytics);
        $this->assertStringContainsString('visible: autoOpen && currentConsent() === null', $consent);
        $this->assertStringNotContainsString('this.reject();', $consent);

        $this->get('/en/privacy')
            ->assertOk()
            ->assertSee('data-uses-livewire="false"', false)
            ->assertSee('data-cookie-consent-auto-open="false"', false)
            ->assertSee('data-cookie-consent', false)
            ->assertSee('@keydown.tab="trapFocus"', false)
            ->assertSee('@keydown.escape.window="close"', false)
            ->assertSee('aria-pressed=', false)
            ->assertSee('data-open-cookie-preferences', false);

        $this->get('/en')
            ->assertOk()
            ->assertSee('data-cookie-consent-auto-open="true"', false);
    }

    public function test_analytics_is_allowlisted_and_sensitive_routes_never_expose_its_configuration(): void
    {
        config()->set('services.google_analytics.measurement_id', 'G-TEST123');
        $originalEnvironment = $this->app->environment();

        try {
            $this->app->detectEnvironment(fn (): string => 'production');

            $this->get('/en')
                ->assertOk()
                ->assertHeader('Referrer-Policy', 'strict-origin')
                ->assertSee('<meta name="google-analytics-id" content="G-TEST123">', false);

            foreach (['/en/privacy', '/en/cookies', '/en/terms', '/en/reader/login', '/en/reader/register'] as $path) {
                $this->get($path)
                    ->assertOk()
                    ->assertDontSee('google-analytics-id', false);
            }

            $this->get('/en/reader/reset-password/private-token?email=private@example.com')
                ->assertOk()
                ->assertHeader('Referrer-Policy', 'no-referrer')
                ->assertSee('<meta name="referrer" content="no-referrer">', false)
                ->assertDontSee('google-analytics-id', false);
        } finally {
            $this->app->detectEnvironment(fn (): string => $originalEnvironment);
        }
    }

    public function test_collection_points_link_to_the_applicable_legal_information(): void
    {
        $this->get('/en/reader/register')
            ->assertOk()
            ->assertSee('name="terms_accepted"', false)
            ->assertSee('required', false)
            ->assertSee('href="'.localized_route('terms', locale: 'en').'"', false)
            ->assertSee('href="'.localized_route('privacy', locale: 'en').'"', false);

        $consultation = file_get_contents(resource_path('views/livewire/website/consultation-request.blade.php'));
        $community = file_get_contents(resource_path('views/livewire/website/article-community.blade.php'));

        $this->assertStringContainsString("localized_route('privacy')", $consultation);
        $this->assertStringContainsString("__('community.contribution_notice'", $community);
        $this->assertStringContainsString("__('community.reply_notice'", $community);
        $this->assertStringContainsString("__('community.report_notice'", $community);
    }
}
