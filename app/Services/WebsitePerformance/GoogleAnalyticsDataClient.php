<?php

namespace App\Services\WebsitePerformance;

use App\Contracts\WebsitePerformance\GoogleAccessTokenProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Throwable;

class GoogleAnalyticsDataClient extends WebsitePerformanceHttpClient
{
    private const REPORT_PAGE_SIZE = 1000;

    /** @var list<string> */
    private const TOTAL_METRICS = [
        'sessions',
        'activeUsers',
        'newUsers',
        'engagedSessions',
        'engagementRate',
        'averageSessionDuration',
        'eventCount',
        'screenPageViews',
    ];

    /** @var list<string> */
    private const COUNTER_METRICS = [
        'sessions',
        'activeUsers',
        'newUsers',
        'engagedSessions',
        'eventCount',
        'screenPageViews',
        'keyEvents',
    ];

    /** @var list<string> */
    private const EVENT_NAMES = [
        'primary_cta_click',
        'service_cta_click',
        'article_related_click',
        'direct_contact_click',
        'consultation_form_start',
        'consultation_submit_success',
        'consultation_submit_error',
        'language_switch',
        'audio_start',
        'audio_complete',
    ];

    /** @var list<string> */
    private const CTA_FUNNEL_EVENTS = [
        'primary_cta_click',
        'service_cta_click',
        'direct_contact_click',
        'consultation_form_start',
        'consultation_submit_success',
        'consultation_submit_error',
    ];

    public function __construct(
        Factory $http,
        Repository $config,
        private readonly GoogleAccessTokenProvider $tokens,
    ) {
        parent::__construct($http, $config);
    }

    /**
     * @param  array{current: array{start: string, end: string}, previous: array{start: string, end: string}, context_90d: array{start: string, end: string}}  $periods
     * @return array<string, mixed>
     */
    public function collect(array $periods): array
    {
        $propertyId = trim((string) $this->config->get('services.website_performance.ga4_property_id'));

        if (preg_match('/\A\d+\z/', $propertyId) !== 1) {
            throw new WebsitePerformanceSourceException('ga4_configuration_unavailable');
        }

        $accessToken = $this->tokens->accessToken();
        $responses = $this->requestReports($propertyId, $accessToken, $periods);
        $warnings = [];

        $current = $this->window($responses, 'current', $warnings);
        $previous = $this->window($responses, 'previous', $warnings);
        $context = [
            'totals' => $this->totals($responses, 'context_90d_totals', $warnings),
        ];

        $hasData = $this->windowHasData($current)
            || $this->windowHasData($previous)
            || $context['totals'] !== null;
        $warnings = array_values(array_unique($warnings));
        sort($warnings);

        return [
            'status' => $hasData ? ($warnings === [] ? 'ok' : 'partial') : 'unavailable',
            'fresh_through' => $this->freshThrough($current, $previous, $context, $periods),
            'warnings' => $warnings,
            'current' => $current,
            'previous' => $previous,
            'context_90d' => $context,
            'deltas' => $this->deltas($current['totals'], $previous['totals']),
        ];
    }

