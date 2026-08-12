<?php

namespace App\Services\WebsitePerformance;

use Carbon\CarbonImmutable;
use Throwable;

class WebsitePerformanceReporter
{
    public function __construct(
        private readonly GoogleAnalyticsDataClient $ga4,
        private readonly SearchConsoleClient $searchConsole,
        private readonly FirstPartyMetricsClient $firstParty,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function report(int $days, CarbonImmutable $endDate, string $timezone): array
    {
        $periods = $this->periods($days, $endDate);
        $sources = [
            'ga4' => $this->collect($this->ga4, $periods, $endDate->toDateString()),
            'search_console' => $this->collect($this->searchConsole, $periods, $endDate->toDateString()),
            'first_party' => $this->collect($this->firstParty, $periods, $endDate->toDateString()),
        ];
        $statuses = array_column($sources, 'status');
        $usableSources = array_filter($statuses, fn (string $status): bool => $status !== 'unavailable');

        return [
            'schema_version' => 1,
            'generated_at' => now($timezone)->toAtomString(),
            'timezone' => $timezone,
            'data_cutoff' => $endDate->toDateString(),
            'status' => $usableSources === []
                ? 'unavailable'
                : (count($usableSources) === count($sources) && ! in_array('partial', $statuses, true) ? 'ok' : 'partial'),
            'periods' => $periods,
            'sources' => $sources,
            'quality' => [
                'low_volume_thresholds' => [
                    'search_console_query_or_page_impressions' => 30,
                    'ga4_page_sessions' => 10,
                    'ga4_relevant_events' => 20,
                    'trend_baseline_observations' => 20,
                    'trend_minimum_absolute_change' => 5,
                    'trend_relative_change' => 0.3,
                ],
                'limitations' => [
                    'ga4' => 'consented_traffic_only',
                    'first_party' => 'aggregate_inquiries_not_joined_to_ga4_or_search_console',
                    'search_console' => 'omitted_rows_are_unavailable_not_zero',
                ],
                'source_statuses' => $statuses,
                'flags' => $this->dataQualityFlags($sources),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    public function exitCode(array $report): int
    {
        return match ($report['status'] ?? 'unavailable') {
            'ok' => 0,
            'partial' => 2,
            default => 1,
        };
    }

    /**
     * @param  GoogleAnalyticsDataClient|SearchConsoleClient|FirstPartyMetricsClient  $source
     * @param  array{current: array{start: string, end: string}, previous: array{start: string, end: string}, context_90d: array{start: string, end: string}}  $periods
     * @return array<string, mixed>
     */
    private function collect(object $source, array $periods, string $cutoff): array
    {
        try {
            return $source->collect($periods);
        } catch (WebsitePerformanceSourceException $exception) {
            return $this->unavailable($cutoff, $exception->reason);
        } catch (Throwable) {
            return $this->unavailable($cutoff, 'source_unavailable');
        }
    }

    /**
     * @return array{status: string, fresh_through: null, warnings: list<string>, current: null, previous: null, context_90d: null, deltas: array<never, never>}
     */
    private function unavailable(string $cutoff, string $reason): array
    {
        return [
            'status' => 'unavailable',
            'fresh_through' => null,
            'warnings' => [$reason],
            'current' => null,
            'previous' => null,
            'context_90d' => null,
            'deltas' => [],
        ];
    }

    /**
     * @return array{current: array{start: string, end: string}, previous: array{start: string, end: string}, context_90d: array{start: string, end: string}}
     */
    private function periods(int $days, CarbonImmutable $endDate): array
    {
        $currentStart = $endDate->subDays($days - 1);
        $previousEnd = $currentStart->subDay();

        return [
            'current' => [
                'start' => $currentStart->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'previous' => [
                'start' => $previousEnd->subDays($days - 1)->toDateString(),
                'end' => $previousEnd->toDateString(),
            ],
            'context_90d' => [
                'start' => $endDate->subDays(89)->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $sources
     * @return list<array{source: string, period: string, metric: string, status: string, observed: int|null, threshold: int}>
     */
    private function dataQualityFlags(array $sources): array
    {
        $flags = [];

        foreach (['current', 'previous'] as $period) {
            $ga4 = $sources['ga4'][$period] ?? null;
            $ga4Sessions = is_array($ga4) ? $ga4['totals']['sessions'] ?? null : null;
            $flags[] = $this->flag('ga4', $period, 'sessions', $ga4Sessions, 10);

            $funnelRows = is_array($ga4)
                && ($ga4['cta_funnel']['available'] ?? false) === true
                && is_array($ga4['cta_funnel']['rows'] ?? null)
                ? $ga4['cta_funnel']['rows']
                : null;
            $funnelEvents = $funnelRows === null
                ? null
                : array_sum(array_map(fn (array $row): int => (int) ($row['eventCount'] ?? 0), $funnelRows));
            $flags[] = $this->flag('ga4', $period, 'relevant_events', $funnelEvents, 20);

            $searchConsole = $sources['search_console'][$period] ?? null;
            $queryRows = is_array($searchConsole)
                && ($searchConsole['queries']['available'] ?? false) === true
                && is_array($searchConsole['queries']['rows'] ?? null)
                ? $searchConsole['queries']['rows']
                : null;
            $pageRows = is_array($searchConsole)
                && ($searchConsole['pages']['available'] ?? false) === true
                && is_array($searchConsole['pages']['rows'] ?? null)
                ? $searchConsole['pages']['rows']
                : null;
            $flags[] = $this->flag('search_console', $period, 'query_impressions', $this->highestMetric($queryRows, 'impressions'), 30);
            $flags[] = $this->flag('search_console', $period, 'page_impressions', $this->highestMetric($pageRows, 'impressions'), 30);

            $inquiries = $sources['first_party'][$period]['inquiries']['total'] ?? null;
            $flags[] = $this->flag('first_party', $period, 'inquiries_for_trend', $inquiries, 20);
        }

        return $flags;
    }

    /**
     * @return array{source: string, period: string, metric: string, status: string, observed: int|null, threshold: int}
     */
    private function flag(string $source, string $period, string $metric, mixed $observed, int $threshold): array
    {
        $value = (is_int($observed) && $observed >= 0) || (is_float($observed) && is_finite($observed) && $observed >= 0)
            ? (int) $observed
            : null;

        return [
            'source' => $source,
            'period' => $period,
            'metric' => $metric,
            'status' => $value === null ? 'unavailable' : ($value < $threshold ? 'insufficient_sample' : 'sufficient'),
            'observed' => $value,
            'threshold' => $threshold,
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $rows
     */
    private function highestMetric(?array $rows, string $metric): ?int
    {
        if ($rows === null) {
            return null;
        }

        $values = array_filter(
            array_map(
                fn (array $row): ?int => is_int($row[$metric] ?? null) && $row[$metric] >= 0 ? $row[$metric] : null,
                $rows,
            ),
            fn (?int $value): bool => $value !== null,
        );

        return $values === [] ? 0 : max($values);
    }
}
