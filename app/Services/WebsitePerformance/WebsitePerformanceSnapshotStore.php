<?php

namespace App\Services\WebsitePerformance;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;
use JsonException;
use Throwable;

class WebsitePerformanceSnapshotStore
{
    private const SnapshotDirectoryPrefix = 'website-performance';

    private const SnapshotSchemaVersion = 1;

    private const SnapshotHistoryLimit = 60;

    private const MaximumSnapshotHistoryLimit = 60;

    private const MaximumDiagnosticCodes = 12;

    /** @var list<string> */
    private const SourceNames = [
        'first_party',
        'ga4',
        'search_console',
    ];

    /** @var list<string> */
    private const ComparisonPeriods = [
        'current',
        'previous',
    ];

    /** @var list<string> */
    private const ReportStatuses = [
        'ok',
        'partial',
        'unavailable',
    ];

    /** @var array<string, string> */
    private const HighIntentEventMetrics = [
        'primary_cta_click' => 'cta_clicks',
        'service_cta_click' => 'cta_clicks',
        'direct_contact_click' => 'cta_clicks',
        'consultation_form_start' => 'form_starts',
        'consultation_submit_success' => 'successful_submissions',
    ];

    /** @var array<string, array<string, int>> */
    private const LowVolumeMetricThresholds = [
        'first_party' => [
            'inquiries_for_trend' => 20,
        ],
        'ga4' => [
            'sessions' => 10,
            'relevant_events' => 20,
        ],
        'search_console' => [
            'query_impressions' => 30,
            'page_impressions' => 30,
        ],
    ];

    /** @var list<string> */
    private const InspectionMetrics = [
        'canonical_mismatches',
        'indexing_issues',
        'robots_issues',
        'page_fetch_issues',
        'verdict_issues',
    ];

    /** @var list<string> */
    private const Ga4WarningReportKeys = [
        'totals',
        'acquisition',
        'landing_pages',
        'events',
        'cta_funnel',
        'locale',
        'page_type',
        'ui_location',
    ];

    /** @var list<string> */
    private const Ga4WarningPeriods = [
        'current',
        'previous',
        'context_90d',
    ];

    /** @var list<string> */
    private const Ga4WarningStates = [
        'invalid_utf8',
        'invalid',
        'unavailable',
    ];

