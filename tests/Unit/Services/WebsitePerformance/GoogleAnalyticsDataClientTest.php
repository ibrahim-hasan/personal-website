<?php

namespace Tests\Unit\Services\WebsitePerformance;

use App\Contracts\WebsitePerformance\GoogleAccessTokenProvider;
use App\Services\WebsitePerformance\GoogleAnalyticsDataClient;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAnalyticsDataClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.website_performance.ga4_property_id', '545826061');
    }

    public function test_it_reports_cta_events_with_their_ui_location_and_page_type(): void
    {
        $ctaReports = [];

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$ctaReports) {
            $payload = $request->data();
            $dimensions = array_column($payload['dimensions'] ?? [], 'name');

            if ($dimensions === ['eventName', 'customEvent:ui_location', 'customEvent:page_type']) {
                $ctaReports[] = $payload;
            }

            return Http::response(['rows' => [$this->rowFor($payload)]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('ok', $report['status']);
        $this->assertSame([
            [
                'event_name' => 'consultation_submit_success',
                'ui_location' => 'hero_consultation',
                'page_type' => 'home',
                'eventCount' => 7,
                'activeUsers' => 12,
            ],
        ], $report['current']['cta_funnel']['rows']);
        $this->assertCount(2, $ctaReports);

        foreach ($ctaReports as $payload) {
            $this->assertSame(
                ['eventName', 'customEvent:ui_location', 'customEvent:page_type'],
                array_column($payload['dimensions'], 'name'),
            );
            $this->assertSame(
                [
                    'primary_cta_click',
                    'service_cta_click',
                    'direct_contact_click',
                    'consultation_form_start',
                    'consultation_submit_success',
                    'consultation_submit_error',
                ],
                $payload['dimensionFilter']['filter']['inListFilter']['values'],
            );
        }
    }

    public function test_it_marks_missing_metric_values_as_partial_instead_of_zero(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $payload = $request->data();
            $row = $this->rowFor($payload);
            $dimensions = array_column($payload['dimensions'] ?? [], 'name');

            if ($dimensions === [] && ($payload['dateRanges'][0]['startDate'] ?? null) === '2026-07-13') {
                array_pop($row['metricValues']);
            }

            return Http::response(['rows' => [$row]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('partial', $report['status']);
        $this->assertNull($report['current']['totals']);
        $this->assertContains('ga4_current_totals_invalid', $report['warnings']);
    }

    public function test_it_paginates_ga4_reports_when_row_count_exceeds_the_page_limit(): void
    {
        $landingPageOffsets = [];

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$landingPageOffsets) {
            $payload = $request->data();
            $dimensions = array_column($payload['dimensions'] ?? [], 'name');
            $isCurrentLandingPages = $dimensions === ['landingPagePlusQueryString']
                && ($payload['dateRanges'][0]['startDate'] ?? null) === '2026-07-13';

            if ($isCurrentLandingPages) {
                $offset = $payload['offset'] ?? null;
                $landingPageOffsets[] = $offset;
                $row = $this->rowFor($payload);

                return $offset === null
                    ? Http::response(['rows' => array_fill(0, 1000, $row), 'rowCount' => 1001], 200)
                    : Http::response(['rows' => [$row], 'rowCount' => 1001], 200);
            }

            return Http::response(['rows' => [$this->rowFor($payload)]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('ok', $report['status']);
        $this->assertSame([null, '1000'], $landingPageOffsets);
        $this->assertCount(1001, $report['current']['landing_pages']['rows']);
    }

    public function test_it_marks_a_ga4_report_unavailable_when_a_follow_up_page_is_incomplete(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $payload = $request->data();
            $dimensions = array_column($payload['dimensions'] ?? [], 'name');
            $isCurrentLandingPages = $dimensions === ['landingPagePlusQueryString']
                && ($payload['dateRanges'][0]['startDate'] ?? null) === '2026-07-13';

            if ($isCurrentLandingPages) {
                $row = $this->rowFor($payload);

                return ($payload['offset'] ?? null) === null
                    ? Http::response(['rows' => array_fill(0, 1000, $row), 'rowCount' => 1001], 200)
                    : Http::response(['rows' => [], 'rowCount' => 1001], 200);
            }

            return Http::response(['rows' => [$this->rowFor($payload)]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('partial', $report['status']);
        $this->assertFalse($report['current']['landing_pages']['available']);
        $this->assertContains('ga4_current_landing_pages_unavailable', $report['warnings']);
    }

    public function test_it_marks_a_ga4_report_unavailable_when_row_count_is_malformed(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $payload = $request->data();
            $dimensions = array_column($payload['dimensions'] ?? [], 'name');
            $isCurrentAcquisition = $dimensions === ['sessionDefaultChannelGroup']
                && ($payload['dateRanges'][0]['startDate'] ?? null) === '2026-07-13';
            $response = ['rows' => [$this->rowFor($payload)]];

            if ($isCurrentAcquisition) {
                $response['rowCount'] = 'not-a-number';
            }

            return Http::response($response, 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('partial', $report['status']);
        $this->assertFalse($report['current']['acquisition_channels']['available']);
        $this->assertContains('ga4_current_acquisition_unavailable', $report['warnings']);
    }

    public function test_it_retries_a_rate_limited_ga4_report(): void
    {
        $attempts = 0;

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$attempts) {
            $payload = $request->data();
            $dimensions = array_column($payload['dimensions'] ?? [], 'name');
            $isCurrentTotals = $dimensions === []
                && ($payload['dateRanges'][0]['startDate'] ?? null) === '2026-07-13';

            if ($isCurrentTotals) {
                $attempts++;

                if ($attempts === 1) {
                    return Http::response(['error' => ['code' => 429]], 429);
                }
            }

            return Http::response(['rows' => [$this->rowFor($payload)]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('ok', $report['status']);
        $this->assertSame(2, $attempts);
    }

    public function test_it_stops_after_two_retries_for_a_persistent_ga4_rate_limit(): void
    {
        $this->assertPersistentGa4RetryCount(429);
    }

    public function test_it_stops_after_two_retries_for_a_persistent_ga4_server_error(): void
    {
        $this->assertPersistentGa4RetryCount(503);
    }

    public function test_it_rejects_non_array_ga_rows_instead_of_silently_dropping_them(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $payload = $request->data();
            $row = $this->rowFor($payload);
            $dimensions = array_column($payload['dimensions'] ?? [], 'name');

            if ($dimensions === ['sessionDefaultChannelGroup'] && ($payload['dateRanges'][0]['startDate'] ?? null) === '2026-07-13') {
                return Http::response(['rows' => [$row, 'malformed']], 200);
            }

            return Http::response(['rows' => [$row]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('partial', $report['status']);
        $this->assertFalse($report['current']['acquisition_channels']['available']);
        $this->assertContains('ga4_current_acquisition_unavailable', $report['warnings']);
    }

    public function test_it_omits_malformed_utf8_landing_page_rows_without_losing_valid_rows(): void
    {
        $client = $this->client();
        $payload = [
            'dimensions' => [['name' => 'landingPagePlusQueryString']],
            'metrics' => [
                ['name' => 'sessions'],
                ['name' => 'activeUsers'],
                ['name' => 'engagedSessions'],
                ['name' => 'engagementRate'],
                ['name' => 'averageSessionDuration'],
            ],
        ];
        $invalidRow = $this->rowFor($payload);
        $invalidRow['dimensionValues'][0]['value'] = "/\xC3(";
        $validRow = $this->rowFor($payload);
        $warnings = [];
        $breakdown = new \ReflectionMethod(GoogleAnalyticsDataClient::class, 'breakdown');
        $arguments = [
            ['current_landing_pages' => ['rows' => [$invalidRow, $validRow]]],
            'current_landing_pages',
            'landing_page',
            ['sessions', 'activeUsers', 'engagedSessions', 'engagementRate', 'averageSessionDuration'],
            &$warnings,
            true,
            null,
            false,
        ];

        /** @var array{available: bool, rows: list<array<string, int|float|string>>} $result */
        $result = $breakdown->invokeArgs($client, $arguments);

        $this->assertTrue($result['available']);
        $this->assertSame(['/contact'], array_column($result['rows'], 'landing_page'));
        $this->assertSame(['ga4_current_landing_pages_invalid_utf8'], $warnings);
        $this->assertJson(json_encode($result, JSON_THROW_ON_ERROR));
    }

    public function test_it_keeps_a_source_usable_and_uses_the_previous_cutoff_when_only_a_previous_landing_page_report_is_available(): void
    {
        Http::preventStrayRequests();
        Http::fake(function (Request $request) {
            $payload = $request->data();
            $dimensions = array_column($payload['dimensions'] ?? [], 'name');
            $isPreviousLandingPage = $dimensions === ['landingPagePlusQueryString']
                && ($payload['dateRanges'][0]['startDate'] ?? null) === '2026-06-15';

            return $isPreviousLandingPage
                ? Http::response(['rows' => [$this->rowFor($payload)]], 200)
                : Http::response([], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame('partial', $report['status']);
        $this->assertTrue($report['previous']['landing_pages']['available']);
        $this->assertSame('2026-07-12', $report['fresh_through']);
    }

    private function client(): GoogleAnalyticsDataClient
    {
        return new GoogleAnalyticsDataClient(
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

    private function assertPersistentGa4RetryCount(int $status): void
    {
        $attempts = 0;

        Http::preventStrayRequests();
        Http::fake(function (Request $request) use (&$attempts, $status) {
            $payload = $request->data();
            $dimensions = array_column($payload['dimensions'] ?? [], 'name');
            $isCurrentTotals = $dimensions === []
                && ($payload['dateRanges'][0]['startDate'] ?? null) === '2026-07-13';

            if ($isCurrentTotals) {
                $attempts++;

                return Http::response([], $status);
            }

            return Http::response(['rows' => [$this->rowFor($payload)]], 200);
        });

        $report = $this->client()->collect($this->periods());

        $this->assertSame(3, $attempts, "GA4 status {$status} should receive the initial request plus two retries.");
        $this->assertSame('partial', $report['status']);
        $this->assertNull($report['current']['totals']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function rowFor(array $payload): array
    {
        $dimensions = array_column($payload['dimensions'] ?? [], 'name');
        $metrics = array_column($payload['metrics'] ?? [], 'name');

        $dimensionValues = array_map(
            fn (string $dimension): array => ['value' => match ($dimension) {
                'sessionDefaultChannelGroup' => 'Organic Search',
                'landingPagePlusQueryString' => '/contact?campaign=private',
                'eventName' => 'consultation_submit_success',
                'customEvent:ui_location' => 'hero_consultation',
                'customEvent:page_type' => 'home',
                'customEvent:locale' => 'ar',
                default => 'unknown',
            }],
            $dimensions,
        );
        $metricValues = array_map(
            fn (string $metric): array => ['value' => (string) match ($metric) {
                'sessions' => 15,
                'activeUsers' => 12,
                'newUsers' => 3,
                'engagedSessions' => 10,
                'engagementRate' => 0.6667,
                'averageSessionDuration' => 42.5,
                'eventCount' => 7,
                'screenPageViews' => 20,
                'keyEvents' => 4,
                default => 0,
            }],
            $metrics,
        );

        return [
            'dimensionValues' => $dimensionValues,
            'metricValues' => $metricValues,
        ];
    }
}
