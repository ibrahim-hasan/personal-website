<?php

namespace Tests\Unit\Services\WebsitePerformance;

use App\Services\WebsitePerformance\FirstPartyMetricsClient;
use App\Services\WebsitePerformance\WebsitePerformanceSourceException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirstPartyMetricsClientTest extends TestCase
{
    /** @var list<Request> */
    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.website_performance.website_url', 'https://ibrahimhasan.net');
        config()->set('services.website_performance.metrics_url', 'https://ibrahimhasan.net/api/v1/metrics/website');
        config()->set('services.website_performance.metrics_token_url', 'https://ibrahimhasan.net/oauth/token');
        config()->set('services.website_performance.metrics_client_id', 'reporting-client');
        config()->set('services.website_performance.metrics_client_secret', 'reporting-secret');
        config()->set('services.website_performance.timezone', 'Asia/Riyadh');
    }

    public function test_it_retrieves_only_the_validated_aggregate_contract(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $this->requests[] = $request;

            if ($request->url() === $this->tokenUrl()) {
                return Http::response(['access_token' => 'first-party-token'], 200);
            }

            return Http::response($this->payloadForRequest($request), 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('ok', $report['status']);
        $this->assertSame(2, $report['current']['inquiries']['total']);
        $this->assertSame(1, $report['current']['inquiries']['responded']);
        $this->assertSame(0.5, $report['current']['inquiries']['response_rate']);
        $this->assertCount(4, $this->requests);

        $tokenRequest = $this->requests[0];
        $this->assertSame($this->tokenUrl(), $tokenRequest->url());
        $this->assertTrue($tokenRequest->isForm());
        $this->assertSame('client_credentials', $tokenRequest['grant_type']);
        $this->assertSame('analytics:read', $tokenRequest['scope']);
        $this->assertSame('reporting-client', $tokenRequest['client_id']);
        $this->assertSame('reporting-secret', $tokenRequest['client_secret']);

        foreach (array_slice($this->requests, 1) as $request) {
            $this->assertTrue($request->hasHeader('Authorization', 'Bearer first-party-token'));
            $this->assertStringStartsWith('https://ibrahimhasan.net/api/v1/metrics/website?', $request->url());
        }
    }

    public function test_it_retries_a_rate_limited_token_request(): void
    {
        $tokenAttempts = 0;

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$tokenAttempts) {
            if ($request->url() === $this->tokenUrl()) {
                $tokenAttempts++;

                return $tokenAttempts === 1
                    ? Http::response(['error' => 'slow_down'], 429)
                    : Http::response(['access_token' => 'first-party-token'], 200);
            }

            return Http::response($this->payloadForRequest($request), 200);
        });

        $this->client()->collect($this->periods());

        $this->assertSame(2, $tokenAttempts);
    }

    public function test_it_does_not_follow_an_authentication_redirect(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            $this->tokenUrl() => Http::response('', 302, ['Location' => 'https://untrusted.example/token']),
        ]);

        $this->assertSourceException(
            fn (): array => $this->client()->collect($this->periods()),
            'first_party_authentication_unavailable',
        );

        Http::assertSentCount(1);
    }

    public function test_it_rejects_a_metrics_or_token_url_outside_the_configured_website_origin(): void
    {
        config()->set('services.website_performance.metrics_token_url', 'https://untrusted.example/oauth/token');
        Http::preventStrayRequests();

        $this->assertSourceException(
            fn (): array => $this->client()->collect($this->periods()),
            'first_party_configuration_unavailable',
        );

        Http::assertNothingSent();
    }

    public function test_it_rejects_an_incomplete_aggregate_contract_instead_of_substituting_zero(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if ($request->url() === $this->tokenUrl()) {
                return Http::response(['access_token' => 'first-party-token'], 200);
            }

            $payload = $this->payloadForRequest($request);
            unset($payload['data']['inquiries']['total']);

            return Http::response($payload, 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('unavailable', $report['status']);
        $this->assertNull($report['current']['inquiries']);
        $this->assertContains('first_party_current_unavailable', $report['warnings']);
    }

    public function test_it_rejects_negative_aggregate_counts(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if ($request->url() === $this->tokenUrl()) {
                return Http::response(['access_token' => 'first-party-token'], 200);
            }

            $payload = $this->payloadForRequest($request);
            $payload['data']['inquiries']['total'] = -1;

            return Http::response($payload, 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('unavailable', $report['status']);
        $this->assertNull($report['current']['inquiries']);
        $this->assertContains('first_party_current_unavailable', $report['warnings']);
    }

    public function test_it_preserves_a_usable_previous_window_when_current_and_context_are_unavailable(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if ($request->url() === $this->tokenUrl()) {
                return Http::response(['access_token' => 'first-party-token'], 200);
            }

            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            if (in_array($query['start'] ?? null, ['2026-07-13', '2026-05-12'], true)) {
                return Http::response([], 200);
            }

            return Http::response($this->payloadForRequest($request), 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('partial', $report['status']);
        $this->assertNull($report['current']['inquiries']);
        $this->assertSame(2, $report['previous']['inquiries']['total']);
        $this->assertNull($report['context_90d']['inquiries']);
        $this->assertSame('2026-07-12', $report['fresh_through']);
        $this->assertContains('first_party_current_unavailable', $report['warnings']);
        $this->assertContains('first_party_context_90d_unavailable', $report['warnings']);
        $this->assertNull($report['deltas']['total']['absolute']);
    }

    private function client(): FirstPartyMetricsClient
    {
        return new FirstPartyMetricsClient(app(Factory::class), app(Repository::class));
    }

    /**
     * @return array{current: array{start: string, end: string}, previous: array{start: string, end: string}, context_90d: array{start: string, end: string}}
     */
    private function periods(): array
    {
        return [
            'current' => ['start' => '2026-07-13', 'end' => '2026-08-09'],
            'previous' => ['start' => '2026-06-15', 'end' => '2026-07-12'],
            'context_90d' => ['start' => '2026-05-12', 'end' => '2026-08-09'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadForRequest(Request $request): array
    {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $start = (string) ($query['start'] ?? '');
        $end = (string) ($query['end'] ?? '');

        return [
            'data' => [
                'timezone' => 'Asia/Riyadh',
                'start_date' => $start,
                'end_date' => $end,
                'inquiries' => [
                    'total' => 2,
                    'by_day' => [['date' => $start, 'count' => 2]],
                    'by_locale' => [['locale' => 'ar', 'count' => 2]],
                    'by_service' => [['service_key' => 'ai-adoption', 'count' => 2]],
                    'by_status' => [['status' => 'new', 'count' => 2]],
                    'responded' => 1,
                    'response_rate' => 0.5,
                ],
            ],
            'meta' => ['privacy' => 'aggregate_only'],
        ];
    }

    private function tokenUrl(): string
    {
        return 'https://ibrahimhasan.net/oauth/token';
    }

    private function assertSourceException(callable $callback, string $reason): void
    {
        try {
            $callback();
            $this->fail('The aggregate source contract should have been rejected.');
        } catch (WebsitePerformanceSourceException $exception) {
            $this->assertSame($reason, $exception->reason);
        }
    }
}
