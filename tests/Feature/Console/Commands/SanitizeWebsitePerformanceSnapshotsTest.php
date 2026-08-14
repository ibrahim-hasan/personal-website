<?php

namespace Tests\Feature\Console\Commands;

use App\Services\WebsitePerformance\WebsitePerformanceSnapshotStore;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SanitizeWebsitePerformanceSnapshotsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config()->set('services.website_performance.snapshot_disk', 'local');
        config()->set('services.website_performance.snapshot_directory', 'website-performance');
        config()->set('filesystems.disks.local.root', storage_path('app/private'));
    }

    public function test_it_rewrites_legacy_snapshots_without_touching_sibling_private_files(): void
    {
        $credentialPath = 'google/reporting-service-account.json';
        $credentialContents = '{"private_key":"server-only-secret"}';
        Storage::disk('local')->put($credentialPath, $credentialContents);

        $legacyPath = 'website-performance/2026/08/09/legacy-raw.json';
        Storage::disk('local')->put($legacyPath, json_encode([
            'schema_version' => 1,
            'generated_at' => '2026-08-09T09:00:00+00:00',
            'timezone' => 'Asia/Riyadh',
            'data_cutoff' => '2026-08-09',
            'status' => 'partial',
            'periods' => [
                'current' => ['start' => '2026-07-13', 'end' => '2026-08-09'],
            ],
            'sources' => [
                'search_console' => [
                    'status' => 'partial',
                    'warnings' => ['url_inspection_request_unavailable', 'raw warning text'],
                    'current' => [
                        'totals' => ['clicks' => 21, 'impressions' => 150],
                        'queries' => ['rows' => [['query' => 'private@example.test']]],
                        'pages' => ['rows' => [['page' => 'https://ibrahimhasan.net/private-page']]],
                        'opaque_token' => 'server-only-token',
                    ],
                    'url_inspection' => [
                        'available' => true,
                        'results' => [[
                            'url' => 'https://ibrahimhasan.net/private-inspection-url',
                            'user_canonical' => 'https://ibrahimhasan.net/private-user-canonical',
                            'google_canonical' => 'https://ibrahimhasan.net/private-google-canonical',
                            'indexing_state' => 'INDEXING_ALLOWED',
                            'robots_txt_state' => 'ALLOWED',
                            'page_fetch_state' => 'SUCCESSFUL',
                            'verdict' => 'PASS',
                        ]],
                    ],
                ],
            ],
            'quality' => [
                'flags' => [[
                    'source' => 'ga4',
                    'period' => 'current',
                    'metric' => 'sessions',
                    'status' => 'sufficient',
                    'observed' => 15,
                    'threshold' => 10,
                ]],
            ],
        ], JSON_THROW_ON_ERROR));
        Storage::disk('local')->put('website-performance/2026/08/10/malformed.json', '{not-json');
        Storage::disk('local')->put('website-performance/2026/08/08/unsafe.json', json_encode([
            'schema_version' => 1,
            'generated_at' => '2026-08-08T09:00:00+00:00',
            'status' => 'partial',
            'sources' => [
                'first_party' => [
                    'status' => 'partial',
                    'current' => [
                        'email' => 'private@example.test',
                        'inquiries' => ['total' => 8],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $safePath = app(WebsitePerformanceSnapshotStore::class)->persist([
            'schema_version' => 1,
            'generated_at' => '2026-08-11T09:00:00+00:00',
            'status' => 'unavailable',
            'sources' => [],
        ]);
        $safeContents = Storage::disk('local')->get($safePath);

        $this->artisan('website:performance-sanitize-snapshots')
            ->expectsOutputToContain('4 scanned; 3 rewritten; 1 already safe; 2 unavailable.')
            ->assertSuccessful();

        $sanitizedContents = Storage::disk('local')->get($legacyPath);
        $sanitized = json_decode($sanitizedContents, true, 512, JSON_THROW_ON_ERROR);
        $malformed = json_decode(Storage::disk('local')->get('website-performance/2026/08/10/malformed.json'), true, 512, JSON_THROW_ON_ERROR);
        $unsafe = json_decode(Storage::disk('local')->get('website-performance/2026/08/08/unsafe.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(1, $sanitized['snapshot_schema_version']);
        $this->assertSame(21, $sanitized['sources']['search_console']['current']['clicks']);
        $this->assertSame(['url_inspection_request_unavailable'], $sanitized['sources']['search_console']['warning_codes']);
        $this->assertSame('issues_detected', $sanitized['quality']['flags'][1]['status']);
        $this->assertSame('unavailable', $malformed['status']);
        $this->assertSame(['privacy' => 'aggregate_only'], $malformed['meta']);
        $this->assertSame('unavailable', $unsafe['status']);
        $this->assertStringNotContainsString('private@example.test', json_encode($unsafe, JSON_THROW_ON_ERROR));

        foreach ([
            'private@example.test',
            'https://ibrahimhasan.net/private-page',
            'server-only-token',
            'raw warning text',
            'https://ibrahimhasan.net/private-inspection-url',
            'https://ibrahimhasan.net/private-user-canonical',
            'https://ibrahimhasan.net/private-google-canonical',
        ] as $unsafeValue) {
            $this->assertStringNotContainsString($unsafeValue, $sanitizedContents);
        }

        $this->assertStringNotContainsString('"queries"', $sanitizedContents);
        $this->assertStringNotContainsString('"pages"', $sanitizedContents);
        $this->assertStringNotContainsString('"url_inspection"', $sanitizedContents);
        $this->assertSame($credentialContents, Storage::disk('local')->get($credentialPath));
        $this->assertSame($safeContents, Storage::disk('local')->get($safePath));
    }
}
