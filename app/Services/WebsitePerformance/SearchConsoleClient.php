<?php

namespace App\Services\WebsitePerformance;

use App\Contracts\WebsitePerformance\GoogleAccessTokenProvider;
use Carbon\CarbonImmutable;
use DOMDocument;
use DOMXPath;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Throwable;

class SearchConsoleClient extends WebsitePerformanceHttpClient
{
    private const SEARCH_ANALYTICS_URL = 'https://www.googleapis.com/webmasters/v3/sites/%s/searchAnalytics/query';

    private const URL_INSPECTION_URL = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';

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
        $property = trim((string) $this->config->get('services.website_performance.search_console_property'));

        if (! $this->validProperty($property)) {
            throw new WebsitePerformanceSourceException('search_console_configuration_unavailable');
        }

        $accessToken = $this->tokens->accessToken();
        $warnings = [];
        $current = $this->window($accessToken, $property, $periods['current'], $warnings);
        $previous = $this->window($accessToken, $property, $periods['previous'], $warnings);
        $context = [
            'totals' => $this->totals($accessToken, $property, $periods['context_90d'], $warnings),
        ];
        $inspection = $this->inspectSitemapUrls($accessToken, $property, $warnings);

        $hasData = $this->windowHasData($current)
            || $this->windowHasData($previous)
            || $context['totals'] !== null
            || $inspection['available'];
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
            'url_inspection' => $inspection,
        ];
    }

    /**
     * @param  array{start: string, end: string}  $range
     * @param  list<string>  $warnings
     * @return array{totals: array{clicks: int, impressions: int, ctr: float, position: float}|null, queries: array{available: bool, rows: list<array{query: string, clicks: int, impressions: int, ctr: float, position: float}>}, pages: array{available: bool, rows: list<array{page: string, clicks: int, impressions: int, ctr: float, position: float}>}, countries: array{available: bool, rows: list<array{country: string, clicks: int, impressions: int, ctr: float, position: float}>}, devices: array{available: bool, rows: list<array{device: string, clicks: int, impressions: int, ctr: float, position: float}>}}
     */
    private function window(string $accessToken, string $property, array $range, array &$warnings): array
    {
        return [
            'totals' => $this->totals($accessToken, $property, $range, $warnings),
            'queries' => $this->breakdown($accessToken, $property, $range, 'query', $warnings),
            'pages' => $this->breakdown($accessToken, $property, $range, 'page', $warnings),
            'countries' => $this->breakdown($accessToken, $property, $range, 'country', $warnings),
            'devices' => $this->breakdown($accessToken, $property, $range, 'device', $warnings),
        ];
    }

    /**
     * @param  array{totals: array{clicks: int, impressions: int, ctr: float, position: float}|null, queries: array{available: bool}, pages: array{available: bool}, countries: array{available: bool}, devices: array{available: bool}}  $window
     */
    private function windowHasData(array $window): bool
    {
        return $window['totals'] !== null
            || $window['queries']['available']
            || $window['pages']['available']
            || $window['countries']['available']
            || $window['devices']['available'];
    }

    /**
     * @param  array{totals: array{clicks: int, impressions: int, ctr: float, position: float}|null, queries: array{available: bool}, pages: array{available: bool}, countries: array{available: bool}, devices: array{available: bool}}  $current
     * @param  array{totals: array{clicks: int, impressions: int, ctr: float, position: float}|null, queries: array{available: bool}, pages: array{available: bool}, countries: array{available: bool}, devices: array{available: bool}}  $previous
     * @param  array{totals: array{clicks: int, impressions: int, ctr: float, position: float}|null}  $context
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
     * @param  array{start: string, end: string}  $range
     * @param  list<string>  $warnings
     * @return array{clicks: int, impressions: int, ctr: float, position: float}|null
     */
    private function totals(string $accessToken, string $property, array $range, array &$warnings): ?array
    {
        $rows = $this->searchRows($accessToken, $property, $range, [], $warnings);

        if ($rows === null || $rows === []) {
            return null;
        }

        if (count($rows) !== 1) {
            $warnings[] = 'search_console_totals_invalid';

            return null;
        }

        $metrics = $this->metrics($rows[0]);

        if ($metrics === null) {
            $warnings[] = 'search_console_totals_invalid';
        }

        return $metrics;
    }

    /**
     * @param  array{start: string, end: string}  $range
     * @param  list<string>  $warnings
     * @return array{available: bool, rows: list<array{query?: string, page?: string, country?: string, device?: string, clicks: int, impressions: int, ctr: float, position: float}>}
     */
    private function breakdown(string $accessToken, string $property, array $range, string $dimension, array &$warnings): array
    {
        $rows = $this->searchRows($accessToken, $property, $range, [$dimension], $warnings);

        if ($rows === null) {
            return ['available' => false, 'rows' => []];
        }

        $mapped = [];

        foreach ($rows as $row) {
            $raw = $row['keys'][0] ?? null;
            $value = match ($dimension) {
                'query' => $this->query($raw),
                'page' => $this->canonicalPage($raw),
                'country' => $this->country($raw),
                'device' => $this->device($raw),
                default => null,
            };

            if ($value === null) {
                continue;
            }

            $metrics = $this->metrics($row);

            if ($metrics === null) {
                $warnings[] = "search_console_{$dimension}_invalid";

                return ['available' => false, 'rows' => []];
            }

            $mapped[] = [
                $dimension => $value,
                ...$metrics,
            ];
        }

        return ['available' => true, 'rows' => $mapped];
    }

    /**
     * @param  array{start: string, end: string}  $range
     * @param  list<string>  $dimensions
     * @param  list<string>  $warnings
     * @return list<array<string, mixed>>|null
     */
    private function searchRows(string $accessToken, string $property, array $range, array $dimensions, array &$warnings): ?array
    {
        $url = sprintf(self::SEARCH_ANALYTICS_URL, rawurlencode($property));
        $rows = [];
        $startRow = 0;
        $pageSize = 25000;
        while (true) {
            try {
                $response = $this->request()
                    ->withToken($accessToken)
                    ->post($url, [
                        'startDate' => $range['start'],
                        'endDate' => $range['end'],
                        'dimensions' => $dimensions,
                        'dataState' => 'final',
                        'rowLimit' => $pageSize,
                        'startRow' => $startRow,
                    ]);
            } catch (Throwable) {
                $warnings[] = 'search_console_request_unavailable';

                return null;
            }

            if (! $response->successful()) {
                $warnings[] = 'search_console_request_unavailable';

                return null;
            }

            $payload = $response->json();
            $pageRows = is_array($payload) ? $payload['rows'] ?? null : null;

            if (! is_array($pageRows)) {
                $warnings[] = 'search_console_rows_unavailable';

                return null;
            }

            $safeRows = array_values(array_filter($pageRows, 'is_array'));

            if (count($safeRows) !== count($pageRows)) {
                $warnings[] = 'search_console_rows_unavailable';

                return null;
            }

            if ($safeRows === []) {
                if ($startRow > 0) {
                    return $rows;
                }

                $warnings[] = 'search_console_rows_unavailable';

                return null;
            }

            $rows = [...$rows, ...$safeRows];

            if (count($safeRows) < $pageSize) {
                return $rows;
            }

            $startRow += $pageSize;
        }
    }

    /**
     * @param  list<string>  $warnings
     * @return array{available: bool, requested_count: int, inspected_count: int, capped: bool, results: list<array{url: string, verdict: string|null, coverage_state: string|null, indexing_state: string|null, last_crawl_time: string|null, page_fetch_state: string|null, robots_txt_state: string|null, user_canonical: string|null, google_canonical: string|null}>}
     */
    private function inspectSitemapUrls(string $accessToken, string $property, array &$warnings): array
    {
        $candidates = $this->inspectionCandidates($warnings);

        if ($candidates === []) {
            $warnings[] = 'url_inspection_sitemap_unavailable';

            return [
                'available' => false,
                'requested_count' => 0,
                'inspected_count' => 0,
                'capped' => false,
                'results' => [],
            ];
        }

        $limit = min(50, max(1, (int) $this->config->get('services.website_performance.url_inspection_limit', 50)));
        $selected = $this->selectInspectionCandidates($candidates, $limit);
        $concurrency = min(10, max(1, (int) $this->config->get('services.website_performance.url_inspection_concurrency', 5)));
        $responses = $this->http->pool(function (Pool $pool) use ($accessToken, $property, $selected): void {
            foreach ($selected as $index => $candidate) {
                $pool->as((string) $index)
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
                    ->post(self::URL_INSPECTION_URL, [
                        'inspectionUrl' => $candidate['url'],
                        'siteUrl' => $property,
                        'languageCode' => 'en-US',
                    ]);
            }
        }, $concurrency);

        $results = [];

        foreach ($selected as $index => $candidate) {
            $response = $responses[(string) $index] ?? null;

            if (! $response instanceof Response || ! $response->successful()) {
                $warnings[] = 'url_inspection_request_unavailable';

                continue;
            }

            $result = $this->inspectionResult($candidate['url'], $response->json());

            if ($result === null) {
                $warnings[] = 'url_inspection_response_unavailable';

                continue;
            }

            $results[] = $result;
        }

        return [
            'available' => $results !== [],
            'requested_count' => count($selected),
            'inspected_count' => count($results),
            'capped' => count($selected) < count($candidates),
            'results' => $results,
        ];
    }

    /**
     * @return list<array{url: string, last_modified: string|null, core: bool}>
     */
    private function inspectionCandidates(array &$warnings): array
    {
        $sitemapUrl = $this->sitemapUrl();

        if ($sitemapUrl === null) {
            $warnings[] = 'url_inspection_sitemap_unavailable';

            return [];
        }

        try {
            $response = $this->request()
                ->withOptions(['allow_redirects' => false])
                ->get($sitemapUrl);
        } catch (Throwable) {
            $warnings[] = 'url_inspection_sitemap_unavailable';

            return [];
        }

        if (! $response->successful()) {
            $warnings[] = 'url_inspection_sitemap_unavailable';

            return [];
        }

        return $this->parseSitemap($response->body(), $warnings);
    }

    private function sitemapUrl(): ?string
    {
        $websiteUrl = trim((string) $this->config->get('services.website_performance.website_url'));

        return $this->isSecureUrl($websiteUrl)
            ? rtrim($websiteUrl, '/').'/sitemap.xml'
            : null;
    }

    /**
     * @param  list<string>  $warnings
     * @return list<array{url: string, last_modified: string|null, core: bool}>
     */
    private function parseSitemap(string $xml, array &$warnings): array
    {
        $previousErrors = libxml_use_internal_errors(true);
        $document = new DOMDocument;

        try {
            $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } catch (Throwable) {
            $loaded = false;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }

        if (! $loaded) {
            $warnings[] = 'url_inspection_sitemap_unavailable';

            return [];
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[local-name()="url"]');

        if ($nodes === false) {
            $warnings[] = 'url_inspection_sitemap_unavailable';

            return [];
        }

        $candidates = [];

        foreach ($nodes as $node) {
            $url = $this->canonicalPage(trim((string) $xpath->evaluate('string(./*[local-name()="loc"])', $node)));

            if ($url === null) {
                continue;
            }

            $path = (string) parse_url($url, PHP_URL_PATH);
            $segments = array_values(array_filter(explode('/', $path)));
            $lastModified = $this->sitemapDate(
                trim((string) $xpath->evaluate('string(./*[local-name()="lastmod"])', $node)),
            );
            $candidates[$url] = [
                'url' => $url,
                'last_modified' => $lastModified,
                'core' => count($segments) <= 1,
            ];
        }

        if ($candidates === []) {
            $warnings[] = 'url_inspection_sitemap_unavailable';
        }

        return array_values($candidates);
    }

    private function sitemapDate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, $this->timezone())->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  list<array{url: string, last_modified: string|null, core: bool}>  $candidates
     * @return list<array{url: string, last_modified: string|null, core: bool}>
     */
    private function selectInspectionCandidates(array $candidates, int $limit): array
    {
        if (count($candidates) <= $limit) {
            return $candidates;
        }

        $recentThreshold = now($this->timezone())->subDays(90)->toDateString();
        usort($candidates, function (array $first, array $second) use ($recentThreshold): int {
            $firstPriority = ($first['core'] ? 2 : 0) + (($first['last_modified'] ?? '') >= $recentThreshold ? 1 : 0);
            $secondPriority = ($second['core'] ? 2 : 0) + (($second['last_modified'] ?? '') >= $recentThreshold ? 1 : 0);

            return $secondPriority <=> $firstPriority
                ?: strcmp((string) ($second['last_modified'] ?? ''), (string) ($first['last_modified'] ?? ''))
                ?: strcmp($first['url'], $second['url']);
        });

        return array_slice($candidates, 0, $limit);
    }

    /**
     * @return array{url: string, verdict: string|null, coverage_state: string|null, indexing_state: string|null, last_crawl_time: string|null, page_fetch_state: string|null, robots_txt_state: string|null, user_canonical: string|null, google_canonical: string|null}|null
     */
    private function inspectionResult(string $url, mixed $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        $index = $payload['inspectionResult']['indexStatusResult'] ?? null;

        if (! is_array($index)) {
            return null;
        }

        return [
            'url' => $url,
            'verdict' => $this->enumValue($index['verdict'] ?? null),
            'coverage_state' => $this->enumValue($index['coverageState'] ?? null),
            'indexing_state' => $this->enumValue($index['indexingState'] ?? null),
            'last_crawl_time' => $this->dateTime($index['lastCrawlTime'] ?? null),
            'page_fetch_state' => $this->enumValue($index['pageFetchState'] ?? null),
            'robots_txt_state' => $this->enumValue($index['robotsTxtState'] ?? null),
            'user_canonical' => $this->canonicalPage($index['userCanonical'] ?? null),
            'google_canonical' => $this->canonicalPage($index['googleCanonical'] ?? null),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{clicks: int, impressions: int, ctr: float, position: float}|null
     */
    private function metrics(array $row): ?array
    {
        $clicks = $this->number($row['clicks'] ?? null);
        $impressions = $this->number($row['impressions'] ?? null);
        $ctr = $this->number($row['ctr'] ?? null);
        $position = $this->number($row['position'] ?? null);

        if ($clicks === null || $impressions === null || $ctr === null || $position === null) {
            return null;
        }

        if ($clicks < 0
            || $impressions < 0
            || floor($clicks) !== $clicks
            || floor($impressions) !== $impressions
            || $ctr < 0
            || $ctr > 1
            || $position < 0) {
            return null;
        }

        return [
            'clicks' => (int) $clicks,
            'impressions' => (int) $impressions,
            'ctr' => $ctr,
            'position' => $position,
        ];
    }

    private function number(mixed $value): ?float
    {
        return is_scalar($value) && is_numeric($value) && is_finite((float) $value)
            ? (float) $value
            : null;
    }

    private function query(mixed $value): ?string
    {
        $query = $this->text($value, 160);

        if ($query === null
            || preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/iu', $query) === 1
            || preg_match('/https?:\/\/|\bwww\./iu', $query) === 1
            || preg_match('/(?:\+?\d[\s().-]*){7,}/u', $query) === 1) {
            return null;
        }

        return $query;
    }

    private function canonicalPage(mixed $value): ?string
    {
        if (! is_string($value) || ! $this->isSecureUrl($value)) {
            return null;
        }

        $base = trim((string) $this->config->get('services.website_performance.website_url'));

        if (! $this->isSecureUrl($base)) {
            return null;
        }

        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        $baseHost = strtolower((string) parse_url($base, PHP_URL_HOST));

        if ($host === '' || $host !== $baseHost) {
            return null;
        }

        $path = (string) (parse_url($value, PHP_URL_PATH) ?: '/');

        return rtrim($base, '/').'/'.ltrim(Str::limit($path, 480, ''), '/');
    }

    private function country(mixed $value): ?string
    {
        $value = $this->text($value, 2);

        return $value !== null && preg_match('/\A[A-Za-z]{2}\z/', $value) === 1
            ? strtoupper($value)
            : null;
    }

    private function device(mixed $value): ?string
    {
        $value = $this->text($value, 20);

        return $value !== null && preg_match('/\A[A-Z_]{1,20}\z/', $value) === 1 ? $value : null;
    }

    private function enumValue(mixed $value): ?string
    {
        $value = $this->text($value, 120);

        return $value !== null && preg_match('/\A[A-Za-z0-9_ -]{1,120}\z/', $value) === 1 ? $value : null;
    }

    private function dateTime(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc()->toAtomString();
        } catch (Throwable) {
            return null;
        }
    }

    private function text(mixed $value, int $limit): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : Str::limit($value, $limit, '');
    }

    private function validProperty(string $property): bool
    {
        return str_starts_with($property, 'sc-domain:') || $this->isSecureUrl($property);
    }

    private function timezone(): string
    {
        $timezone = trim((string) $this->config->get('services.website_performance.timezone', 'Asia/Riyadh'));

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'Asia/Riyadh';
    }

    /**
     * @param  array{clicks: int, impressions: int, ctr: float, position: float}|null  $current
     * @param  array{clicks: int, impressions: int, ctr: float, position: float}|null  $previous
     * @return array<string, array{current: int|float|null, previous: int|float|null, absolute: int|float|null, relative: float|null}>
     */
    private function deltas(?array $current, ?array $previous): array
    {
        $deltas = [];

        foreach (['clicks', 'impressions', 'ctr', 'position'] as $metric) {
            $currentValue = $current[$metric] ?? null;
            $previousValue = $previous[$metric] ?? null;
            $absolute = $currentValue === null || $previousValue === null ? null : $currentValue - $previousValue;

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
