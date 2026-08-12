<?php

namespace App\Services\WebsitePerformance;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Throwable;

class FirstPartyMetricsClient extends WebsitePerformanceHttpClient
{
    private ?string $accessToken = null;

    /**
     * @param  array{current: array{start: string, end: string}, previous: array{start: string, end: string}, context_90d: array{start: string, end: string}}  $periods
     * @return array<string, mixed>
     */
    public function collect(array $periods): array
    {
        $this->ensureConfiguration();
        $accessToken = $this->accessToken();

        $warnings = [];
        $current = $this->collectWindow($accessToken, 'current', $periods['current'], $warnings);
        $previous = $this->collectWindow($accessToken, 'previous', $periods['previous'], $warnings);
        $context = $this->collectWindow($accessToken, 'context_90d', $periods['context_90d'], $warnings);
        $hasData = $current !== null || $previous !== null || $context !== null;

        return [
            'status' => ! $hasData ? 'unavailable' : ($warnings === [] ? 'ok' : 'partial'),
            'fresh_through' => $this->freshThrough($current, $previous, $context, $periods),
            'warnings' => $warnings,
            'current' => ['inquiries' => $current],
            'previous' => ['inquiries' => $previous],
            'context_90d' => ['inquiries' => $context],
            'deltas' => $this->deltas($current, $previous),
        ];
    }

    /**
     * @param  array{start: string, end: string}  $range
     * @param  list<string>  $warnings
     * @return array{timezone: string, start_date: string, end_date: string, total: int, by_day: list<array{date: string, count: int}>, by_locale: list<array{locale: string, count: int}>, by_service: list<array{service_key: string, count: int}>, by_status: list<array{status: string, count: int}>, responded: int, response_rate: float|null}|null
     */
    private function collectWindow(string $accessToken, string $period, array $range, array &$warnings): ?array
    {
        try {
            return $this->window($accessToken, $range);
        } catch (WebsitePerformanceSourceException) {
            $warnings[] = "first_party_{$period}_unavailable";

            return null;
        }
    }

    /**
     * @param  array{timezone: string, start_date: string, end_date: string, total: int, by_day: list<array{date: string, count: int}>, by_locale: list<array{locale: string, count: int}>, by_service: list<array{service_key: string, count: int}>, by_status: list<array{status: string, count: int}>, responded: int, response_rate: float|null}|null  $current
     * @param  array{timezone: string, start_date: string, end_date: string, total: int, by_day: list<array{date: string, count: int}>, by_locale: list<array{locale: string, count: int}>, by_service: list<array{service_key: string, count: int}>, by_status: list<array{status: string, count: int}>, responded: int, response_rate: float|null}|null  $previous
     * @param  array{timezone: string, start_date: string, end_date: string, total: int, by_day: list<array{date: string, count: int}>, by_locale: list<array{locale: string, count: int}>, by_service: list<array{service_key: string, count: int}>, by_status: list<array{status: string, count: int}>, responded: int, response_rate: float|null}|null  $context
     * @param  array{current: array{start: string, end: string}, previous: array{start: string, end: string}, context_90d: array{start: string, end: string}}  $periods
     */
    private function freshThrough(?array $current, ?array $previous, ?array $context, array $periods): ?string
    {
        if ($current !== null) {
            return $periods['current']['end'];
        }

        if ($context !== null) {
            return $periods['context_90d']['end'];
        }

        return $previous !== null ? $periods['previous']['end'] : null;
    }

    private function ensureConfiguration(): void
    {
        $metricsUrl = trim((string) $this->config->get('services.website_performance.metrics_url'));
        $tokenUrl = trim((string) $this->config->get('services.website_performance.metrics_token_url'));
        $websiteUrl = trim((string) $this->config->get('services.website_performance.website_url'));
        $clientId = trim((string) $this->config->get('services.website_performance.metrics_client_id'));
        $clientSecret = (string) $this->config->get('services.website_performance.metrics_client_secret');

        if (! $this->isSecureUrl($metricsUrl)
            || ! $this->isSecureUrl($tokenUrl)
            || ! $this->isSecureUrl($websiteUrl)
            || ! $this->sharesWebsiteOrigin($metricsUrl, $websiteUrl)
            || ! $this->sharesWebsiteOrigin($tokenUrl, $websiteUrl)
            || $clientId === ''
            || $clientSecret === '') {
            throw new WebsitePerformanceSourceException('first_party_configuration_unavailable');
        }
    }

