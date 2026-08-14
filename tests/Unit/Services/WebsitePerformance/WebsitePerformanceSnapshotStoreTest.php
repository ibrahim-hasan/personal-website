<?php

namespace Tests\Unit\Services\WebsitePerformance;

use App\Services\WebsitePerformance\WebsitePerformanceSnapshotStore;
use App\Services\WebsitePerformance\WebsitePerformanceSourceException;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsitePerformanceSnapshotStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config()->set('services.website_performance.snapshot_disk', 'local');
        config()->set('services.website_performance.snapshot_directory', 'website-performance');
        config()->set('filesystems.disks.local.root', storage_path('app/private'));
    }

    public function test_it_persists_only_private_aggregate_report_snapshots(): void
    {
        $path = $this->store()->persist($this->report());

        $this->assertMatchesRegularExpression(
            '#\Awebsite-performance/2026/08/09/20260809T090000\.000000Z-[0-9a-f-]{36}\.json\z#',
            $path,
        );
        Storage::disk('local')->assertExists($path);
        $contents = Storage::disk('local')->get($path);
        $this->assertStringContainsString('aggregate_only', $contents);
        $this->assertStringNotContainsString('private@example.test', $contents);
    }

    public function test_it_refuses_public_or_non_private_storage_disks(): void
    {
        config()->set('services.website_performance.snapshot_disk', 'public');

        $this->assertSnapshotException(
            fn (): string => $this->store()->persist($this->report()),
            'snapshot_configuration_unavailable',
        );
    }

    public function test_it_refuses_snapshot_directories_outside_its_dedicated_private_prefix(): void
    {
        foreach (['.', 'google', '../website-performance', 'website-performance/../../google'] as $directory) {
            config()->set('services.website_performance.snapshot_directory', $directory);

            $this->assertSnapshotException(
                fn (): string => $this->store()->persist($this->report()),
                'snapshot_configuration_unavailable',
            );
        }
    }

    public function test_it_never_prunes_sibling_private_files(): void
    {
        $credentialPath = 'google/reporting-service-account.json';
        Storage::disk('local')->put($credentialPath, '{"private_key":"not-a-real-key"}');
        touch(Storage::disk('local')->path($credentialPath), strtotime('2025-07-08 00:00:00 UTC'));

        $this->store()->persist($this->report());

        Storage::disk('local')->assertExists($credentialPath);
    }

    public function test_it_versions_same_second_snapshots_and_prunes_expired_reports(): void
    {
        $expiredPath = 'website-performance/2025/07/08/expired.json';
        Storage::disk('local')->put($expiredPath, '{}');
        touch(Storage::disk('local')->path($expiredPath), strtotime('2025-07-08 00:00:00 UTC'));

        $first = $this->store()->persist($this->report());
        $second = $this->store()->persist($this->report());

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertExists([$first, $second]);
        Storage::disk('local')->assertMissing($expiredPath);
    }

    public function test_it_rejects_personal_fields_identifiers_references_and_hashes(): void
    {
        foreach (['message', 'id', 'reference', 'hash', 'submission_id', 'email'] as $field) {
            $report = $this->report();
            $report['sources']['first_party']['current'][$field] = 'not-safe';

            $this->assertSnapshotException(
                fn (): string => $this->store()->persist($report),
                'snapshot_privacy_unavailable',
            );
        }
    }

    public function test_it_persists_a_strict_aggregate_projection_without_raw_dimensions_or_tokens(): void
    {
        $report = $this->report();
        $report['periods'] = [
            'current' => ['start' => '2026-07-13', 'end' => '2026-08-09'],
            'previous' => ['start' => '2026-06-15', 'end' => '2026-07-12'],
            'context_90d' => ['start' => '2026-05-12', 'end' => '2026-08-09'],
        ];
        $report['warnings'] = ['raw-top-level-warning'];
        $report['opaque_token'] = 'top-level-token';
        $report['sources'] = [
            'first_party' => [
                'status' => 'ok',
                'fresh_through' => '2026-08-09',
                'warnings' => ['first-party-warning-code'],
                'current' => [
                    'inquiries' => [
                        'total' => 4,
                        'responded' => 3,
                        'response_rate' => 0.75,
                        'by_service' => [['service_key' => 'private-service-key', 'count' => 4]],
                    ],
                ],
                'previous' => ['inquiries' => ['total' => 2, 'responded' => 1, 'response_rate' => 0.5]],
                'context_90d' => ['inquiries' => ['total' => 12, 'responded' => 10, 'response_rate' => 0.8333]],
            ],
            'ga4' => [
                'status' => 'partial',
                'fresh_through' => '2026-08-09',
                'warnings' => ['ga4-raw-warning-code'],
                'current' => [
                    'totals' => ['sessions' => 20, 'engagedSessions' => 15, 'eventCount' => 40],
                    'acquisition_channels' => ['rows' => [['channel' => 'Private channel label']]],
                    'landing_pages' => ['rows' => [['landing_page' => 'https://ibrahimhasan.net/private-landing-page']]],
                    'events' => [
                        'available' => true,
                        'rows' => [
                            ['event_name' => 'primary_cta_click', 'eventCount' => 2],
                            ['event_name' => 'consultation_form_start', 'eventCount' => 3],
                            ['event_name' => 'consultation_submit_success', 'eventCount' => 2],
                        ],
                    ],
                    'cta_funnel' => ['rows' => [['ui_location' => 'private-ui-location']]],
                    'segments' => ['page_type' => ['rows' => [['page_type' => 'private-page-type']]]],
                ],
                'previous' => ['totals' => ['sessions' => 10, 'engagedSessions' => 8, 'eventCount' => 20]],
                'context_90d' => ['totals' => ['sessions' => 50, 'engagedSessions' => 35, 'eventCount' => 100]],
            ],
            'search_console' => [
                'status' => 'ok',
                'fresh_through' => '2026-08-09',
                'warnings' => ['search-console-raw-warning-code'],
                'current' => [
                    'totals' => ['clicks' => 21, 'impressions' => 150, 'ctr' => 0.14, 'position' => 7.2],
                    'queries' => ['rows' => [['query' => 'private@example.test']]],
                    'pages' => ['rows' => [['page' => 'https://ibrahimhasan.net/private-page']]],
                    'countries' => ['rows' => [['country' => 'SA']]],
                    'devices' => ['rows' => [['device' => 'MOBILE']]],
                ],
                'previous' => ['totals' => ['clicks' => 15, 'impressions' => 100]],
                'context_90d' => ['totals' => ['clicks' => 60, 'impressions' => 400]],
                'url_inspection' => [
                    'results' => [[
                        'url' => 'https://ibrahimhasan.net/private-inspection-url',
                        'user_canonical' => 'https://ibrahimhasan.net/private-canonical',
                    ]],
                ],
            ],
        ];

        $path = $this->store()->persist($report);
        $contents = Storage::disk('local')->get($path);
        $snapshot = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $snapshot['snapshot_schema_version']);
        $this->assertSame(['privacy' => 'aggregate_only'], $snapshot['meta']);
        $this->assertSame(4, $snapshot['sources']['first_party']['current']['total']);
        $this->assertSame(2, $snapshot['sources']['ga4']['current']['cta_clicks']);
        $this->assertSame(21, $snapshot['sources']['search_console']['current']['clicks']);

        foreach ([
            'raw-top-level-warning',
            'top-level-token',
            'first-party-warning-code',
            'private-service-key',
            'Private channel label',
            'https://ibrahimhasan.net/private-landing-page',
            'primary_cta_click',
            'private-ui-location',
            'private-page-type',
            'private@example.test',
            'https://ibrahimhasan.net/private-page',
            'https://ibrahimhasan.net/private-inspection-url',
            'https://ibrahimhasan.net/private-canonical',
            'MOBILE',
        ] as $unsafeValue) {
            $this->assertStringNotContainsString($unsafeValue, $contents);
        }

        foreach (['"queries"', '"pages"', '"landing_pages"', '"url_inspection"', '"ui_location"', '"opaque_token"'] as $unsafeKey) {
            $this->assertStringNotContainsString($unsafeKey, $contents);
        }
    }

    public function test_it_preserves_only_allowlisted_diagnostics_and_aggregate_indexing_health(): void
    {
        $report = $this->report();
        $report['sources'] = [
            'first_party' => [
                'status' => 'partial',
                'warnings' => ['first_party_current_unavailable', 'private warning text'],
                'current' => ['inquiries' => ['total' => 2, 'responded' => 0, 'response_rate' => 0.0]],
            ],
            'ga4' => [
                'status' => 'partial',
                'warnings' => ['ga4_current_landing_pages_unavailable', 'ga4_context_90d_totals_unavailable', 'ga4 raw warning text'],
                'current' => ['totals' => ['sessions' => 3]],
            ],
            'search_console' => [
                'status' => 'partial',
                'warnings' => ['url_inspection_request_unavailable', 'search-console raw warning text'],
                'url_inspection' => [
                    'available' => true,
                    'results' => [[
                        'url' => 'https://ibrahimhasan.net/private-inspection-url',
                        'user_canonical' => 'https://ibrahimhasan.net/private-user-canonical',
                        'google_canonical' => 'https://ibrahimhasan.net/private-google-canonical',
                        'indexing_state' => 'BLOCKED_BY_META_TAG',
                        'robots_txt_state' => 'DISALLOWED',
                        'page_fetch_state' => 'SOFT_404',
                        'verdict' => 'FAIL',
                    ]],
                ],
            ],
        ];
        $report['quality'] = [
            'flags' => [
                [
                    'source' => 'ga4',
                    'period' => 'current',
                    'metric' => 'sessions',
                    'status' => 'sufficient',
                    'observed' => 3,
                    'threshold' => 999,
                ],
                [
                    'source' => 'search_console',
                    'period' => 'current',
                    'metric' => 'private_metric',
                    'status' => 'private_status',
                    'observed' => 99,
                    'threshold' => 1,
                ],
            ],
        ];

        $path = $this->store()->persist($report);
        $contents = Storage::disk('local')->get($path);
        $snapshot = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['first_party_current_unavailable'], $snapshot['sources']['first_party']['warning_codes']);
        $this->assertSame([
            'ga4_context_90d_totals_unavailable',
            'ga4_current_landing_pages_unavailable',
        ], $snapshot['sources']['ga4']['warning_codes']);
        $this->assertSame(['url_inspection_request_unavailable'], $snapshot['sources']['search_console']['warning_codes']);
        $this->assertSame([
            [
                'source' => 'ga4',
                'period' => 'current',
                'metric' => 'sessions',
                'status' => 'insufficient_sample',
                'observed' => 3,
                'threshold' => 10,
            ],
            [
                'source' => 'search_console',
                'period' => 'current',
                'metric' => 'canonical_mismatches',
                'status' => 'issues_detected',
                'observed' => 1,
                'threshold' => 0,
            ],
            [
                'source' => 'search_console',
                'period' => 'current',
                'metric' => 'indexing_issues',
                'status' => 'issues_detected',
                'observed' => 1,
                'threshold' => 0,
            ],
            [
                'source' => 'search_console',
                'period' => 'current',
                'metric' => 'robots_issues',
                'status' => 'issues_detected',
                'observed' => 1,
                'threshold' => 0,
            ],
            [
                'source' => 'search_console',
                'period' => 'current',
                'metric' => 'page_fetch_issues',
                'status' => 'issues_detected',
                'observed' => 1,
                'threshold' => 0,
            ],
            [
                'source' => 'search_console',
                'period' => 'current',
                'metric' => 'verdict_issues',
                'status' => 'issues_detected',
                'observed' => 1,
                'threshold' => 0,
            ],
        ], $snapshot['quality']['flags']);

        foreach ([
            'private warning text',
            'ga4 raw warning text',
            'search-console raw warning text',
            'private_metric',
            'private_status',
            'https://ibrahimhasan.net/private-inspection-url',
            'https://ibrahimhasan.net/private-user-canonical',
            'https://ibrahimhasan.net/private-google-canonical',
            'BLOCKED_BY_META_TAG',
            'DISALLOWED',
            'SOFT_404',
        ] as $unsafeValue) {
            $this->assertStringNotContainsString($unsafeValue, $contents);
        }
    }

    public function test_it_keeps_zero_and_one_response_rates_readable_after_json_serialization(): void
    {
        $report = $this->report();
        $report['sources'] = [
            'first_party' => [
                'status' => 'ok',
                'current' => ['inquiries' => ['total' => 2, 'responded' => 0, 'response_rate' => 0.0]],
                'previous' => ['inquiries' => ['total' => 2, 'responded' => 2, 'response_rate' => 1.0]],
            ],
        ];

        $path = $this->store()->persist($report);
        $contents = Storage::disk('local')->get($path);
        $summaries = $this->store()->summaries();

        $this->assertStringContainsString('"response_rate": 0.0', $contents);
        $this->assertStringContainsString('"response_rate": 1.0', $contents);
        $this->assertSame(0.0, $summaries['latest']['sources']['first_party']['current']['response_rate']);
        $this->assertSame(1.0, $summaries['latest']['sources']['first_party']['previous']['response_rate']);
    }

    public function test_it_derives_the_overall_status_from_sanitized_source_states(): void
    {
        $report = $this->report();
        $report['status'] = 'ok';
        $report['sources'] = [];

        $path = $this->store()->persist($report);
        $snapshot = json_decode(Storage::disk('local')->get($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('unavailable', $snapshot['status']);
        $this->assertSame('unavailable', $this->store()->summaries()['latest']['status']);
    }

    public function test_it_orders_report_history_by_generated_at_instead_of_file_modification_time(): void
    {
        $newerReport = $this->report();
        $newerReport['generated_at'] = '2026-08-10T09:00:00+00:00';
        $newerPath = $this->store()->persist($newerReport);

        $olderReport = $this->report();
        $olderPath = $this->store()->persist($olderReport);
        touch(Storage::disk('local')->path($olderPath), strtotime('2026-08-11 09:00:00 UTC'));

        $summaries = $this->store()->summaries();

        $this->assertSame('2026-08-10T09:00:00+00:00', $summaries['latest']['generated_at']);
        $this->assertSame('2026-08-10T09:00:00+00:00', $summaries['history'][0]['generated_at']);
        $this->assertSame('2026-08-09T09:00:00+00:00', $summaries['history'][1]['generated_at']);
        Storage::disk('local')->assertExists($newerPath);
    }

    public function test_raw_legacy_snapshots_are_never_rendered_as_report_summaries(): void
    {
        $legacyReport = $this->report();
        $legacyReport['sources']['search_console'] = [
            'status' => 'ok',
            'current' => [
                'totals' => ['clicks' => 21, 'impressions' => 150],
                'queries' => ['rows' => [['query' => 'private@example.test']]],
                'pages' => ['rows' => [['page' => 'https://ibrahimhasan.net/private-page']]],
            ],
        ];
        Storage::disk('local')->put(
            'website-performance/2026/08/10/legacy-raw-report.json',
            json_encode($legacyReport, JSON_THROW_ON_ERROR),
        );

        $summaries = $this->store()->summaries();

        $this->assertSame('empty', $summaries['state']);
        $this->assertNull($summaries['latest']);
        $this->assertSame([], $summaries['history']);
    }

    public function test_it_reads_only_whitelisted_aggregate_summary_fields_and_skips_unsafe_snapshots(): void
    {
        $report = $this->report();
        $report['periods'] = [
            'current' => ['start' => '2026-07-13', 'end' => '2026-08-09'],
            'previous' => ['start' => '2026-06-15', 'end' => '2026-07-12'],
            'context_90d' => ['start' => '2026-05-12', 'end' => '2026-08-09'],
        ];
        $report['sources'] = [
            'first_party' => [
                'status' => 'ok',
                'fresh_through' => '2026-08-09',
                'warnings' => [],
                'current' => ['inquiries' => ['total' => 4, 'responded' => 3, 'response_rate' => 0.75]],
                'previous' => ['inquiries' => ['total' => 2, 'responded' => 1, 'response_rate' => 0.5]],
                'context_90d' => ['inquiries' => ['total' => 12, 'responded' => 10, 'response_rate' => 0.8333]],
            ],
            'ga4' => [
                'status' => 'partial',
                'fresh_through' => '2026-08-09',
                'warnings' => ['ga4_current_landing_pages_unavailable'],
                'current' => [
                    'totals' => ['sessions' => 20, 'engagedSessions' => 15, 'eventCount' => 40],
                    'events' => [
                        'available' => true,
                        'rows' => [
                            ['event_name' => 'primary_cta_click', 'eventCount' => 2],
                            ['event_name' => 'service_cta_click', 'eventCount' => 1],
                            ['event_name' => 'consultation_form_start', 'eventCount' => 3],
                            ['event_name' => 'consultation_submit_success', 'eventCount' => 2],
                        ],
                    ],
                ],
                'previous' => ['totals' => ['sessions' => 10, 'engagedSessions' => 8, 'eventCount' => 20]],
                'context_90d' => ['totals' => ['sessions' => 50, 'engagedSessions' => 35, 'eventCount' => 100]],
            ],
            'search_console' => [
                'status' => 'ok',
                'fresh_through' => '2026-08-09',
                'warnings' => [],
                'current' => [
                    'totals' => ['clicks' => 21, 'impressions' => 150],
                    'queries' => ['rows' => [['query' => 'private@example.test']]],
                    'opaque_token' => 'server-only-secret',
                ],
                'previous' => ['totals' => ['clicks' => 15, 'impressions' => 100]],
                'context_90d' => ['totals' => ['clicks' => 60, 'impressions' => 400]],
            ],
        ];
        $this->store()->persist($report);

        $unsafeReport = $report;
        $unsafeReport['generated_at'] = '2026-08-10T09:00:00+00:00';
        $unsafeReport['sources']['first_party']['current']['email'] = 'private@example.test';
        Storage::disk('local')->put(
            'website-performance/2026/08/10/unsafe.json',
            json_encode($unsafeReport, JSON_THROW_ON_ERROR),
        );

        $summaries = $this->store()->summaries();

        $this->assertSame('ready', $summaries['state']);
        $this->assertCount(1, $summaries['history']);
        $this->assertSame($summaries['latest'], $summaries['history'][0]);
        $this->assertSame(4, $summaries['latest']['sources']['first_party']['current']['total']);
        $this->assertSame(3, $summaries['latest']['sources']['ga4']['current']['cta_clicks']);
        $this->assertSame(3, $summaries['latest']['sources']['ga4']['current']['form_starts']);
        $this->assertSame(2, $summaries['latest']['sources']['ga4']['current']['successful_submissions']);
        $this->assertSame(21, $summaries['latest']['sources']['search_console']['current']['clicks']);
        $this->assertArrayNotHasKey('queries', $summaries['latest']['sources']['search_console']['current']);
        $this->assertStringNotContainsString('private@example.test', json_encode($summaries, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('server-only-secret', json_encode($summaries, JSON_THROW_ON_ERROR));
    }

    private function store(): WebsitePerformanceSnapshotStore
    {
        return new WebsitePerformanceSnapshotStore(app(FilesystemFactory::class), app(Repository::class));
    }

    /**
     * @return array<string, mixed>
     */
    private function report(): array
    {
        return [
            'schema_version' => 1,
            'generated_at' => '2026-08-09T09:00:00+00:00',
            'status' => 'partial',
            'meta' => ['privacy' => 'aggregate_only'],
            'sources' => [
                'first_party' => [
                    'status' => 'partial',
                    'current' => ['inquiries' => ['total' => 2]],
                ],
            ],
        ];
    }

    private function assertSnapshotException(callable $callback, string $reason): void
    {
        try {
            $callback();
            $this->fail('The unsafe snapshot must be rejected.');
        } catch (WebsitePerformanceSourceException $exception) {
            $this->assertSame($reason, $exception->reason);
        }
    }
}