    public function __construct(
        private readonly FilesystemFactory $storage,
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @param  array<string, mixed>  $report
     */
    public function persist(array $report): string
    {
        $this->assertAggregateOnly($report);
        $snapshot = $this->sanitizeReport($report);
        $generatedAt = CarbonImmutable::parse($snapshot['generated_at']);
        $directory = $this->directory();

        $path = sprintf(
            '%s/%s/%s-%s.json',
            $directory,
            $generatedAt->utc()->format('Y/m/d'),
            $generatedAt->utc()->format('Ymd\\THis.u\\Z'),
            (string) Str::uuid(),
        );

        $this->writeSnapshot($path, $snapshot);

        $this->prune($directory, $generatedAt);

        return $path;
    }

    /**
     * Replace only legacy or malformed files inside the validated snapshot directory
     * with a strict aggregate-only snapshot. Safe snapshots are left untouched.
     *
     * @return array{scanned: int, rewritten: int, already_safe: int, unavailable: int}
     */
    public function sanitizeSnapshots(): array
    {
        $result = [
            'scanned' => 0,
            'rewritten' => 0,
            'already_safe' => 0,
            'unavailable' => 0,
        ];

        foreach ($this->snapshotPaths() as $path) {
            $result['scanned']++;

            try {
                $report = json_decode($this->disk()->get($path), true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                $this->writeSnapshot($path, $this->unavailableSnapshot($path));
                $result['rewritten']++;
                $result['unavailable']++;

                continue;
            }

            if (is_array($report) && $this->isSanitizedSnapshot($report)) {
                $result['already_safe']++;

                continue;
            }

            [$snapshot, $isUnavailable] = $this->legacySnapshotProjection($report, $path);
            $this->writeSnapshot($path, $snapshot);
            $result['rewritten']++;

            if ($isUnavailable) {
                $result['unavailable']++;
            }
        }

        return $result;
    }

    /**
     * Read a strictly whitelisted, aggregate-only projection of saved reports.
     *
     * @return array{
     *     state: 'ready'|'empty',
     *     latest: array<string, mixed>|null,
     *     history: list<array<string, mixed>>
     * }
     */
    public function summaries(int $historyLimit = self::SnapshotHistoryLimit): array
    {
        $limit = min(max($historyLimit, 1), self::MaximumSnapshotHistoryLimit);
        $history = [];

        foreach ($this->snapshotPaths() as $path) {
            $summary = $this->snapshotSummary($path);

            if ($summary === null) {
                continue;
            }

            $history[] = $summary;
        }

        usort(
            $history,
            fn (array $first, array $second): int => strcmp($second['generated_at'], $first['generated_at']),
        );
        $history = array_slice($history, 0, $limit);

        return [
            'state' => $history === [] ? 'empty' : 'ready',
            'latest' => $history[0] ?? null,
            'history' => $history,
        ];
    }

    /**
     * @return list<string>
     */
    private function snapshotPaths(): array
    {
        try {
            $disk = $this->disk();
            $paths = array_values(array_filter(
                $disk->allFiles($this->directory()),
                fn (mixed $path): bool => is_string($path) && str_ends_with($path, '.json'),
            ));
        } catch (WebsitePerformanceSourceException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new WebsitePerformanceSourceException('snapshot_storage_unavailable');
        }

        usort($paths, function (string $first, string $second) use ($disk): int {
            $firstModified = $this->lastModified($disk, $first);
            $secondModified = $this->lastModified($disk, $second);

            return $secondModified <=> $firstModified ?: strcmp($second, $first);
        });

        return $paths;
    }

    private function lastModified(FilesystemAdapter $disk, string $path): int
    {
        try {
            return $disk->lastModified($path);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function snapshotSummary(string $path): ?array
    {
        try {
            $report = json_decode($this->disk()->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($report)) {
            return null;
        }

        if (! $this->isSanitizedSnapshot($report)) {
            return null;
        }

        return $this->reportSummary($report);
    }

    /**
     * @return array{0: array<string, mixed>, 1: bool}
     */
    private function legacySnapshotProjection(mixed $report, string $path): array
    {
        if (! is_array($report)
            || ! is_array($report['sources'] ?? null)
            || $this->dateTimeValue($report['generated_at'] ?? null) === null) {
            return [$this->unavailableSnapshot($path, is_array($report) ? $report : null), true];
        }

        try {
            $this->assertAggregateOnly($report);

            return [$this->sanitizeReport($report), false];
        } catch (WebsitePerformanceSourceException) {
            return [$this->unavailableSnapshot($path, $report), true];
        }
    }

    /**
     * @param  array<string, mixed>|null  $report
     * @return array<string, mixed>
     */
    private function unavailableSnapshot(string $path, ?array $report = null): array
    {
        $generatedAt = $report === null ? null : $this->dateTimeValue($report['generated_at'] ?? null);
        $periods = $report !== null && is_array($report['periods'] ?? null) ? $report['periods'] : [];

        return [
            'snapshot_schema_version' => self::SnapshotSchemaVersion,
            'schema_version' => $report === null ? 1 : $this->schemaVersion($report['schema_version'] ?? null),
            'generated_at' => $generatedAt ?? $this->snapshotTimestamp($path),
            'timezone' => $report === null ? 'Asia/Riyadh' : ($this->timezoneValue($report['timezone'] ?? null) ?? 'Asia/Riyadh'),
            'data_cutoff' => $report === null ? null : $this->dateValue($report['data_cutoff'] ?? null),
            'status' => 'unavailable',
            'periods' => [
                'current' => $this->periodSummary($periods['current'] ?? null),
                'previous' => $this->periodSummary($periods['previous'] ?? null),
                'context_90d' => $this->periodSummary($periods['context_90d'] ?? null),
            ],
            'sources' => [
                'first_party' => $this->unavailableSourceSummary('first_party'),
                'ga4' => $this->unavailableSourceSummary('ga4'),
                'search_console' => $this->unavailableSourceSummary('search_console'),
            ],
            'quality' => [
                'flags' => $this->unavailableInspectionQualityFlags(),
            ],
            'meta' => ['privacy' => 'aggregate_only'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function unavailableSourceSummary(string $sourceName): array
    {
        return [
            'status' => 'unavailable',
            'fresh_through' => null,
            'warning_count' => 0,
            'warning_codes' => [],
            'current' => $this->normalizeSanitizedWindow(null, $sourceName),
            'previous' => $this->normalizeSanitizedWindow(null, $sourceName),
            'context_90d' => $this->normalizeSanitizedWindow(null, $sourceName),
        ];
    }

    private function snapshotTimestamp(string $path): string
    {
        return CarbonImmutable::createFromTimestampUTC($this->lastModified($this->disk(), $path))->toAtomString();
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function writeSnapshot(string $path, array $snapshot): void
    {
        try {
            $json = json_encode(
                $snapshot,
                JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            throw new WebsitePerformanceSourceException('snapshot_serialization_unavailable');
        }

        try {
            if (! $this->disk()->put($path, $json, 'private')) {
                throw new WebsitePerformanceSourceException('snapshot_storage_unavailable');
            }
        } catch (WebsitePerformanceSourceException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new WebsitePerformanceSourceException('snapshot_storage_unavailable');
        }
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function reportSummary(array $report): array
    {
        return [
            'generated_at' => $report['generated_at'],
            'timezone' => $report['timezone'],
            'data_cutoff' => $report['data_cutoff'],
            'status' => $report['status'],
            'periods' => $report['periods'],
            'sources' => $report['sources'],
            'quality' => $report['quality'],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function sanitizeReport(array $report): array
    {
        $timezone = $this->timezoneValue($report['timezone'] ?? null) ?? 'Asia/Riyadh';
        $generatedAt = $this->dateTimeValue($report['generated_at'] ?? null);

        if ($generatedAt === null) {
            throw new WebsitePerformanceSourceException('snapshot_serialization_unavailable');
        }

        $sources = is_array($report['sources'] ?? null) ? $report['sources'] : [];
        $periods = is_array($report['periods'] ?? null) ? $report['periods'] : [];
        $quality = is_array($report['quality'] ?? null) ? $report['quality'] : [];
        $sourceSummaries = [
            'first_party' => $this->sourceSummary($sources['first_party'] ?? null, 'first_party'),
            'ga4' => $this->sourceSummary($sources['ga4'] ?? null, 'ga4'),
            'search_console' => $this->sourceSummary($sources['search_console'] ?? null, 'search_console'),
        ];

        return [
            'snapshot_schema_version' => self::SnapshotSchemaVersion,
            'schema_version' => $this->schemaVersion($report['schema_version'] ?? null),
            'generated_at' => $generatedAt,
            'timezone' => $timezone,
            'data_cutoff' => $this->dateValue($report['data_cutoff'] ?? null),
            'status' => $this->reportStatus($sourceSummaries),
            'periods' => [
                'current' => $this->periodSummary($periods['current'] ?? null),
                'previous' => $this->periodSummary($periods['previous'] ?? null),
                'context_90d' => $this->periodSummary($periods['context_90d'] ?? null),
            ],
            'sources' => $sourceSummaries,
            'quality' => [
                'flags' => [
                    ...$this->lowVolumeQualityFlags($quality['flags'] ?? null),
                    ...$this->inspectionQualityFlags($sources['search_console'] ?? null),
                ],
            ],
            'meta' => ['privacy' => 'aggregate_only'],
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function isSanitizedSnapshot(array $report): bool
    {
        if (($report['snapshot_schema_version'] ?? null) !== self::SnapshotSchemaVersion) {
            return false;
        }

        try {
            $this->assertAggregateOnly($report);

            return $report === $this->normalizeSanitizedSnapshot($report);
        } catch (WebsitePerformanceSourceException) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function normalizeSanitizedSnapshot(array $report): array
    {
        $generatedAt = $this->dateTimeValue($report['generated_at'] ?? null);

        if ($generatedAt === null) {
            throw new WebsitePerformanceSourceException('snapshot_serialization_unavailable');
        }

        $sources = is_array($report['sources'] ?? null) ? $report['sources'] : [];
        $periods = is_array($report['periods'] ?? null) ? $report['periods'] : [];
        $quality = is_array($report['quality'] ?? null) ? $report['quality'] : [];
        $sourceSummaries = [
            'first_party' => $this->normalizeSanitizedSource($sources['first_party'] ?? null, 'first_party'),
            'ga4' => $this->normalizeSanitizedSource($sources['ga4'] ?? null, 'ga4'),
            'search_console' => $this->normalizeSanitizedSource($sources['search_console'] ?? null, 'search_console'),
        ];

        return [
            'snapshot_schema_version' => self::SnapshotSchemaVersion,
            'schema_version' => $this->schemaVersion($report['schema_version'] ?? null),
            'generated_at' => $generatedAt,
            'timezone' => $this->timezoneValue($report['timezone'] ?? null) ?? 'Asia/Riyadh',
            'data_cutoff' => $this->dateValue($report['data_cutoff'] ?? null),
            'status' => $this->reportStatus($sourceSummaries),
            'periods' => [
                'current' => $this->periodSummary($periods['current'] ?? null),
                'previous' => $this->periodSummary($periods['previous'] ?? null),
                'context_90d' => $this->periodSummary($periods['context_90d'] ?? null),
            ],
            'sources' => $sourceSummaries,
            'quality' => [
                'flags' => [
                    ...$this->normalizeStoredLowVolumeQualityFlags($quality['flags'] ?? null),
                    ...$this->normalizeStoredInspectionQualityFlags($quality['flags'] ?? null),
                ],
            ],
            'meta' => ['privacy' => 'aggregate_only'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSanitizedSource(mixed $source, string $sourceName): array
    {
        $source = is_array($source) ? $source : [];
        $warningCodes = $this->storedWarningCodes($source['warning_codes'] ?? null, $sourceName);

        return [
            'status' => $this->statusValue($source['status'] ?? null) ?? 'unavailable',
            'fresh_through' => $this->dateValue($source['fresh_through'] ?? null),
            'warning_count' => count($warningCodes),
            'warning_codes' => $warningCodes,
            'current' => $this->normalizeSanitizedWindow($source['current'] ?? null, $sourceName),
            'previous' => $this->normalizeSanitizedWindow($source['previous'] ?? null, $sourceName),
            'context_90d' => $this->normalizeSanitizedWindow($source['context_90d'] ?? null, $sourceName),
        ];
    }

    /**
     * @return array<string, int|float|null>
     */
    private function normalizeSanitizedWindow(mixed $window, string $sourceName): array
    {
        $window = is_array($window) ? $window : [];

        return match ($sourceName) {
            'first_party' => [
                'total' => $this->countValue($window['total'] ?? null),
                'responded' => $this->countValue($window['responded'] ?? null),
                'response_rate' => $this->rateValue($window['response_rate'] ?? null),
            ],
            'ga4' => [
                'sessions' => $this->countValue($window['sessions'] ?? null),
                'engaged_sessions' => $this->countValue($window['engaged_sessions'] ?? null),
                'event_count' => $this->countValue($window['event_count'] ?? null),
                'cta_clicks' => $this->countValue($window['cta_clicks'] ?? null),
                'form_starts' => $this->countValue($window['form_starts'] ?? null),
                'successful_submissions' => $this->countValue($window['successful_submissions'] ?? null),
            ],
            'search_console' => [
                'clicks' => $this->countValue($window['clicks'] ?? null),
                'impressions' => $this->countValue($window['impressions'] ?? null),
            ],
            default => [],
        };
    }

    /**
     * @return array{start: string, end: string}|null
     */
    private function periodSummary(mixed $period): ?array
    {
        if (! is_array($period)) {
            return null;
        }

        $start = $this->dateValue($period['start'] ?? null);
        $end = $this->dateValue($period['end'] ?? null);

        return $start !== null && $end !== null && $start <= $end
            ? ['start' => $start, 'end' => $end]
            : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function sourceSummary(mixed $source, string $sourceName): array
    {
        $source = is_array($source) ? $source : [];
        $warningCodes = $this->warningCodes($source['warnings'] ?? null, $sourceName);

        return [
            'status' => $this->statusValue($source['status'] ?? null) ?? 'unavailable',
            'fresh_through' => $this->dateValue($source['fresh_through'] ?? null),
            'warning_count' => count($warningCodes),
            'warning_codes' => $warningCodes,
            'current' => $this->windowSummary($source['current'] ?? null, $sourceName),
            'previous' => $this->windowSummary($source['previous'] ?? null, $sourceName),
            'context_90d' => $this->windowSummary($source['context_90d'] ?? null, $sourceName),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $sources
     */
    private function reportStatus(array $sources): string
    {
        $statuses = array_map(
            fn (string $sourceName): string => $this->statusValue($sources[$sourceName]['status'] ?? null) ?? 'unavailable',
            self::SourceNames,
        );
        $usableCount = count(array_filter($statuses, fn (string $status): bool => $status !== 'unavailable'));

        if ($usableCount === 0) {
            return 'unavailable';
        }

        return $usableCount === count(self::SourceNames) && ! in_array('partial', $statuses, true)
            ? 'ok'
            : 'partial';
    }

    /**
     * @return array<string, int|float|null>
     */
    private function windowSummary(mixed $window, string $sourceName): array
    {
        $window = is_array($window) ? $window : [];

        return match ($sourceName) {
            'first_party' => $this->firstPartyWindowSummary($window),
            'ga4' => $this->ga4WindowSummary($window),
            'search_console' => $this->searchConsoleWindowSummary($window),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $window
     * @return array{total: int|null, responded: int|null, response_rate: float|null}
     */
    private function firstPartyWindowSummary(array $window): array
    {
        $inquiries = is_array($window['inquiries'] ?? null) ? $window['inquiries'] : [];

        return [
            'total' => $this->countValue($inquiries['total'] ?? null),
            'responded' => $this->countValue($inquiries['responded'] ?? null),
            'response_rate' => $this->rateValue($inquiries['response_rate'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $window
     * @return array{sessions: int|null, engaged_sessions: int|null, event_count: int|null, cta_clicks: int|null, form_starts: int|null, successful_submissions: int|null}
     */
    private function ga4WindowSummary(array $window): array
    {
        $totals = is_array($window['totals'] ?? null) ? $window['totals'] : [];
        $events = $this->highIntentEventMetrics($window['events'] ?? null);

        return [
            'sessions' => $this->countValue($totals['sessions'] ?? null),
            'engaged_sessions' => $this->countValue($totals['engagedSessions'] ?? null),
            'event_count' => $this->countValue($totals['eventCount'] ?? null),
            ...$events,
        ];
    }

    /**
     * @param  array<string, mixed>  $window
     * @return array{clicks: int|null, impressions: int|null}
     */
    private function searchConsoleWindowSummary(array $window): array
    {
        $totals = is_array($window['totals'] ?? null) ? $window['totals'] : [];

        return [
            'clicks' => $this->countValue($totals['clicks'] ?? null),
            'impressions' => $this->countValue($totals['impressions'] ?? null),
        ];
    }

    /**
     * @return array{cta_clicks: int|null, form_starts: int|null, successful_submissions: int|null}
     */
    private function highIntentEventMetrics(mixed $events): array
    {
        $unavailable = [
            'cta_clicks' => null,
            'form_starts' => null,
            'successful_submissions' => null,
        ];

        if (! is_array($events) || ($events['available'] ?? null) !== true || ! is_array($events['rows'] ?? null)) {
            return $unavailable;
        }

        $metrics = [
            'cta_clicks' => 0,
            'form_starts' => 0,
            'successful_submissions' => 0,
        ];

        foreach ($events['rows'] as $row) {
            if (! is_array($row)) {
                return $unavailable;
            }

            $eventName = $row['event_name'] ?? null;
            $metric = is_string($eventName) ? self::HighIntentEventMetrics[$eventName] ?? null : null;

            if ($metric === null) {
                continue;
            }

            $count = $this->countValue($row['eventCount'] ?? null);

            if ($count === null) {
                return $unavailable;
            }

            $metrics[$metric] += $count;
        }

        return $metrics;
    }

    /**
     * @return list<array{source: string, period: string, metric: string, status: string, observed: int|null, threshold: int}>
     */
    private function lowVolumeQualityFlags(mixed $flags): array
    {
        if (! is_array($flags)) {
            return [];
        }

        $uniqueFlags = [];

        foreach ($flags as $flag) {
            $normalized = $this->lowVolumeQualityFlag($flag);

            if ($normalized === null) {
                continue;
            }

            $uniqueFlags[implode(':', [$normalized['source'], $normalized['period'], $normalized['metric']])] = $normalized;
        }

        ksort($uniqueFlags);

        return array_values($uniqueFlags);
    }

    /**
     * @return array{source: string, period: string, metric: string, status: string, observed: int|null, threshold: int}|null
     */
    private function lowVolumeQualityFlag(mixed $flag): ?array
    {
        if (! is_array($flag)) {
            return null;
        }

        $source = $flag['source'] ?? null;
        $period = $flag['period'] ?? null;
        $metric = $flag['metric'] ?? null;

        if (! is_string($source)
            || ! is_string($period)
            || ! is_string($metric)
            || ! in_array($period, self::ComparisonPeriods, true)
            || ! isset(self::LowVolumeMetricThresholds[$source][$metric])) {
            return null;
        }

        return $this->lowVolumeQualityFlagFor(
            $source,
            $period,
            $metric,
            $this->countValue($flag['observed'] ?? null),
        );
    }

    /**
     * @return array{source: string, period: string, metric: string, status: string, observed: int|null, threshold: int}
     */
    private function lowVolumeQualityFlagFor(string $source, string $period, string $metric, ?int $observed): array
    {
        $threshold = self::LowVolumeMetricThresholds[$source][$metric];

        return [
            'source' => $source,
            'period' => $period,
            'metric' => $metric,
            'status' => $observed === null ? 'unavailable' : ($observed < $threshold ? 'insufficient_sample' : 'sufficient'),
            'observed' => $observed,
            'threshold' => $threshold,
        ];
    }

    /**
     * @return list<array{source: string, period: string, metric: string, status: string, observed: int|null, threshold: int}>
     */
    private function inspectionQualityFlags(mixed $searchConsoleSource): array
    {
        $source = is_array($searchConsoleSource) ? $searchConsoleSource : [];
        $inspection = is_array($source['url_inspection'] ?? null) ? $source['url_inspection'] : null;

        if ($inspection === null
            || ($inspection['available'] ?? null) !== true
            || ! is_array($inspection['results'] ?? null)) {
            return $this->unavailableInspectionQualityFlags();
        }

        $issueCounts = array_fill_keys(self::InspectionMetrics, 0);

        foreach ($inspection['results'] as $result) {
            if (! is_array($result)) {
                return $this->unavailableInspectionQualityFlags();
            }

            $canonicalMismatch = $this->canonicalMismatch($result);
            $indexingIssue = $this->inspectionIssue($result['indexing_state'] ?? null, 'INDEXING_ALLOWED');
            $robotsIssue = $this->inspectionIssue($result['robots_txt_state'] ?? null, 'ALLOWED');
            $pageFetchIssue = $this->inspectionIssue($result['page_fetch_state'] ?? null, 'SUCCESSFUL');
            $verdictIssue = $this->inspectionIssue($result['verdict'] ?? null, 'PASS');

            if ($canonicalMismatch === null
                || $indexingIssue === null
                || $robotsIssue === null
                || $pageFetchIssue === null
                || $verdictIssue === null) {
                return $this->unavailableInspectionQualityFlags();
            }

            $issueCounts['canonical_mismatches'] += $canonicalMismatch;
            $issueCounts['indexing_issues'] += $indexingIssue;
            $issueCounts['robots_issues'] += $robotsIssue;
            $issueCounts['page_fetch_issues'] += $pageFetchIssue;
            $issueCounts['verdict_issues'] += $verdictIssue;
        }

        return array_map(
            fn (string $metric): array => $this->inspectionQualityFlag($metric, $issueCounts[$metric]),
            self::InspectionMetrics,
        );
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function canonicalMismatch(array $result): ?int
    {
        $userCanonical = $result['user_canonical'] ?? null;
        $googleCanonical = $result['google_canonical'] ?? null;

        if (! is_string($userCanonical)
            || ! is_string($googleCanonical)
            || mb_strlen($userCanonical) > 2048
            || mb_strlen($googleCanonical) > 2048) {
            return null;
        }

        return $userCanonical === $googleCanonical ? 0 : 1;
    }

    private function inspectionIssue(mixed $value, string $passingValue): ?int
    {
        if (! is_string($value) || mb_strlen($value) > 100) {
            return null;
        }

        return $value === $passingValue ? 0 : 1;
    }

    /**
     * @return list<array{source: string, period: string, metric: string, status: string, observed: int|null, threshold: int}>
     */
    private function unavailableInspectionQualityFlags(): array
    {
        return array_map(
            fn (string $metric): array => $this->inspectionQualityFlag($metric, null),
            self::InspectionMetrics,
        );
    }

    /**
     * @return array{source: string, period: string, metric: string, status: string, observed: int|null, threshold: int}
     */
    private function inspectionQualityFlag(string $metric, ?int $observed): array
    {
        return [
            'source' => 'search_console',
            'period' => 'current',
            'metric' => $metric,
            'status' => $observed === null ? 'unavailable' : ($observed === 0 ? 'clear' : 'issues_detected'),
            'observed' => $observed,
            'threshold' => 0,
        ];
    }

    /**
     * @return list<array{source: string, period: string, metric: string, status: string, observed: int|null, threshold: int}>
     */
    private function normalizeStoredLowVolumeQualityFlags(mixed $flags): array
    {
        return $this->lowVolumeQualityFlags($flags);
    }

    /**
     * @return list<array{source: string, period: string, metric: string, status: string, observed: int|null, threshold: int}>
     */
    private function normalizeStoredInspectionQualityFlags(mixed $flags): array
    {
        $flags = is_array($flags) ? $flags : [];
        $normalizedByMetric = [];

        foreach ($flags as $flag) {
            if (! is_array($flag)
                || ($flag['source'] ?? null) !== 'search_console'
                || ($flag['period'] ?? null) !== 'current'
                || ! is_string($flag['metric'] ?? null)
                || ! in_array($flag['metric'], self::InspectionMetrics, true)) {
                continue;
            }

            $normalizedByMetric[$flag['metric']] = $this->inspectionQualityFlag(
                $flag['metric'],
                $this->countValue($flag['observed'] ?? null),
            );
        }

        return array_map(
            fn (string $metric): array => $normalizedByMetric[$metric] ?? $this->inspectionQualityFlag($metric, null),
            self::InspectionMetrics,
        );
    }

    /**
     * @return list<string>
     */
    private function warningCodes(mixed $warnings, string $sourceName): array
    {
        if (! is_array($warnings)) {
            return [];
        }

        $codes = [];

        foreach ($warnings as $warning) {
            if (is_string($warning) && $this->isAllowedWarningCode($warning, $sourceName)) {
                $codes[] = $warning;
            }
        }

        $codes = array_values(array_unique($codes));
        sort($codes);

        return array_slice($codes, 0, self::MaximumDiagnosticCodes);
    }

    /**
     * @return list<string>
     */
    private function storedWarningCodes(mixed $codes, string $sourceName): array
    {
        return $this->warningCodes($codes, $sourceName);
    }

    private function isAllowedWarningCode(string $code, string $sourceName): bool
    {
        if (! in_array($sourceName, self::SourceNames, true)) {
            return false;
        }

        if ($code === 'source_unavailable') {
            return true;
        }

        return match ($sourceName) {
            'first_party' => in_array($code, [
                'first_party_configuration_unavailable',
                'first_party_authentication_unavailable',
                'first_party_metrics_unavailable',
                'first_party_contract_unavailable',
                'first_party_current_unavailable',
                'first_party_previous_unavailable',
                'first_party_context_90d_unavailable',
            ], true),
            'ga4' => in_array($code, [
                'google_credentials_unavailable',
                'google_authentication_unavailable',
                'ga4_configuration_unavailable',
                'ga4_report_unavailable',
                'ga4_report_pagination_unavailable',
            ], true) || $this->isAllowedGa4WarningCode($code),
            'search_console' => in_array($code, [
                'google_credentials_unavailable',
                'google_authentication_unavailable',
                'search_console_configuration_unavailable',
                'search_console_totals_invalid',
                'search_console_query_invalid',
                'search_console_page_invalid',
                'search_console_country_invalid',
                'search_console_device_invalid',
                'search_console_request_unavailable',
                'search_console_rows_unavailable',
                'url_inspection_sitemap_unavailable',
                'url_inspection_request_unavailable',
                'url_inspection_response_unavailable',
            ], true),
        };
    }

    private function isAllowedGa4WarningCode(string $code): bool
    {
        foreach (self::Ga4WarningPeriods as $period) {
            foreach (self::Ga4WarningReportKeys as $reportKey) {
                foreach (self::Ga4WarningStates as $state) {
                    if ($code === "ga4_{$period}_{$reportKey}_{$state}") {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function schemaVersion(mixed $value): int
    {
        $version = $this->countValue($value);

        return $version !== null && $version > 0 && $version <= 100 ? $version : 1;
    }

    private function statusValue(mixed $value): ?string
    {
        return is_string($value) && in_array($value, self::ReportStatuses, true) ? $value : null;
    }

    private function timezoneValue(mixed $value): ?string
    {
        return is_string($value) && in_array($value, timezone_identifiers_list(), true) ? $value : null;
    }

    private function dateValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, 'UTC');
        } catch (Throwable) {
            return null;
        }

        return $date?->toDateString() === $value ? $value : null;
    }

    private function dateTimeValue(mixed $value): ?string
    {
        if (! is_string($value) || mb_strlen($value) > 100) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toAtomString();
        } catch (Throwable) {
            return null;
        }
    }

    private function countValue(mixed $value): ?int
    {
        if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
            return null;
        }

        $count = (float) $value;

        if ($count < 0 || floor($count) !== $count || $count > PHP_INT_MAX) {
            return null;
        }

        return (int) $count;
    }

    private function rateValue(mixed $value): ?float
    {
        if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
            return null;
        }

        $rate = (float) $value;

        return $rate >= 0 && $rate <= 1 ? $rate : null;
    }

    private function prune(string $directory, CarbonImmutable $generatedAt): void
    {
        $disk = $this->disk();
        $before = $generatedAt->subMonthsNoOverflow(13)->getTimestamp();

        foreach ($disk->allFiles($directory) as $path) {
            if ($disk->lastModified($path) < $before) {
                $disk->delete($path);
            }
        }
    }

    private function disk(): FilesystemAdapter
    {
        $diskName = (string) $this->config->get('services.website_performance.snapshot_disk', 'local');
        $root = rtrim((string) $this->config->get('filesystems.disks.local.root'), DIRECTORY_SEPARATOR);
        $privateRoot = rtrim(storage_path('app/private'), DIRECTORY_SEPARATOR);

        if ($diskName !== 'local'
            || $root === ''
            || ($root !== $privateRoot && ! str_starts_with($root, $privateRoot.DIRECTORY_SEPARATOR))) {
            throw new WebsitePerformanceSourceException('snapshot_configuration_unavailable');
        }

        return $this->storage->disk('local');
    }

    private function directory(): string
    {
        $directory = trim((string) $this->config->get(
            'services.website_performance.snapshot_directory',
            self::SnapshotDirectoryPrefix,
        ), '/');

        if (preg_match(
            '/\Awebsite-performance(?:\/[A-Za-z0-9][A-Za-z0-9_-]{0,63})*\z/',
            $directory,
        ) !== 1) {
            throw new WebsitePerformanceSourceException('snapshot_configuration_unavailable');
        }

        return $directory;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function assertAggregateOnly(array $report): void
    {
        foreach ($report as $key => $value) {
            $key = strtolower((string) $key);

            if (in_array($key, [
                'name',
                'email',
                'phone',
                'company',
                'role',
                'challenge',
                'timing',
                'message',
                'notes',
                'id',
                'reference',
                'hash',
                'public_reference',
                'submission_hash',
                'submission_id',
                'record_id',
                'inquiry_id',
            ], true)
                || str_ends_with($key, '_id')
                || str_ends_with($key, '_hash')
                || str_ends_with($key, '_reference')
                || str_ends_with($key, '_identifier')) {
                throw new WebsitePerformanceSourceException('snapshot_privacy_unavailable');
            }

            if (is_array($value)) {
                $this->assertAggregateOnly($value);
            }
        }
    }
}