    /**
     * @param  array{current: array{start: string, end: string}, previous: array{start: string, end: string}, context_90d: array{start: string, end: string}}  $periods
     * @return array<string, Response|Throwable|array<string, mixed>>
     */
    private function requestReports(string $propertyId, string $accessToken, array $periods): array
    {
        $url = 'https://analyticsdata.googleapis.com/v1beta/properties/'.rawurlencode($propertyId).':runReport';
        $reports = $this->reportDefinitions($periods);

        $responses = $this->http->pool(function (Pool $pool) use ($reports, $accessToken, $url): void {
            foreach ($reports as $key => $report) {
                $pool->as($key)
                    ->acceptJson()
                    ->withToken($accessToken)
                    ->connectTimeout($this->connectTimeout())
                    ->timeout($this->timeout())
                    ->retry(
                        [100, 500],
                        0,
                        fn (Throwable $exception): bool => $this->shouldRetry($exception),
                        false,
                    )
                    ->post($url, $report);
            }
        }, 5);

        foreach ($reports as $key => $report) {
            $response = $responses[$key] ?? new WebsitePerformanceSourceException('ga4_report_unavailable');

            if (! $response instanceof Response) {
                continue;
            }

            $responses[$key] = $this->paginateReport($url, $accessToken, $report, $response);
        }

        return $responses;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return Response|Throwable|array<string, mixed>
     */
    private function paginateReport(string $url, string $accessToken, array $report, Response $initialResponse): Response|Throwable|array
    {
        if (! $initialResponse->successful()) {
            return $initialResponse;
        }

        $payload = $initialResponse->json();
        $initialRows = $this->pageRows($payload);

        if ($initialRows === null) {
            return $initialResponse;
        }

        $hasRowCount = is_array($payload) && array_key_exists('rowCount', $payload);
        $rowCount = $this->rowCount($payload);

        if ($rowCount === null) {
            return ! $hasRowCount && count($initialRows) < self::REPORT_PAGE_SIZE
                ? $initialResponse
                : new WebsitePerformanceSourceException('ga4_report_pagination_unavailable');
        }

        if ($rowCount < count($initialRows)) {
            return new WebsitePerformanceSourceException('ga4_report_pagination_unavailable');
        }

        if ($rowCount === count($initialRows)) {
            return $initialResponse;
        }

        $allRows = $initialRows;

        while (count($allRows) < $rowCount) {
            $pageReport = [...$report, 'offset' => (string) count($allRows)];

            try {
                $response = $this->request()
                    ->withToken($accessToken)
                    ->post($url, $pageReport);
            } catch (Throwable $exception) {
                return $exception;
            }

            if (! $response->successful()) {
                return new WebsitePerformanceSourceException('ga4_report_pagination_unavailable');
            }

            $pagePayload = $response->json();
            $rows = $this->pageRows($pagePayload);
            $expectedRows = min(self::REPORT_PAGE_SIZE, $rowCount - count($allRows));

            if ($rows === null
                || count($rows) !== $expectedRows
                || (is_array($pagePayload)
                    && array_key_exists('rowCount', $pagePayload)
                    && $this->rowCount($pagePayload) !== $rowCount)) {
                return new WebsitePerformanceSourceException('ga4_report_pagination_unavailable');
            }

            array_push($allRows, ...$rows);
        }

        $payload['rows'] = $allRows;
        $payload['rowCount'] = $rowCount;

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    private function pageRows(mixed $payload): ?array
    {
        if (! is_array($payload) || ! isset($payload['rows']) || ! is_array($payload['rows'])) {
            return null;
        }

        if (array_filter($payload['rows'], 'is_array') !== $payload['rows']) {
            return null;
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = array_values($payload['rows']);

        return $rows;
    }

    private function rowCount(mixed $payload): ?int
    {
        $value = is_array($payload) ? $payload['rowCount'] ?? null : null;

        if (! is_scalar($value) || ! is_numeric($value) || ! is_finite((float) $value)) {
            return null;
        }

        $count = (float) $value;

        return $count < 0 || floor($count) !== $count ? null : (int) $count;
    }

    /**
     * @param  array{current: array{start: string, end: string}, previous: array{start: string, end: string}, context_90d: array{start: string, end: string}}  $periods
     * @return array<string, array<string, mixed>>
     */
    private function reportDefinitions(array $periods): array
    {
        $reports = [];

        foreach (['current', 'previous'] as $period) {
            $range = $periods[$period];
            $reports["{$period}_totals"] = $this->report($range, [], self::TOTAL_METRICS);
            $reports["{$period}_acquisition"] = $this->report(
                $range,
                ['sessionDefaultChannelGroup'],
                ['sessions', 'activeUsers', 'engagedSessions', 'eventCount'],
            );
            $reports["{$period}_landing_pages"] = $this->report(
                $range,
                ['landingPagePlusQueryString'],
                ['sessions', 'activeUsers', 'engagedSessions', 'engagementRate', 'averageSessionDuration'],
            );
            $reports["{$period}_events"] = $this->report(
                $range,
                ['eventName'],
                ['eventCount', 'activeUsers', 'keyEvents'],
                [
                    'filter' => [
                        'fieldName' => 'eventName',
                        'inListFilter' => ['values' => self::EVENT_NAMES],
                    ],
                ],
            );
            $reports["{$period}_cta_funnel"] = $this->report(
                $range,
                ['eventName', 'customEvent:ui_location', 'customEvent:page_type'],
                ['eventCount', 'activeUsers'],
                [
                    'filter' => [
                        'fieldName' => 'eventName',
                        'inListFilter' => ['values' => self::CTA_FUNNEL_EVENTS],
                    ],
                ],
            );

            foreach (['locale', 'page_type', 'ui_location'] as $dimension) {
                $reports["{$period}_{$dimension}"] = $this->report(
                    $range,
                    ["customEvent:{$dimension}"],
                    ['eventCount', 'activeUsers'],
                );
            }
        }

        $reports['context_90d_totals'] = $this->report($periods['context_90d'], [], self::TOTAL_METRICS);

        return $reports;
    }

    /**
     * @param  array{start: string, end: string}  $range
     * @param  list<string>  $dimensions
     * @param  list<string>  $metrics
     * @param  array<string, mixed>|null  $dimensionFilter
     * @return array<string, mixed>
     */
    private function report(array $range, array $dimensions, array $metrics, ?array $dimensionFilter = null): array
    {
        $report = [
            'dateRanges' => [[
                'startDate' => $range['start'],
                'endDate' => $range['end'],
            ]],
            'dimensions' => array_map(fn (string $name): array => ['name' => $name], $dimensions),
            'metrics' => array_map(fn (string $name): array => ['name' => $name], $metrics),
            'limit' => self::REPORT_PAGE_SIZE,
            'keepEmptyRows' => false,
        ];

        if ($dimensionFilter !== null) {
            $report['dimensionFilter'] = $dimensionFilter;
        }

        return $report;
    }

    /**
     * @param  array<string, Response|Throwable|array<string, mixed>>  $responses
     * @param  list<string>  $warnings
     * @return array{totals: array<string, int|float>|null, acquisition_channels: array{available: bool, rows: list<array<string, int|float|string>>}, landing_pages: array{available: bool, rows: list<array<string, int|float|string>>}, events: array{available: bool, rows: list<array<string, int|float|string>>}, cta_funnel: array{available: bool, rows: list<array<string, int|float|string|null>>}, segments: array<string, array{available: bool, rows: list<array<string, int|float|string>}>}
     */
    private function window(array $responses, string $period, array &$warnings): array
    {
        return [
            'totals' => $this->totals($responses, "{$period}_totals", $warnings),
            'acquisition_channels' => $this->breakdown(
                $responses,
                "{$period}_acquisition",
                'channel',
                ['sessions', 'activeUsers', 'engagedSessions', 'eventCount'],
                $warnings,
            ),
            'landing_pages' => $this->breakdown(
                $responses,
                "{$period}_landing_pages",
                'landing_page',
                ['sessions', 'activeUsers', 'engagedSessions', 'engagementRate', 'averageSessionDuration'],
                $warnings,
                pagePath: true,
            ),
            'events' => $this->breakdown(
                $responses,
                "{$period}_events",
                'event_name',
                ['eventCount', 'activeUsers', 'keyEvents'],
                $warnings,
                allowedValues: self::EVENT_NAMES,
            ),
            'cta_funnel' => $this->ctaFunnel($responses, "{$period}_cta_funnel", $warnings),
            'segments' => [
                'locale' => $this->breakdown(
                    $responses,
                    "{$period}_locale",
                    'locale',
                    ['eventCount', 'activeUsers'],
                    $warnings,
                    tokenValue: true,
                ),
                'page_type' => $this->breakdown(
                    $responses,
                    "{$period}_page_type",
                    'page_type',
                    ['eventCount', 'activeUsers'],
                    $warnings,
                    tokenValue: true,
                ),
                'ui_location' => $this->breakdown(
                    $responses,
                    "{$period}_ui_location",
                    'ui_location',
                    ['eventCount', 'activeUsers'],
                    $warnings,
                    tokenValue: true,
                ),
            ],
        ];
    }

    /**
     * @param  array{totals: array<string, int|float>|null, acquisition_channels: array{available: bool}, landing_pages: array{available: bool}, events: array{available: bool}, cta_funnel: array{available: bool}, segments: array<string, array{available: bool}>}  $window
     */
    private function windowHasData(array $window): bool
    {
        return $window['totals'] !== null
            || $window['acquisition_channels']['available']
            || $window['landing_pages']['available']
            || $window['events']['available']
            || $window['cta_funnel']['available']
            || collect($window['segments'])->contains(
                fn (array $segment): bool => $segment['available'],
            );
    }

    /**
     * @param  array{totals: array<string, int|float>|null, acquisition_channels: array{available: bool}, landing_pages: array{available: bool}, events: array{available: bool}, cta_funnel: array{available: bool}, segments: array<string, array{available: bool}>}  $current
     * @param  array{totals: array<string, int|float>|null, acquisition_channels: array{available: bool}, landing_pages: array{available: bool}, events: array{available: bool}, cta_funnel: array{available: bool}, segments: array<string, array{available: bool}>}  $previous
     * @param  array{totals: array<string, int|float>|null}  $context
     * @param  array{current: array{start: string, end: string}, previous: array{start: string, end: string}, context_90d: array{start: string, end: string}}  $periods
     */
    private function freshThrough(array $current, array $previous, array $context, array $periods): ?string
    {
        if ($this->windowHasData($current)) {
            return $periods['current']['end'];
        }

        if ($context['totals'] !== null) {
            return $periods['context_90d']['end'];
        }

        return $this->windowHasData($previous) ? $periods['previous']['end'] : null;
    }

    /**
     * @param  array<string, Response|Throwable|array<string, mixed>>  $responses
     * @param  list<string>  $warnings
     * @return array{available: bool, rows: list<array{event_name: string, ui_location: string|null, page_type: string|null, eventCount: int|float, activeUsers: int|float}>}
     */
    private function ctaFunnel(array $responses, string $key, array &$warnings): array
    {
        $rows = $this->rows($responses, $key, $warnings);

        if ($rows === null) {
            return ['available' => false, 'rows' => []];
        }

        $mapped = [];

        foreach ($rows as $row) {
            $eventNameValue = $row['dimensionValues'][0]['value'] ?? null;
            $uiLocationValue = $row['dimensionValues'][1]['value'] ?? null;
            $pageTypeValue = $row['dimensionValues'][2]['value'] ?? null;

            if ($this->hasInvalidUtf8($eventNameValue)
                || $this->hasInvalidUtf8($uiLocationValue)
                || $this->hasInvalidUtf8($pageTypeValue)) {
                $warnings[] = "ga4_{$key}_invalid_utf8";

                continue;
            }

            $eventName = $this->label($eventNameValue);

            if ($eventName === null || ! in_array($eventName, self::CTA_FUNNEL_EVENTS, true)) {
                continue;
            }

            $metrics = $this->metrics($row, ['eventCount', 'activeUsers']);

            if ($metrics === null) {
                $warnings[] = "ga4_{$key}_invalid";

                return ['available' => false, 'rows' => []];
            }

            $mapped[] = [
                'event_name' => $eventName,
                'ui_location' => $this->tokenValue($uiLocationValue),
                'page_type' => $this->tokenValue($pageTypeValue),
                ...$metrics,
            ];
        }

        return ['available' => true, 'rows' => $mapped];
    }

    /**
     * @param  array<string, Response|Throwable|array<string, mixed>>  $responses
     * @param  list<string>  $warnings
     * @return array<string, int|float>|null
     */
    private function totals(array $responses, string $key, array &$warnings): ?array
    {
        $rows = $this->rows($responses, $key, $warnings);

        if ($rows === null || count($rows) !== 1) {
            if ($rows !== null && $rows !== []) {
                $warnings[] = "ga4_{$key}_invalid";
            }

            return null;
        }

        $metrics = $this->metrics($rows[0], self::TOTAL_METRICS);

        if ($metrics === null) {
            $warnings[] = "ga4_{$key}_invalid";
        }

        return $metrics;
    }

    /**
     * @param  array<string, Response|Throwable|array<string, mixed>>  $responses
     * @param  list<string>  $metrics
     * @param  list<string>  $warnings
     * @param  list<string>|null  $allowedValues
     * @return array{available: bool, rows: list<array<string, int|float|string>>}
     */
    private function breakdown(
        array $responses,
        string $key,
        string $labelKey,
        array $metrics,
        array &$warnings,
        bool $pagePath = false,
        ?array $allowedValues = null,
        bool $tokenValue = false,
    ): array {
        $rows = $this->rows($responses, $key, $warnings);

        if ($rows === null) {
            return ['available' => false, 'rows' => []];
        }

        $mapped = [];

        foreach ($rows as $row) {
            $rawValue = $row['dimensionValues'][0]['value'] ?? null;

            if ($this->hasInvalidUtf8($rawValue)) {
                $warnings[] = "ga4_{$key}_invalid_utf8";

                continue;
            }

            $invalidUtf8 = false;
            $label = $pagePath
                ? $this->pagePath($rawValue, $invalidUtf8)
                : ($tokenValue ? $this->tokenValue($rawValue) : $this->label($rawValue));

            if ($invalidUtf8) {
                $warnings[] = "ga4_{$key}_invalid_utf8";

                continue;
            }

            if ($label === null || ($allowedValues !== null && ! in_array($label, $allowedValues, true))) {
                continue;
            }

            $metricValues = $this->metrics($row, $metrics);

            if ($metricValues === null) {
                $warnings[] = "ga4_{$key}_invalid";

                return ['available' => false, 'rows' => []];
            }

            $mapped[] = [
                $labelKey => $label,
                ...$metricValues,
            ];
        }

        return ['available' => true, 'rows' => $mapped];
    }

    /**
     * @param  array<string, Response|Throwable|array<string, mixed>>  $responses
     * @param  list<string>  $warnings
     * @return list<array<string, mixed>>|null
     */
    private function rows(array $responses, string $key, array &$warnings): ?array
    {
        $response = $responses[$key] ?? null;

        if ($response instanceof Response) {
            if (! $response->successful()) {
                $warnings[] = "ga4_{$key}_unavailable";

                return null;
            }

            $payload = $response->json();
        } elseif (is_array($response)) {
            $payload = $response;
        } else {
            $warnings[] = "ga4_{$key}_unavailable";

            return null;
        }

        if (! is_array($payload) || ! isset($payload['rows']) || ! is_array($payload['rows'])) {
            $warnings[] = "ga4_{$key}_unavailable";

            return null;
        }

        $rows = $payload['rows'];

        if (array_filter($rows, 'is_array') !== $rows) {
            $warnings[] = "ga4_{$key}_unavailable";

            return null;
        }

        /** @var list<array<string, mixed>> $rows */
        return array_values($rows);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $names
     * @return array<string, int|float>|null
     */
    private function metrics(array $row, array $names): ?array
    {
        $values = is_array($row['metricValues'] ?? null) ? $row['metricValues'] : [];
        $metrics = [];

        foreach ($names as $index => $name) {
            $value = $values[$index]['value'] ?? null;

            if (! is_scalar($value) || ! is_numeric($value) || ! is_finite((float) $value)) {
                return null;
            }

            $number = (float) $value;

            if ($number < 0) {
                return null;
            }

            if (in_array($name, self::COUNTER_METRICS, true)) {
                if (floor($number) !== $number) {
                    return null;
                }

                $metrics[$name] = (int) $number;

                continue;
            }

            $metrics[$name] = $number;
        }

        return $metrics;
    }

    private function label(mixed $value, ?bool &$invalidUtf8 = null): ?string
    {
        $invalidUtf8 = false;

        if (! is_scalar($value)) {
            return null;
        }

        if (! mb_check_encoding((string) $value, 'UTF-8')) {
            $invalidUtf8 = true;

            return null;
        }

        $value = trim(strip_tags((string) $value));

        if ($value === '') {
            return null;
        }

        if (! mb_check_encoding($value, 'UTF-8')) {
            $invalidUtf8 = true;

            return null;
        }

        $value = Str::limit($value, 120, '');

        if (! mb_check_encoding($value, 'UTF-8')) {
            $invalidUtf8 = true;

            return null;
        }

        return $value;
    }

    private function hasInvalidUtf8(mixed $value): bool
    {
        return is_string($value) && ! mb_check_encoding($value, 'UTF-8');
    }

    private function tokenValue(mixed $value): ?string
    {
        $value = $this->label($value);

        if ($value === null || preg_match('/\A[A-Za-z0-9_.:-]{1,120}\z/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    private function pagePath(mixed $value, ?bool &$invalidUtf8 = null): ?string
    {
        $invalidUtf8 = false;
        $labelInvalidUtf8 = false;
        $value = $this->label($value, $labelInvalidUtf8);

        if ($labelInvalidUtf8) {
            $invalidUtf8 = true;
        }

        if ($value === null) {
            return null;
        }

        if ($value === '(not set)') {
            return $value;
        }

        $path = parse_url($value, PHP_URL_PATH);

        if (! is_string($path) || ! str_starts_with($path, '/')) {
            return null;
        }

        if (! mb_check_encoding($path, 'UTF-8')) {
            $invalidUtf8 = true;

            return null;
        }

        $path = Str::limit($path, 240, '');

        if (! mb_check_encoding($path, 'UTF-8')) {
            $invalidUtf8 = true;

            return null;
        }

        return $path;
    }

    /**
     * @param  array<string, int|float>|null  $current
     * @param  array<string, int|float>|null  $previous
     * @return array<string, array{current: int|float|null, previous: int|float|null, absolute: int|float|null, relative: float|null}>
     */
    private function deltas(?array $current, ?array $previous): array
    {
        $deltas = [];

        foreach (self::TOTAL_METRICS as $metric) {
            $currentValue = $current[$metric] ?? null;
            $previousValue = $previous[$metric] ?? null;
            $absolute = is_int($currentValue) || is_float($currentValue)
                ? ((is_int($previousValue) || is_float($previousValue)) ? $currentValue - $previousValue : null)
                : null;

            $deltas[$metric] = [
                'current' => $currentValue,
                'previous' => $previousValue,
                'absolute' => $absolute,
                'relative' => $previousValue === null || $previousValue == 0 || $absolute === null
                    ? null
                    : round($absolute / $previousValue, 4),
            ];
        }

        return $deltas;
    }
}
