<?php

namespace Tests\Unit\Services\WebsitePerformance;

use App\Services\WebsitePerformance\SeoTargetPageResolver;
use App\Services\WebsitePerformance\SeoTargetScorecard;
use PHPUnit\Framework\TestCase;

class SeoTargetScorecardTest extends TestCase
{
    public function test_it_evaluates_the_approved_ninety_day_targets_from_aggregate_signals(): void
    {
        $scorecard = (new SeoTargetScorecard)->build($this->sources());

        $this->assertSame('partial', $scorecard['status']);
        $this->assertSame('directional', $scorecard['query_groups']['status']);
        $this->assertSame('met', $scorecard['query_groups']['observed_status']);
        $this->assertSame(6, count($scorecard['query_groups']['groups']));
        $this->assertSame(SeoTargetPageResolver::queryGroupKeys(), array_column($scorecard['query_groups']['groups'], 'key'));
        $this->assertSame(2, $scorecard['query_groups']['top_10_count']);
        $this->assertSame(3, $scorecard['query_groups']['additional_top_20_count']);
        $this->assertSame('directional', $scorecard['target_page_ctr']['status']);
        $this->assertSame('met', $scorecard['target_page_ctr']['observed_status']);
        $this->assertSame(1, $scorecard['target_page_ctr']['eligible_count']);
        $this->assertSame('directional', $scorecard['saudi_gcc_non_brand']['status']);
        $this->assertSame('growth', $scorecard['saudi_gcc_non_brand']['observed_status']);
        $this->assertSame('directional', $scorecard['international_english']['status']);
        $this->assertSame('maintained', $scorecard['international_english']['observed_status']);
        $this->assertSame(3, $scorecard['organic_consultations']['context_90d']['total']);
        $this->assertSame('canonical_url', $scorecard['locale_breakdown']['method']);
        $this->assertSame(180, $scorecard['locale_breakdown']['context_90d']['en']['impressions']);
        $this->assertTrue($scorecard['measurement']['search_console_sample_limited']);
    }

    public function test_it_does_not_score_low_volume_queries_or_pages_before_the_threshold(): void
    {
        $sources = $this->sources();
        $sources['search_console']['context_90d']['seo_targets']['query_groups'] = array_map(
            fn (array $row): array => [...$row, 'impressions' => 29, 'clicks' => 1, 'ctr' => 1 / 29],
            $sources['search_console']['context_90d']['seo_targets']['query_groups'],
        );
        $sources['search_console']['context_90d']['seo_targets']['target_pages'] = [
            ['key' => 'services', ...$this->metrics(4, 99, 4.0)],
        ];

        $scorecard = (new SeoTargetScorecard)->build($sources);

        $this->assertSame('insufficient_sample', $scorecard['query_groups']['status']);
        $this->assertSame(0, $scorecard['query_groups']['eligible_count']);
        $this->assertSame('insufficient_sample', $scorecard['target_page_ctr']['status']);
        $this->assertSame(0, $scorecard['target_page_ctr']['eligible_count']);
        $this->assertSame('partial', $scorecard['status']);
    }

    public function test_it_surfaces_wrong_page_impressions_without_counting_them_toward_the_query_target(): void
    {
        $sources = $this->sources();
        $sources['search_console']['context_90d']['seo_targets']['query_groups'][0] = [
            ...$sources['search_console']['context_90d']['seo_targets']['query_groups'][0],
            ...$this->metrics(0, 0, 0.0),
            'wrong_page_clicks' => 4,
            'wrong_page_impressions' => 80,
        ];

        $scorecard = (new SeoTargetScorecard)->build($sources);

        $this->assertSame('directional', $scorecard['query_groups']['status']);
        $this->assertSame(80, $scorecard['query_groups']['wrong_page_impressions']);
        $this->assertSame('insufficient_sample', $scorecard['query_groups']['groups'][0]['status']);
    }

    public function test_it_marks_missing_or_malformed_target_inputs_unavailable(): void
    {
        $sources = $this->sources();
        $sources['search_console']['context_90d']['seo_targets']['query_groups'][0]['position'] = -1;
        unset($sources['search_console']['previous']['seo_targets']);
        unset($sources['ga4']['current']['organic_consultation_submissions']);

        $scorecard = (new SeoTargetScorecard)->build($sources);

        $this->assertSame('unavailable', $scorecard['query_groups']['status']);
        $this->assertSame('unavailable', $scorecard['saudi_gcc_non_brand']['status']);
        $this->assertTrue($scorecard['organic_consultations']['available']);
        $this->assertFalse($scorecard['organic_consultations']['current']['available']);
    }

