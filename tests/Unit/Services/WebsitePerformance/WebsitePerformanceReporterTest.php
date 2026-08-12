<?php

namespace Tests\Unit\Services\WebsitePerformance;

use App\Services\WebsitePerformance\FirstPartyMetricsClient;
use App\Services\WebsitePerformance\GoogleAnalyticsDataClient;
use App\Services\WebsitePerformance\SearchConsoleClient;
use App\Services\WebsitePerformance\WebsitePerformanceReporter;
use Carbon\CarbonImmutable;
use Mockery;
use Tests\TestCase;

class WebsitePerformanceReporterTest extends TestCase
{
    public function test_it_builds_aligned_comparison_windows_and_evaluates_data_quality_flags(): void
    {
        $ga4 = Mockery::mock(GoogleAnalyticsDataClient::class);
        $searchConsole = Mockery::mock(SearchConsoleClient::class);
        $firstParty = Mockery::mock(FirstPartyMetricsClient::class);
        $ga4->shouldReceive('collect')->once()->andReturn($this->ga4Source());
        $searchConsole->shouldReceive('collect')->once()->andReturn($this->searchConsoleSource());
        $firstParty->shouldReceive('collect')->once()->andReturn($this->firstPartySource());

        $report = (new WebsitePerformanceReporter($ga4, $searchConsole, $firstParty))->report(
            28,
            CarbonImmutable::parse('2026-08-09', 'Asia/Riyadh'),
            'Asia/Riyadh',
        );

        $this->assertSame([
            'current' => ['start' => '2026-07-13', 'end' => '2026-08-09'],
            'previous' => ['start' => '2026-06-15', 'end' => '2026-07-12'],
            'context_90d' => ['start' => '2026-05-12', 'end' => '2026-08-09'],
        ], $report['periods']);
        $this->assertSame('partial', $report['status']);

        $flags = $report['quality']['flags'];
        $this->assertSame('unavailable', $this->flag($flags, 'ga4', 'current', 'relevant_events')['status']);
        $this->assertSame('unavailable', $this->flag($flags, 'search_console', 'current', 'query_impressions')['status']);
        $this->assertSame('sufficient', $this->flag($flags, 'search_console', 'current', 'page_impressions')['status']);
        $this->assertSame('insufficient_sample', $this->flag($flags, 'first_party', 'current', 'inquiries_for_trend')['status']);
    }

    public function test_it_uses_the_documented_partial_and_unavailable_exit_codes(): void
    {
        $reporter = new WebsitePerformanceReporter(
            Mockery::mock(GoogleAnalyticsDataClient::class),
            Mockery::mock(SearchConsoleClient::class),
            Mockery::mock(FirstPartyMetricsClient::class),
        );

        $this->assertSame(0, $reporter->exitCode(['status' => 'ok']));
        $this->assertSame(2, $reporter->exitCode(['status' => 'partial']));
        $this->assertSame(1, $reporter->exitCode(['status' => 'unavailable']));
    }

    /**
     * @return array<string, mixed>
     */
    private function ga4Source(): array
    {
        return [
            'status' => 'partial',
            'fresh_through' => '2026-08-09',
            'warnings' => ['ga4_current_cta_funnel_unavailable'],
            'current' => [
                'totals' => ['sessions' => 14],
                'cta_funnel' => ['available' => false, 'rows' => []],
            ],
            'previous' => [
                'totals' => ['sessions' => 13],
                'cta_funnel' => ['available' => true, 'rows' => [['eventCount' => 24]]],
            ],
            'context_90d' => ['totals' => ['sessions' => 50]],
            'deltas' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchConsoleSource(): array
    {
        return [
            'status' => 'ok',
            'fresh_through' => '2026-08-09',
            'warnings' => [],
            'current' => [
                'totals' => ['clicks' => 12],
                'queries' => ['available' => false, 'rows' => []],
                'pages' => ['available' => true, 'rows' => [['impressions' => 35]]],
            ],
            'previous' => [
                'totals' => ['clicks' => 20],
                'queries' => ['available' => true, 'rows' => [['impressions' => 32]]],
                'pages' => ['available' => true, 'rows' => [['impressions' => 32]]],
            ],
            'context_90d' => ['totals' => ['clicks' => 52]],
            'deltas' => [],
            'url_inspection' => ['available' => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function firstPartySource(): array
    {
        return [
            'status' => 'ok',
            'fresh_through' => '2026-08-09',
            'warnings' => [],
            'current' => ['inquiries' => ['total' => 3]],
            'previous' => ['inquiries' => ['total' => 5]],
            'context_90d' => ['inquiries' => ['total' => 12]],
            'deltas' => [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $flags
     * @return array<string, mixed>
     */
    private function flag(array $flags, string $source, string $period, string $metric): array
    {
        $flag = collect($flags)->first(
            fn (array $candidate): bool => $candidate['source'] === $source
                && $candidate['period'] === $period
                && $candidate['metric'] === $metric,
        );

        $this->assertIsArray($flag);

        return $flag;
    }
}
