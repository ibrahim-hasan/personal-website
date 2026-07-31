<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('contact_email', 'privacy@example.com', 'contact');
    }

    public function test_standard_security_headers_are_present_without_enabling_csp_reporting(): void
    {
        $this->get('/privacy')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin')
            ->assertHeader('Permissions-Policy', $this->permissionsPolicy())
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only')
            ->assertHeaderMissing('Reporting-Endpoints')
            ->assertHeaderMissing('Report-To');
    }

    public function test_csp_is_opt_in_report_only_and_uses_only_explicit_sources(): void
    {
        config()->set('security.csp.report_only', true);
        config()->set('security.csp.media_origins', ['https://media-staging.ibrahimhasan.net']);

        $response = $this->get('/privacy');
        $policy = (string) $response->headers->get('Content-Security-Policy-Report-Only');
        $content = $response->getContent();
        $endpoint = rtrim((string) config('app.url'), '/').'/api/csp-reports';
        $reportTo = json_decode((string) $response->headers->get('Report-To'), true, flags: JSON_THROW_ON_ERROR);

        $response
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeaderMissing('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString('report-to csp', $policy);
        $this->assertStringContainsString('https://challenges.cloudflare.com', $policy);
        $this->assertStringContainsString('https://www.googletagmanager.com', $policy);
        $this->assertStringContainsString('https://www.google-analytics.com', $policy);
        $this->assertStringContainsString('https://fonts.bunny.net', $policy);
        $this->assertStringContainsString('https://media-staging.ibrahimhasan.net', $policy);
        $this->assertMatchesRegularExpression('/<script\s+nonce="([^"]+)"\s+type="application\/ld\+json">/', $content);
        preg_match('/<script\s+nonce="([^"]+)"\s+type="application\/ld\+json">/', $content, $nonceMatches);
        $this->assertStringContainsString("'nonce-{$nonceMatches[1]}'", $policy);
        $this->assertMatchesRegularExpression(
            '/<script\s+nonce="'.preg_quote($nonceMatches[1], '/').'"\s+type="application\/ld\+json">/',
            $content,
        );
        $this->assertStringNotContainsString('unsafe-eval', $policy);
        $this->assertStringNotContainsString('unsafe-inline', $policy);
        $this->assertStringNotContainsString('*', $policy);
        $response->assertHeader('Reporting-Endpoints', 'csp="'.$endpoint.'"');
        $this->assertSame('csp', $reportTo['group']);
        $this->assertSame(86_400, $reportTo['max_age']);
        $this->assertSame($endpoint, $reportTo['endpoints'][0]['url']);
    }

    public function test_global_baseline_headers_cover_admin_and_non_html_health_responses(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin');

        config()->set('security.csp.report_only', true);

        $this->get('/health/ready')
            ->assertStatus(503)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only')
            ->assertHeaderMissing('Reporting-Endpoints')
            ->assertHeaderMissing('Report-To');
    }

    public function test_unsafe_and_broad_csp_source_values_are_not_emitted(): void
    {
        config()->set('security.csp.report_only', true);
        config()->set('security.csp.sources.script-src', ["'self'", "'unsafe-eval'", '*', 'https://trusted.example.test/path']);
        config()->set('security.csp.media_origins', ['https://media.example.test/', 'https://untrusted.example.test/path']);

        $policy = (string) $this->get('/privacy')->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString('https://media.example.test', $policy);
        $this->assertStringNotContainsString('unsafe-eval', $policy);
        $this->assertStringNotContainsString('trusted.example.test', $policy);
        $this->assertStringNotContainsString('untrusted.example.test', $policy);
        $this->assertStringNotContainsString('*', $policy);
    }

    public function test_reporting_endpoint_uses_only_the_configured_application_url(): void
    {
        config()->set('security.csp.report_only', true);
        config()->set('app.url', 'https://staging.ibrahimhasan.net');

        $this->get('/privacy', ['Host' => 'untrusted.example.test'])
            ->assertOk()
            ->assertHeader('Reporting-Endpoints', 'csp="https://staging.ibrahimhasan.net/api/csp-reports"');
    }

    public function test_reporting_headers_are_not_emitted_for_an_unsafe_configured_endpoint(): void
    {
        config()->set('security.csp.report_only', true);
        config()->set('app.url', 'https://staging.ibrahimhasan.net/?token=must-not-be-emitted');

        $response = $this->get('/privacy');

        $response
            ->assertOk()
            ->assertHeaderMissing('Reporting-Endpoints')
            ->assertHeaderMissing('Report-To');

        $this->assertStringNotContainsString('report-to csp', (string) $response->headers->get('Content-Security-Policy-Report-Only'));
    }

    public function test_sensitive_routes_keep_their_stricter_privacy_headers(): void
    {
        $response = $this->get('/reader/login');

        $response
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'no-referrer');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('no-cache', $response->headers->get('Pragma'));
    }

    private function permissionsPolicy(): string
    {
        return 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()';
    }
}
