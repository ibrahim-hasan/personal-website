<?php

namespace Tests\Feature\Security;

use App\Services\Security\CspReportSignalStore;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CspReportReceiverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('security.csp.report_only', true);
        RateLimiter::clear($this->rateLimitKey());
    }

    protected function tearDown(): void
    {
        RateLimiter::clear($this->rateLimitKey());

        parent::tearDown();
    }

    public function test_it_records_only_a_safe_legacy_csp_signal_and_returns_no_report_data(): void
    {
        $store = app(CspReportSignalStore::class);
        $before = $store->countForCurrentMinute('script-src', 'script');

        $response = $this->postJson('/api/csp-reports', [
            'csp-report' => [
                'document-uri' => 'https://private.example.test/contact?token=very-secret',
                'blocked-uri' => 'https://blocked.example.test/script.js?email=person@example.test',
                'script-sample' => 'window.secret = "do-not-store"',
                'violated-directive' => "script-src-elem 'self'",
            ],
        ]);

        $response
            ->assertNoContent()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, noarchive')
            ->assertHeaderMissing('X-Request-Id');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertSame($before + 1, $store->countForCurrentMinute('script-src', 'script'));
        $this->assertSame('', $response->getContent());
    }

    public function test_it_accepts_reporting_api_payloads_and_ignores_reports_when_observation_is_disabled(): void
    {
        $store = app(CspReportSignalStore::class);
        $before = $store->countForCurrentMinute('style-src', 'style');
        $payload = json_encode([
            [
                'type' => 'csp-violation',
                'body' => [
                    'effectiveDirective' => 'style-src-attr',
                    'documentURL' => 'https://private.example.test/?session=secret',
                    'blockedURL' => 'https://blocked.example.test/?token=secret',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call('POST', '/api/csp-reports', [], [], [], [
            'CONTENT_TYPE' => 'application/reports+json',
        ], $payload)->assertNoContent();

        $this->assertSame($before + 1, $store->countForCurrentMinute('style-src', 'style'));

        config()->set('security.csp.report_only', false);

        $this->call('POST', '/api/csp-reports', [], [], [], [
            'CONTENT_TYPE' => 'application/reports+json',
        ], $payload)->assertNoContent();

        $this->assertSame($before + 1, $store->countForCurrentMinute('style-src', 'style'));
    }

    public function test_it_rate_limits_reports_without_an_ip_derived_key(): void
    {
        config()->set('security.csp.reporting.rate_limit_per_minute', 1);
        RateLimiter::clear($this->rateLimitKey());

        $this->postJson('/api/csp-reports', ['csp-report' => ['violated-directive' => 'img-src']])
            ->assertNoContent();

        $this->postJson('/api/csp-reports', ['csp-report' => ['violated-directive' => 'img-src']])
            ->assertStatus(429);
    }

    private function rateLimitKey(): string
    {
        return md5('csp-reportscsp-report-receiver');
    }
}
