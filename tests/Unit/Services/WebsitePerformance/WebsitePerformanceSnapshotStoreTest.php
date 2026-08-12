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