    private function accessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        try {
            $response = $this->request()
                ->withOptions(['allow_redirects' => false])
                ->asForm()
                ->post((string) $this->config->get('services.website_performance.metrics_token_url'), [
                    'grant_type' => 'client_credentials',
                    'client_id' => (string) $this->config->get('services.website_performance.metrics_client_id'),
                    'client_secret' => (string) $this->config->get('services.website_performance.metrics_client_secret'),
                    'scope' => 'analytics:read',
                ]);
        } catch (Throwable) {
            throw new WebsitePerformanceSourceException('first_party_authentication_unavailable');
        }

        if (! $response->successful()) {
            throw new WebsitePerformanceSourceException('first_party_authentication_unavailable');
        }

        $payload = $response->json();
        $accessToken = is_array($payload) ? $payload['access_token'] ?? null : null;

        if (! is_string($accessToken) || trim($accessToken) === '') {
            throw new WebsitePerformanceSourceException('first_party_authentication_unavailable');
        }

        return $this->accessToken = trim($accessToken);
    }

    /**
     * @param  array{start: string, end: string}  $range
     * @return array{timezone: string, start_date: string, end_date: string, total: int, by_day: list<array{date: string, count: int}>, by_locale: list<array{locale: string, count: int}>, by_service: list<array{service_key: string, count: int}>, by_status: list<array{status: string, count: int}>, responded: int, response_rate: float|null}
     */
    private function window(string $accessToken, array $range): array
    {
        try {
            $response = $this->request()
                ->withOptions(['allow_redirects' => false])
                ->withToken($accessToken)
                ->get((string) $this->config->get('services.website_performance.metrics_url'), [
                    'start' => $range['start'],
                    'end' => $range['end'],
                ]);
        } catch (Throwable) {
            throw new WebsitePerformanceSourceException('first_party_metrics_unavailable');
        }

        if (! $response->successful()) {
            throw new WebsitePerformanceSourceException('first_party_metrics_unavailable');
        }

        return $this->payload($response, $range);
    }

    /**
     * @param  array{start: string, end: string}  $range
     * @return array{timezone: string, start_date: string, end_date: string, total: int, by_day: list<array{date: string, count: int}>, by_locale: list<array{locale: string, count: int}>, by_service: list<array{service_key: string, count: int}>, by_status: list<array{status: string, count: int}>, responded: int, response_rate: float|null}
     */
    private function payload(Response $response, array $range): array
    {
        $payload = $response->json();
        $data = is_array($payload) ? $payload['data'] ?? null : null;
        $inquiries = is_array($data) ? $data['inquiries'] ?? null : null;

        if (! is_array($data)
            || ! is_array($inquiries)
            || ($data['start_date'] ?? null) !== $range['start']
            || ($data['end_date'] ?? null) !== $range['end']
            || (($payload['meta']['privacy'] ?? null) !== 'aggregate_only')) {
            throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
        }

        $timezone = trim((string) ($data['timezone'] ?? ''));

        if ($timezone !== $this->timezone()) {
            throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
        }

        foreach (['total', 'by_day', 'by_locale', 'by_service', 'by_status', 'responded', 'response_rate'] as $field) {
            if (! array_key_exists($field, $inquiries)) {
                throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
            }
        }

        $total = $this->count($inquiries['total']);
        $byDay = $this->byDay($inquiries['by_day'], $range);
        $byLocale = $this->byToken($inquiries['by_locale'], 'locale');
        $byService = $this->byToken($inquiries['by_service'], 'service_key');
        $byStatus = $this->byStatus($inquiries['by_status']);
        $responded = $this->count($inquiries['responded']);
        $responseRate = $this->rate($inquiries['response_rate'], $total, $responded);

        if ($responded > $total
            || $this->sumCounts($byDay) !== $total
            || $this->sumCounts($byLocale) !== $total
            || $this->sumCounts($byService) !== $total
            || $this->sumCounts($byStatus) !== $total) {
            throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
        }

        return [
            'timezone' => $timezone,
            'start_date' => $range['start'],
            'end_date' => $range['end'],
            'total' => $total,
            'by_day' => $byDay,
            'by_locale' => $byLocale,
            'by_service' => $byService,
            'by_status' => $byStatus,
            'responded' => $responded,
            'response_rate' => $responseRate,
        ];
    }

    private function count(mixed $value): int
    {
        if ((! is_int($value) || $value < 0) && (! is_string($value) || ! ctype_digit($value))) {
            throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
        }

        return (int) $value;
    }

    /**
     * @return list<array{date: string, count: int}>
     */
    private function byDay(mixed $rows, array $range): array
    {
        if (! is_array($rows) || count($rows) > 366) {
            throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
        }

        $safeRows = [];
        $seen = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! is_string($row['date'] ?? null) || ! $this->date($row['date'])) {
                throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
            }

            if ($row['date'] < $range['start'] || $row['date'] > $range['end'] || isset($seen[$row['date']])) {
                throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
            }

            $seen[$row['date']] = true;

            $safeRows[] = [
                'date' => $row['date'],
                'count' => $this->count($row['count'] ?? null),
            ];
        }

        return $safeRows;
    }

    /**
     * @return list<array{locale?: string, service_key?: string, count: int}>
     */
    private function byToken(mixed $rows, string $field): array
    {
        if (! is_array($rows) || count($rows) > 100) {
            throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
        }

        $safeRows = [];
        $seen = [];

        foreach ($rows as $row) {
            $value = is_array($row) ? $row[$field] ?? null : null;

            if (! is_string($value) || preg_match('/\A[A-Za-z0-9_.:-]{1,120}\z/', $value) !== 1) {
                throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
            }

            if (isset($seen[$value])) {
                throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
            }

            $seen[$value] = true;

            $safeRows[] = [
                $field => $value,
                'count' => $this->count($row['count'] ?? null),
            ];
        }

        return $safeRows;
    }

    /**
     * @return list<array{status: string, count: int}>
     */
    private function byStatus(mixed $rows): array
    {
        if (! is_array($rows) || count($rows) > 4) {
            throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
        }

        $safeRows = [];
        $seen = [];

        foreach ($rows as $row) {
            $status = is_array($row) ? $row['status'] ?? null : null;

            if (! is_string($status) || ! in_array($status, ['new', 'reviewing', 'replied', 'archived'], true)) {
                throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
            }

            if (isset($seen[$status])) {
                throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
            }

            $seen[$status] = true;

            $safeRows[] = [
                'status' => $status,
                'count' => $this->count($row['count'] ?? null),
            ];
        }

        return $safeRows;
    }

    private function rate(mixed $value, int $total, int $responded): ?float
    {
        if ($total === 0) {
            if ($value !== null) {
                throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
            }

            return null;
        }

        if (! is_numeric($value) || ! is_finite((float) $value)) {
            throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
        }

        $rate = round((float) $value, 4);

        if ($rate < 0 || $rate > 1 || abs($rate - round($responded / $total, 4)) > 0.0001) {
            throw new WebsitePerformanceSourceException('first_party_contract_unavailable');
        }

        return $rate;
    }

    private function date(string $value): bool
    {
        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value)?->toDateString() === $value;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param  list<array<string, int|string>>  $rows
     */
    private function sumCounts(array $rows): int
    {
        return array_sum(array_map(fn (array $row): int => (int) $row['count'], $rows));
    }

    private function timezone(): string
    {
        $timezone = trim((string) $this->config->get('services.website_performance.timezone', 'Asia/Riyadh'));

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'Asia/Riyadh';
    }

    private function sharesWebsiteOrigin(string $url, string $websiteUrl): bool
    {
        $port = parse_url($url, PHP_URL_PORT) ?? 443;
        $websitePort = parse_url($websiteUrl, PHP_URL_PORT) ?? 443;

        return parse_url($url, PHP_URL_SCHEME) === parse_url($websiteUrl, PHP_URL_SCHEME)
            && strtolower((string) parse_url($url, PHP_URL_HOST)) === strtolower((string) parse_url($websiteUrl, PHP_URL_HOST))
            && $port === $websitePort;
    }

    /**
     * @param  array{timezone: string, start_date: string, end_date: string, total: int, by_day: list<array{date: string, count: int}>, by_locale: list<array{locale: string, count: int}>, by_service: list<array{service_key: string, count: int}>, by_status: list<array{status: string, count: int}>, responded: int, response_rate: float|null}|null  $current
     * @param  array{timezone: string, start_date: string, end_date: string, total: int, by_day: list<array{date: string, count: int}>, by_locale: list<array{locale: string, count: int}>, by_service: list<array{service_key: string, count: int}>, by_status: list<array{status: string, count: int}>, responded: int, response_rate: float|null}|null  $previous
     * @return array<string, array{current: int|float|null, previous: int|float|null, absolute: int|float|null, relative: float|null}>
     */
    private function deltas(?array $current, ?array $previous): array
    {
        $deltas = [];

        foreach (['total', 'responded', 'response_rate'] as $metric) {
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
