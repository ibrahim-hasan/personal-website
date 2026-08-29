<?php

namespace App\Services\WebsitePerformance;

class SeoTargetScorecard
{
    public const MeaningfulQueryImpressions = 30;

    public const TargetPageCtrImpressions = 100;

    public const TargetPageCtr = 0.04;

    public const TopTenGoal = 2;

    public const AdditionalTopTwentyGoal = 3;

    /**
     * @param  array<string, array<string, mixed>>  $sources
     * @return array<string, mixed>
     */
    public function build(array $sources): array
    {
        $searchConsole = is_array($sources['search_console'] ?? null) ? $sources['search_console'] : [];
        $ga4 = is_array($sources['ga4'] ?? null) ? $sources['ga4'] : [];
        $queryGroups = $this->queryGroups($searchConsole);
        $targetPageCtr = $this->targetPageCtr($searchConsole);
        $regionalGrowth = $this->comparison($searchConsole, 'saudi_gcc_non_brand', 'clicks', 'growth', 'not_met');
        $internationalEnglish = $this->comparison($searchConsole, 'international_english', 'clicks', 'maintained', 'declined');
        $organicConsultations = $this->organicConsultations($ga4);
        $localeBreakdown = $this->localeBreakdown($searchConsole);
        $componentStatuses = [
            $queryGroups['status'],
            $targetPageCtr['status'],
            $regionalGrowth['status'],
            $internationalEnglish['status'],
        ];

        return [
            'status' => $this->overallStatus($componentStatuses),
            'query_groups' => $queryGroups,
            'target_page_ctr' => $targetPageCtr,
            'saudi_gcc_non_brand' => $regionalGrowth,
            'international_english' => $internationalEnglish,
            'organic_consultations' => $organicConsultations,
            'locale_breakdown' => $localeBreakdown,
            'measurement' => [
                'query_group_minimum_impressions' => self::MeaningfulQueryImpressions,
                'target_page_minimum_impressions' => self::TargetPageCtrImpressions,
                'target_page_ctr' => self::TargetPageCtr,
                'language_method' => 'canonical_url',
                'organic_consultations_scope' => 'consented_ga4_organic_search',
                'search_console_data_basis' => 'top_rows',
                'search_console_sample_limited' => true,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $searchConsole
     * @return array<string, mixed>
     */
    private function queryGroups(array $searchConsole): array
    {
        $signals = $this->targetSignals($searchConsole, 'context_90d');

        if ($signals === null || ! is_array($signals['query_groups'] ?? null)) {
            return $this->unavailableQueryGroups();
        }

        $rows = [];
        $seen = [];
        $topTen = 0;
        $topTwenty = 0;
        $eligible = 0;
        $wrongPageClicks = 0;
        $wrongPageImpressions = 0;
        $queryGroupKeys = SeoTargetPageResolver::queryGroupKeys();

        foreach ($signals['query_groups'] as $candidate) {
            if (! is_array($candidate)
                || ! is_string($candidate['key'] ?? null)
                || ! in_array($candidate['key'], $queryGroupKeys, true)
                || isset($seen[$candidate['key']])) {
                return $this->unavailableQueryGroups();
            }

            $metrics = $this->metrics($candidate);

            if ($metrics === null) {
                return $this->unavailableQueryGroups();
            }

            $assignedPageKeys = $candidate['assigned_page_keys'] ?? null;
            $candidateWrongPageClicks = $this->count($candidate['wrong_page_clicks'] ?? null);
            $candidateWrongPageImpressions = $this->count($candidate['wrong_page_impressions'] ?? null);

            if (! is_array($assignedPageKeys)
                || array_values($assignedPageKeys) !== SeoTargetPageResolver::assignments()[$candidate['key']]
                || $candidateWrongPageClicks === null
                || $candidateWrongPageImpressions === null
                || $candidateWrongPageClicks > $candidateWrongPageImpressions) {
                return $this->unavailableQueryGroups();
            }

            $seen[$candidate['key']] = true;
            $wrongPageClicks += $candidateWrongPageClicks;
            $wrongPageImpressions += $candidateWrongPageImpressions;
            $status = 'insufficient_sample';

            if ($metrics['impressions'] >= self::MeaningfulQueryImpressions) {
                $eligible++;
                $status = match (true) {
                    $metrics['position'] <= 10 => 'top_10',
                    $metrics['position'] <= 20 => 'top_20',
                    default => 'outside_top_20',
                };
                $topTen += $status === 'top_10' ? 1 : 0;
                $topTwenty += in_array($status, ['top_10', 'top_20'], true) ? 1 : 0;
            }

            $rows[] = [
                'key' => $candidate['key'],
                'status' => $status,
                'assigned_page_keys' => $assignedPageKeys,
                ...$metrics,
                'wrong_page_clicks' => $candidateWrongPageClicks,
                'wrong_page_impressions' => $candidateWrongPageImpressions,
            ];
        }

        if (count($seen) !== count($queryGroupKeys)) {
            return $this->unavailableQueryGroups();
        }

        usort(
            $rows,
            fn (array $first, array $second): int => array_search($first['key'], $queryGroupKeys, true)
                <=> array_search($second['key'], $queryGroupKeys, true),
        );

        $additionalTopTwenty = max(0, $topTwenty - min($topTen, self::TopTenGoal));

        return [
            'available' => true,
            'status' => $eligible === 0 && $wrongPageImpressions === 0 ? 'insufficient_sample' : 'directional',
            'observed_status' => $eligible === 0
                ? 'insufficient_sample'
                : ($topTen >= self::TopTenGoal && $additionalTopTwenty >= self::AdditionalTopTwentyGoal ? 'met' : 'not_met'),
            'sample_limited' => true,
            'measurement_basis' => 'search_console_top_rows',
            'eligible_count' => $eligible,
            'top_10_count' => $topTen,
            'top_10_goal' => self::TopTenGoal,
            'additional_top_20_count' => $additionalTopTwenty,
            'additional_top_20_goal' => self::AdditionalTopTwentyGoal,
            'minimum_impressions' => self::MeaningfulQueryImpressions,
            'wrong_page_clicks' => $wrongPageClicks,
            'wrong_page_impressions' => $wrongPageImpressions,
            'groups' => $rows,
        ];
    }

    /** @return array<string, mixed> */
    private function unavailableQueryGroups(): array
    {
        return [
            'available' => false,
            'status' => 'unavailable',
            'observed_status' => 'unavailable',
            'sample_limited' => true,
            'measurement_basis' => 'search_console_top_rows',
            'eligible_count' => null,
            'top_10_count' => null,
            'top_10_goal' => self::TopTenGoal,
            'additional_top_20_count' => null,
            'additional_top_20_goal' => self::AdditionalTopTwentyGoal,
            'minimum_impressions' => self::MeaningfulQueryImpressions,
            'wrong_page_clicks' => null,
            'wrong_page_impressions' => null,
            'groups' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $searchConsole
     * @return array<string, mixed>
     */
    private function targetPageCtr(array $searchConsole): array
    {
        $signals = $this->targetSignals($searchConsole, 'context_90d');

        if ($signals === null || ! is_array($signals['target_pages'] ?? null)) {
            return [
                'available' => false,
                'status' => 'unavailable',
                'observed_status' => 'unavailable',
                'eligible_count' => null,
                'meeting_count' => null,
                'below_target_count' => null,
                'minimum_impressions' => self::TargetPageCtrImpressions,
                'target_ctr' => self::TargetPageCtr,
                'sample_limited' => true,
                'measurement_basis' => 'search_console_top_rows',
                'pages' => [],
            ];
        }

        $eligible = 0;
        $meeting = 0;
        $pages = [];
        $seen = [];

        foreach ($signals['target_pages'] as $candidate) {
            if (! is_array($candidate)
                || ! is_string($candidate['key'] ?? null)
                || ! in_array($candidate['key'], SeoTargetPageResolver::pageKeys(), true)
                || isset($seen[$candidate['key']])) {
                return $this->unavailableTargetPageCtr();
            }

            $metrics = $this->metrics($candidate);

            if ($metrics === null) {
                return $this->unavailableTargetPageCtr();
            }

            $seen[$candidate['key']] = true;

            if ($metrics['impressions'] < self::TargetPageCtrImpressions) {
                $pages[] = [
                    'key' => $candidate['key'],
                    'status' => 'insufficient_sample',
                    ...$metrics,
                ];

                continue;
            }

            $eligible++;
            $meeting += $metrics['ctr'] >= self::TargetPageCtr ? 1 : 0;
            $pages[] = [
                'key' => $candidate['key'],
                'status' => $metrics['ctr'] >= self::TargetPageCtr ? 'met' : 'not_met',
                ...$metrics,
            ];
        }

        return [
            'available' => true,
            'status' => $eligible === 0 ? 'insufficient_sample' : 'directional',
            'observed_status' => $eligible === 0 ? 'insufficient_sample' : ($meeting === $eligible ? 'met' : 'not_met'),
            'sample_limited' => true,
            'measurement_basis' => 'search_console_top_rows',
            'eligible_count' => $eligible,
            'meeting_count' => $meeting,
            'below_target_count' => $eligible - $meeting,
            'minimum_impressions' => self::TargetPageCtrImpressions,
            'target_ctr' => self::TargetPageCtr,
            'pages' => $pages,
        ];
    }

    /** @return array<string, mixed> */
    private function unavailableTargetPageCtr(): array
    {
        return [
            'available' => false,
            'status' => 'unavailable',
            'observed_status' => 'unavailable',
            'sample_limited' => true,
            'measurement_basis' => 'search_console_top_rows',
            'eligible_count' => null,
            'meeting_count' => null,
            'below_target_count' => null,
            'minimum_impressions' => self::TargetPageCtrImpressions,
            'target_ctr' => self::TargetPageCtr,
            'pages' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $searchConsole
     * @return array<string, mixed>
     */
    private function comparison(
        array $searchConsole,
        string $metricKey,
        string $comparisonMetric,
        string $positiveStatus,
        string $negativeStatus,
    ): array {
        $current = $this->targetMetric($searchConsole, 'current', $metricKey);
        $previous = $this->targetMetric($searchConsole, 'previous', $metricKey);

        if ($current === null || $previous === null) {
            return [
                'available' => false,
                'status' => 'unavailable',
                'observed_status' => 'unavailable',
                'sample_limited' => true,
                'measurement_basis' => 'search_console_top_rows',
                'current' => null,
                'previous' => null,
                'absolute_change' => null,
                'relative_change' => null,
            ];
        }

        $currentValue = $current[$comparisonMetric];
        $previousValue = $previous[$comparisonMetric];
        $absolute = $currentValue - $previousValue;
        $direction = $previousValue === 0
            ? 'no_baseline'
            : ($currentValue >= $previousValue && ($positiveStatus !== 'growth' || $currentValue > $previousValue)
                ? $positiveStatus
                : $negativeStatus);

        return [
            'available' => true,
            'status' => $previousValue === 0 ? 'no_baseline' : 'directional',
            'observed_status' => $direction,
            'sample_limited' => true,
            'measurement_basis' => 'search_console_top_rows',
            'current' => $current,
            'previous' => $previous,
            'absolute_change' => $absolute,
            'relative_change' => $previousValue === 0 ? null : round($absolute / $previousValue, 4),
        ];
    }

    /**
     * @param  array<string, mixed>  $ga4
     * @return array<string, mixed>
     */
    private function organicConsultations(array $ga4): array
    {
        $periods = [];
        $hasAvailablePeriod = false;

        foreach (['current', 'previous', 'context_90d'] as $period) {
            $window = is_array($ga4[$period] ?? null) ? $ga4[$period] : [];
            $source = is_array($window['organic_consultation_submissions'] ?? null)
                ? $window['organic_consultation_submissions']
                : [];
            $total = $this->count($source['total'] ?? null);
            $available = ($source['available'] ?? null) === true && $total !== null;
            $hasAvailablePeriod = $hasAvailablePeriod || $available;
            $periods[$period] = ['available' => $available, 'total' => $available ? $total : null];
        }

        return [
            'available' => $hasAvailablePeriod,
            'status' => $hasAvailablePeriod ? 'tracking' : 'unavailable',
            ...$periods,
        ];
    }

    /**
     * @param  array<string, mixed>  $searchConsole
     * @return array<string, mixed>
     */
    private function localeBreakdown(array $searchConsole): array
    {
        $periods = [];
        $hasAvailablePeriod = false;

        foreach (['current', 'previous', 'context_90d'] as $period) {
            $window = is_array($searchConsole[$period] ?? null) ? $searchConsole[$period] : [];
            $breakdown = is_array($window['locale_performance'] ?? null) ? $window['locale_performance'] : [];
            $rows = ($breakdown['available'] ?? null) === true && is_array($breakdown['rows'] ?? null)
                ? $breakdown['rows']
                : null;

            if ($rows === null || ($breakdown['method'] ?? null) !== 'canonical_url') {
                $periods[$period] = ['available' => false, 'ar' => null, 'en' => null];

                continue;
            }

            $localized = ['ar' => $this->zeroMetrics(), 'en' => $this->zeroMetrics()];

            foreach ($rows as $candidate) {
                if (! is_array($candidate)
                    || ! is_string($candidate['locale'] ?? null)
                    || ! array_key_exists($candidate['locale'], $localized)) {
                    continue;
                }

                $metrics = $this->metrics($candidate);

                if ($metrics === null) {
                    continue;
                }

                $localized[$candidate['locale']] = $metrics;
            }

            $hasAvailablePeriod = true;
            $periods[$period] = ['available' => true, ...$localized];
        }

        return [
            'available' => $hasAvailablePeriod,
            'method' => 'canonical_url',
            ...$periods,
        ];
    }

    /**
     * @param  array<string, mixed>  $searchConsole
     * @return array<string, mixed>|null
     */
    private function targetSignals(array $searchConsole, string $period): ?array
    {
        $window = is_array($searchConsole[$period] ?? null) ? $searchConsole[$period] : [];
        $signals = is_array($window['seo_targets'] ?? null) ? $window['seo_targets'] : null;

        return $signals !== null && ($signals['available'] ?? null) === true ? $signals : null;
    }

    /**
     * @param  array<string, mixed>  $searchConsole
     * @return array{clicks: int, impressions: int, ctr: float, position: float}|null
     */
    private function targetMetric(array $searchConsole, string $period, string $metricKey): ?array
    {
        $signals = $this->targetSignals($searchConsole, $period);

        return $signals === null ? null : $this->metrics($signals[$metricKey] ?? null);
    }

    /**
     * @return array{clicks: int, impressions: int, ctr: float, position: float}|null
     */
    private function metrics(mixed $candidate): ?array
    {
        if (! is_array($candidate)) {
            return null;
        }

        $clicks = $this->count($candidate['clicks'] ?? null);
        $impressions = $this->count($candidate['impressions'] ?? null);
        $ctr = $this->rate($candidate['ctr'] ?? null);
        $position = $this->nonNegativeFloat($candidate['position'] ?? null);

        if ($clicks === null || $impressions === null || $ctr === null || $position === null) {
            return null;
        }

        return compact('clicks', 'impressions', 'ctr', 'position');
    }

    /** @return array{clicks: int, impressions: int, ctr: float, position: float} */
    private function zeroMetrics(): array
    {
        return ['clicks' => 0, 'impressions' => 0, 'ctr' => 0.0, 'position' => 0.0];
    }

    private function count(mixed $value): ?int
    {
        return is_int($value) && $value >= 0 ? $value : null;
    }

    private function rate(mixed $value): ?float
    {
        return (is_int($value) || is_float($value))
            && is_finite((float) $value)
            && $value >= 0
            && $value <= 1
            ? (float) $value
            : null;
    }

    private function nonNegativeFloat(mixed $value): ?float
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value) && $value >= 0
            ? (float) $value
            : null;
    }

    /** @param  list<string>  $statuses */
    private function overallStatus(array $statuses): string
    {
        if (count(array_filter($statuses, fn (string $status): bool => $status !== 'unavailable')) === 0) {
            return 'unavailable';
        }

        if (in_array('not_met', $statuses, true) || in_array('declined', $statuses, true)) {
            return 'needs_attention';
        }

        if ($statuses === ['met', 'met', 'growth', 'maintained']) {
            return 'on_track';
        }

        return 'partial';
    }
}
