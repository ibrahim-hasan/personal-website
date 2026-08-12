<?php

namespace Tests\Unit\Services\WebsitePerformance;

use App\Contracts\WebsitePerformance\GoogleAccessTokenProvider;
use App\Services\WebsitePerformance\SearchConsoleClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SearchConsoleClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'http://localhost');
        config()->set('services.website_performance.website_url', 'https://ibrahimhasan.net');
        config()->set('services.website_performance.search_console_property', 'sc-domain:ibrahimhasan.net');
        config()->set('services.website_performance.url_inspection_limit', 50);
        config()->set('services.website_performance.url_inspection_concurrency', 5);
    }

    public function test_it_uses_final_search_data_and_the_live_production_sitemap_for_inspection(): void
    {
        $inspectionUrls = [];
        $searchRequests = [];

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$inspectionUrls, &$searchRequests) {
            if ($request->url() === $this->sitemapUrl()) {
                return Http::response($this->sitemapXml(51), 200, ['Content-Type' => 'application/xml']);
            }

            if (str_contains($request->url(), 'urlInspection/index:inspect')) {
                $inspectionUrls[] = $request->data()['inspectionUrl'] ?? null;

                return Http::response([
                    'inspectionResult' => [
                        'indexStatusResult' => [
                            'verdict' => 'PASS',
                            'coverageState' => 'Submitted and indexed',
                            'indexingState' => 'INDEXING_ALLOWED',
                        ],
                    ],
                ], 200);
            }

            $payload = $request->data();
            $searchRequests[] = $payload;

            return Http::response(['rows' => [$this->searchRowFor($payload)]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('ok', $report['status']);
        $this->assertTrue($report['url_inspection']['available']);
        $this->assertTrue($report['url_inspection']['capped']);
        $this->assertSame(50, $report['url_inspection']['requested_count']);
        $this->assertSame(50, $report['url_inspection']['inspected_count']);
        $this->assertCount(50, $inspectionUrls);
        $this->assertNotContains(null, $inspectionUrls);

        foreach ($inspectionUrls as $inspectionUrl) {
            $this->assertStringStartsWith('https://ibrahimhasan.net/live-', $inspectionUrl);
            $this->assertStringNotContainsString('localhost', $inspectionUrl);
        }

        foreach ($searchRequests as $payload) {
            $this->assertSame('final', $payload['dataState']);
        }
    }

    public function test_it_treats_omitted_search_rows_as_unavailable_not_zero(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if ($request->url() === $this->sitemapUrl()) {
                return Http::response($this->sitemapXml(), 200, ['Content-Type' => 'application/xml']);
            }

            if (str_contains($request->url(), 'urlInspection/index:inspect')) {
                return Http::response([
                    'inspectionResult' => ['indexStatusResult' => ['verdict' => 'PASS']],
                ], 200);
            }

            $payload = $request->data();
            $isCurrentTotals = ($payload['dimensions'] ?? []) === []
                && ($payload['startDate'] ?? null) === '2026-07-13';

            return $isCurrentTotals
                ? Http::response([], 200)
                : Http::response(['rows' => [$this->searchRowFor($payload)]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('partial', $report['status']);
        $this->assertNull($report['current']['totals']);
        $this->assertContains('search_console_rows_unavailable', $report['warnings']);
    }

    public function test_it_rejects_malformed_or_negative_search_metrics_instead_of_coercing_them_to_zero(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if ($request->url() === $this->sitemapUrl()) {
                return Http::response($this->sitemapXml(), 200, ['Content-Type' => 'application/xml']);
            }

            if (str_contains($request->url(), 'urlInspection/index:inspect')) {
                return Http::response([
                    'inspectionResult' => ['indexStatusResult' => ['verdict' => 'PASS']],
                ], 200);
            }

            $payload = $request->data();
            $row = $this->searchRowFor($payload);
            $isCurrentTotals = ($payload['dimensions'] ?? []) === []
                && ($payload['startDate'] ?? null) === '2026-07-13';

            if ($isCurrentTotals) {
                $row['clicks'] = -1;
            }

            return Http::response(['rows' => [$row]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('partial', $report['status']);
        $this->assertNull($report['current']['totals']);
        $this->assertContains('search_console_totals_invalid', $report['warnings']);
    }

    public function test_it_rejects_multiple_no_dimension_total_rows(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if ($request->url() === $this->sitemapUrl()) {
                return Http::response($this->sitemapXml(0), 200, ['Content-Type' => 'application/xml']);
            }

            $payload = $request->data();
            $row = $this->searchRowFor($payload);
            $isCurrentTotals = ($payload['dimensions'] ?? []) === []
                && ($payload['startDate'] ?? null) === '2026-07-13';

            return $isCurrentTotals
                ? Http::response(['rows' => [$row, $row]], 200)
                : Http::response(['rows' => [$row]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertNull($report['current']['totals']);
        $this->assertContains('search_console_totals_invalid', $report['warnings']);
    }

    public function test_it_retries_a_transient_search_console_failure(): void
    {
        $attempts = 0;

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$attempts) {
            if ($request->url() === $this->sitemapUrl()) {
                return Http::response($this->sitemapXml(), 200, ['Content-Type' => 'application/xml']);
            }

            if (str_contains($request->url(), 'urlInspection/index:inspect')) {
                return Http::response([
                    'inspectionResult' => ['indexStatusResult' => ['verdict' => 'PASS']],
                ], 200);
            }

            $payload = $request->data();
            $isCurrentQuery = ($payload['dimensions'] ?? []) === ['query']
                && ($payload['startDate'] ?? null) === '2026-07-13';

            if ($isCurrentQuery) {
                $attempts++;

                if ($attempts === 1) {
                    return Http::response([], 503);
                }
            }

            return Http::response(['rows' => [$this->searchRowFor($payload)]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('ok', $report['status']);
        $this->assertSame(2, $attempts);
    }

    public function test_it_stops_after_two_retries_for_a_persistent_search_analytics_rate_limit(): void
    {
        $this->assertPersistentSearchAnalyticsRetryCount(429);
    }

    public function test_it_stops_after_two_retries_for_a_persistent_search_analytics_server_error(): void
    {
        $this->assertPersistentSearchAnalyticsRetryCount(503);
    }

    public function test_it_stops_after_two_retries_for_a_persistent_url_inspection_rate_limit(): void
    {
        $this->assertPersistentUrlInspectionRetryCount(429);
    }

    public function test_it_stops_after_two_retries_for_a_persistent_url_inspection_server_error(): void
    {
        $this->assertPersistentUrlInspectionRetryCount(503);
    }

    private function assertPersistentSearchAnalyticsRetryCount(int $status): void
    {
        $attempts = 0;

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$attempts, $status) {
            if ($request->url() === $this->sitemapUrl()) {
                return Http::response($this->sitemapXml(), 200, ['Content-Type' => 'application/xml']);
            }

            if (str_contains($request->url(), 'urlInspection/index:inspect')) {
                return Http::response([
                    'inspectionResult' => ['indexStatusResult' => ['verdict' => 'PASS']],
                ], 200);
            }

            $payload = $request->data();
            $isCurrentQuery = ($payload['dimensions'] ?? []) === ['query']
                && ($payload['startDate'] ?? null) === '2026-07-13';

            if ($isCurrentQuery) {
                $attempts++;

                return Http::response([], $status);
            }

            return Http::response(['rows' => [$this->searchRowFor($payload)]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame(3, $attempts);
        $this->assertSame('partial', $report['status']);
        $this->assertFalse($report['current']['queries']['available']);
    }

    private function assertPersistentUrlInspectionRetryCount(int $status): void
    {
        $attempts = 0;

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$attempts, $status) {
            if ($request->url() === $this->sitemapUrl()) {
                return Http::response($this->sitemapXml(), 200, ['Content-Type' => 'application/xml']);
            }

            if (str_contains($request->url(), 'urlInspection/index:inspect')) {
                $attempts++;

                return Http::response([], $status);
            }

            return Http::response(['rows' => [$this->searchRowFor($request->data())]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame(3, $attempts);
        $this->assertFalse($report['url_inspection']['available']);
        $this->assertContains('url_inspection_request_unavailable', $report['warnings']);
    }

    public function test_it_excludes_queries_that_embed_an_email_address(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if ($request->url() === $this->sitemapUrl()) {
                return Http::response($this->sitemapXml(0), 200, ['Content-Type' => 'application/xml']);
            }

            $payload = $request->data();
            $isCurrentQuery = ($payload['dimensions'] ?? []) === ['query']
                && ($payload['startDate'] ?? null) === '2026-07-13';
            $row = $this->searchRowFor($payload);

            if ($isCurrentQuery) {
                $row['keys'] = ['consult jane@example.test'];
            }

            return Http::response(['rows' => [$row]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame([], $report['current']['queries']['rows']);
        $this->assertStringNotContainsString('jane@example.test', json_encode($report, JSON_THROW_ON_ERROR));
    }

    public function test_it_preserves_a_full_page_when_the_terminal_pagination_page_is_empty(): void
    {
        $queryStartRows = [];

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$queryStartRows) {
            if ($request->url() === $this->sitemapUrl()) {
                return Http::response($this->sitemapXml(0), 200, ['Content-Type' => 'application/xml']);
            }

            $payload = $request->data();
            $isCurrentQuery = ($payload['dimensions'] ?? []) === ['query']
                && ($payload['startDate'] ?? null) === '2026-07-13';

            if ($isCurrentQuery) {
                $startRow = (int) ($payload['startRow'] ?? 0);
                $queryStartRows[] = $startRow;

                if ($startRow === 0) {
                    return Http::response([
                        'rows' => array_fill(0, 25000, $this->searchRowFor($payload)),
                    ], 200);
                }

                return Http::response(['rows' => []], 200);
            }

            return Http::response(['rows' => [$this->searchRowFor($payload)]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame([0, 25000], $queryStartRows);
        $this->assertCount(25000, $report['current']['queries']['rows']);
    }

    public function test_it_keeps_a_source_usable_and_uses_the_previous_cutoff_when_only_previous_page_performance_is_available(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            if ($request->url() === $this->sitemapUrl()) {
                return Http::response($this->sitemapXml(0), 200, ['Content-Type' => 'application/xml']);
            }

            $payload = $request->data();
            $isPreviousPage = ($payload['dimensions'] ?? []) === ['page']
                && ($payload['startDate'] ?? null) === '2026-06-15';

            return $isPreviousPage
                ? Http::response(['rows' => [$this->searchRowFor($payload)]], 200)
                : Http::response([], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('partial', $report['status']);
        $this->assertTrue($report['previous']['pages']['available']);
        $this->assertSame('2026-07-12', $report['fresh_through']);
    }

    private function client(): SearchConsoleClient
    {
        return new SearchConsoleClient(
            app(Factory::class),
            app(Repository::class),
            new class implements GoogleAccessTokenProvider
            {
                public function accessToken(): string
                {
                    return 'google-access-token';
                }
            },
        );
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
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function searchRowFor(array $payload): array
    {
        $dimension = $payload['dimensions'][0] ?? null;

        $value = match ($dimension) {
            'query' => 'consultation strategy saudi',
            'page' => 'https://ibrahimhasan.net/contact?campaign=private',
            'country' => 'sa',
            'device' => 'MOBILE',
            default => null,
        };

        return array_filter([
            'keys' => $value === null ? null : [$value],
            'clicks' => 12,
            'impressions' => 80,
            'ctr' => 0.15,
            'position' => 3.4,
        ], fn (mixed $item): bool => $item !== null);
    }

    private function sitemapUrl(): string
    {
        return 'https://ibrahimhasan.net/sitemap.xml';
    }

    private function sitemapXml(int $count = 1): string
    {
        $urls = [];

        for ($index = 1; $index <= $count; $index++) {
            $urls[] = sprintf(
                '<url><loc>https://ibrahimhasan.net/live-%d</loc><lastmod>2026-08-01</lastmod></url>',
                $index,
            );
        }

        return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.implode('', $urls).'</urlset>';
    }
}
