<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_cookies_and_terms_are_truthful_and_available_in_arabic_and_english(): void
    {
        Setting::setValue('contact_email', 'privacy@example.com', 'contact');

        $this->get('/privacy')
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'strict-origin')
            ->assertSee('الخصوصية', false)
            ->assertSee('ما الذي نعالجه', false)
            ->assertSee('كلمة المرور المجزأة بأمان', false)
            ->assertSee('Google Analytics', false)
            ->assertSee('privacy@example.com', false)
            ->assertSee('365 يوماً', false)
            ->assertDontSee('<table', false);

        $this->get('/cookies')
            ->assertOk()
            ->assertSee('ملفات الارتباط والتخزين', false)
            ->assertSee('data-cookie-consent-auto-open="false"', false)
            ->assertSee('السماح بالتحليلات', false)
            ->assertSee('الضرورية فقط', false)
            ->assertSee('الإعدادات', false)
            ->assertSee('XSRF-TOKEN', false)
            ->assertSee('ibrahim-hasan-session', false)
            ->assertSee('ibrahimhasan_analytics_consent', false)
            ->assertSee('_ga_', false)
            ->assertSee('<bdi dir="ltr" lang="en"', false)
            ->assertDontSee('<table', false);

        $this->get('/terms')
            ->assertOk()
            ->assertSee('شروط الاستخدام', false)
            ->assertSee('البلاغات الخاصة ليست مساهمات عامة', false)
            ->assertSee('ولا ينشئ إرسال طلب استشارة', false);

        $this->get('/en/privacy?source=sensitive-query')
            ->assertOk()
            ->assertSee('Privacy', false)
            ->assertSee('What we process', false)
            ->assertSee('securely hashed password', false)
            ->assertSee('Approved comments may remain anonymised', false)
            ->assertSee('privacy@example.com', false)
            ->assertSee('<link rel="canonical" href="'.url('/en/privacy').'">', false)
            ->assertSee('<link rel="alternate" hreflang="ar" href="'.url('/privacy').'">', false)
            ->assertDontSee('source=sensitive-query', false)
            ->assertDontSee('<table', false);

        $this->get('/en/cookies')
            ->assertOk()
            ->assertSee('Cookies and storage', false)
            ->assertSee('data-cookie-consent-auto-open="false"', false)
            ->assertSee('Allow analytics', false)
            ->assertSee('Necessary only', false)
            ->assertSee('Settings', false)
            ->assertSee('Analytics are off by default', false)
            ->assertSee('remember_web_*', false)
            ->assertSee('_ga_', false)
            ->assertSee('<bdi dir="ltr" lang="en"', false)
            ->assertDontSee('<table', false);

        $this->get('/en/terms')
            ->assertOk()
            ->assertSee('Terms of use', false)
            ->assertSee('Private reports are not public contributions', false)
            ->assertSee('Sending or answering a consultation request does not create', false);
    }

    public function test_cookie_controls_default_to_privacy_without_turning_the_page_into_a_modal(): void
    {
        $analytics = file_get_contents(resource_path('js/google-analytics.js'));
        $consent = file_get_contents(resource_path('js/cookie-consent.js'));
        $consentView = file_get_contents(resource_path('views/components/partials/cookie-consent.blade.php'));
        $legalView = file_get_contents(resource_path('views/website/legal.blade.php'));

        $this->assertSame(43_200, config('auth.guards.web.remember'));
        $this->assertStringContainsString("gtag('consent', 'default'", $analytics);
        $this->assertStringContainsString("analytics_storage: 'denied'", $analytics);
        $this->assertStringContainsString("ad_personalization: 'denied'", $analytics);
        $this->assertStringContainsString('ga-disable-${measurementId}', $analytics);
        $this->assertStringContainsString('clearAnalyticsCookies()', $analytics);
        $this->assertStringContainsString("if (! hasAnalyticsConsent()) {\n    clearAnalyticsCookies();\n}", $analytics);
        $this->assertStringContainsString('allow_google_signals: false', $analytics);
        $this->assertStringContainsString('allow_ad_personalization_signals: false', $analytics);
        $this->assertStringContainsString('`${window.location.origin}${window.location.pathname}`', $analytics);
        $this->assertStringNotContainsString('window.location.href', $analytics);
        $this->assertStringContainsString('validConsentValues', $consent);
        $this->assertStringContainsString('${value}.${consentVersion}.${Date.now()}', $consent);
        $this->assertStringContainsString('parts.length !== 3', $consent);
        $this->assertStringContainsString('Number.isInteger(timestamp)', $consent);
        $this->assertStringContainsString("currentConsent() === 'accepted'", $analytics);
        $this->assertStringContainsString('showSettings', $consent);
        $this->assertStringContainsString('cookie-consent-visible', $consent);
        $this->assertStringContainsString('cookie-consent-visibility-changed', $consent);
        $this->assertStringNotContainsString('paddingBlockEnd', $consent);
        $this->assertStringNotContainsString('ResizeObserver', $consent);
        $this->assertStringNotContainsString('trapFocus', $consent);
        $this->assertStringNotContainsString('restoreFocus', $consent);
        $this->assertStringNotContainsString('cookieConsentInerted', $consent);
        $this->assertStringNotContainsString('cookie-consent-open', $consent);
        $this->assertStringNotContainsString('querySelectorAll(\'body >', $consent);
        $this->assertStringContainsString('role="region"', $consentView);
        $this->assertStringContainsString('cookie-consent__surface', $consentView);
        $this->assertStringContainsString('sm:flex-row', $consentView);
        $this->assertStringContainsString('class="button-primary min-h-11 px-4" @click="accept"', $consentView);
        $this->assertStringContainsString('class="button-quiet min-h-11 px-4" @click="reject"', $consentView);
        $this->assertStringNotContainsString('max-w-6xl', $consentView);
        $this->assertStringNotContainsString('font-display text-2xl', $consentView);
        $this->assertStringNotContainsString('aria-modal', $consentView);
        $this->assertStringNotContainsString('role="dialog"', $consentView);
        $this->assertStringNotContainsString('@keydown.tab=', $consentView);
        $this->assertStringNotContainsString('@keydown.escape.window=', $consentView);
        $this->assertStringContainsString('<dl class="mt-5 border-y border-ink/15">', $legalView);
        $this->assertStringContainsString('<span class="mt-3 block text-start">', $legalView);
        $this->assertStringContainsString('<span dir="ltr" class="inline-flex max-w-full flex-wrap gap-x-3 gap-y-2">', $legalView);
        $this->assertStringContainsString('<bdi dir="ltr" lang="en"', $legalView);
        $this->assertStringNotContainsString('overflow-x-auto', $legalView);
        $this->assertStringNotContainsString('min-w-[52rem]', $legalView);
        $this->assertStringNotContainsString('<table', $legalView);

        $this->get('/en/privacy')
            ->assertOk()
            ->assertSee('data-uses-livewire="false"', false)
            ->assertSee('data-cookie-consent-auto-open="false"', false)
            ->assertSee('data-cookie-consent', false)
            ->assertSee('role="region"', false)
            ->assertSee('data-open-cookie-preferences', false);

        config()->set('services.google_analytics.measurement_id', null);
        $this->get('/en')
            ->assertOk()
            ->assertSee('data-cookie-consent-auto-open="false"', false)
            ->assertDontSee('google-analytics-id', false);
    }

    public function test_analytics_is_allowlisted_and_only_prompts_when_production_has_an_explicit_measurement_id(): void
    {
        config()->set('services.google_analytics.measurement_id', 'G-TEST123');
        $originalEnvironment = $this->app->environment();

        try {
            $this->app->detectEnvironment(fn (): string => 'production');

            $this->get('/en')
                ->assertOk()
                ->assertHeader('Referrer-Policy', 'strict-origin')
                ->assertSee('<meta name="google-analytics-id" content="G-TEST123">', false)
                ->assertSee('data-cookie-consent-auto-open="true"', false);

            foreach (['/en/privacy', '/en/cookies', '/en/terms', '/en/reader/login', '/en/reader/register'] as $path) {
                $this->get($path)
                    ->assertOk()
                    ->assertSee('data-cookie-consent-auto-open="false"', false)
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