    public function test_it_counts_top_ten_groups_toward_the_additional_top_twenty_goal(): void
    {
        $sources = $this->sources();

        foreach ($sources['search_console']['context_90d']['seo_targets']['query_groups'] as $index => $group) {
            $sources['search_console']['context_90d']['seo_targets']['query_groups'][$index] = [
                ...$group,
                'position' => $index < 5 ? 7.0 : 21.0,
            ];
        }

        $scorecard = (new SeoTargetScorecard)->build($sources);

        $this->assertSame(5, $scorecard['query_groups']['top_10_count']);
        $this->assertSame(3, $scorecard['query_groups']['additional_top_20_count']);
        $this->assertSame('met', $scorecard['query_groups']['observed_status']);
    }

    public function test_it_compares_international_english_retention_by_clicks(): void
    {
        $sources = $this->sources();
        $sources['search_console']['current']['seo_targets']['international_english'] = $this->metrics(6, 180, 7.0);
        $sources['search_console']['previous']['seo_targets']['international_english'] = $this->metrics(7, 110, 7.5);

        $scorecard = (new SeoTargetScorecard)->build($sources);

        $this->assertSame('declined', $scorecard['international_english']['observed_status']);
        $this->assertSame(-1, $scorecard['international_english']['absolute_change']);
        $this->assertSame(-0.1429, $scorecard['international_english']['relative_change']);
    }

    /** @return array<string, array<string, mixed>> */
    private function sources(): array
    {
        $assignments = SeoTargetPageResolver::assignments();
        $groups = array_map(
            fn (string $key, array $metrics): array => [
                'key' => $key,
                'assigned_page_keys' => $assignments[$key],
                ...$metrics,
                'wrong_page_clicks' => 0,
                'wrong_page_impressions' => 0,
            ],
            array_keys($assignments),
            [
                $this->metrics(9, 90, 7.0),
                $this->metrics(6, 60, 9.5),
                $this->metrics(4, 80, 12.0),
                $this->metrics(4, 70, 15.0),
                $this->metrics(2, 40, 19.5),
                $this->metrics(1, 35, 24.0),
            ],
        );
        $locale = [
            'available' => true,
            'method' => 'canonical_url',
            'rows' => [
                ['locale' => 'ar', ...$this->metrics(12, 140, 8.0)],
                ['locale' => 'en', ...$this->metrics(14, 180, 7.0)],
            ],
        ];

        return [
            'search_console' => [
                'current' => [
                    'seo_targets' => [
                        'available' => true,
                        'saudi_gcc_non_brand' => $this->metrics(12, 120, 8.0),
                        'international_english' => $this->metrics(8, 110, 7.0),
                    ],
                    'locale_performance' => $locale,
                ],
                'previous' => [
                    'seo_targets' => [
                        'available' => true,
                        'saudi_gcc_non_brand' => $this->metrics(10, 100, 8.5),
                        'international_english' => $this->metrics(7, 110, 7.5),
                    ],
                    'locale_performance' => $locale,
                ],
                'context_90d' => [
                    'seo_targets' => [
                        'available' => true,
                        'query_groups' => $groups,
                        'target_pages' => [
                            ['key' => 'services', ...$this->metrics(4, 100, 8.0)],
                            ['key' => 'ai_governance', ...$this->metrics(3, 60, 12.0)],
                        ],
                    ],
                    'locale_performance' => $locale,
                ],
            ],
            'ga4' => [
                'current' => ['organic_consultation_submissions' => ['available' => true, 'total' => 2]],
                'previous' => ['organic_consultation_submissions' => ['available' => true, 'total' => 1]],
                'context_90d' => ['organic_consultation_submissions' => ['available' => true, 'total' => 3]],
            ],
        ];
    }

    /** @return array{clicks: int, impressions: int, ctr: float, position: float} */
    private function metrics(int $clicks, int $impressions, float $position): array
    {
        return [
            'clicks' => $clicks,
            'impressions' => $impressions,
            'ctr' => $impressions === 0 ? 0.0 : $clicks / $impressions,
            'position' => $position,
        ];
    }
}
