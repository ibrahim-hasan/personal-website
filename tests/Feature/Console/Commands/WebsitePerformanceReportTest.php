<?php

namespace Tests\Feature\Console\Commands;

use App\Services\WebsitePerformance\WebsitePerformanceReporter;
use App\Services\WebsitePerformance\WebsitePerformanceSnapshotStore;
use App\Services\WebsitePerformance\WebsitePerformanceSourceException;
use Carbon\CarbonImmutable;
use Mockery;
use Tests\TestCase;

class WebsitePerformanceReportTest extends TestCase
{
    public function test_it_uses_the_three_day_riyadh_cutoff_and_returns_a_partial_exit_code(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-12 14:30:00', 'Asia/Riyadh'));
        config()->set('services.website_performance.timezone', 'Asia/Riyadh');
        $reporter = Mockery::mock(WebsitePerformanceReporter::class);
        $report = $this->report('partial');
        $reporter->shouldReceive('report')
            ->once()
            ->withArgs(function (int $days, CarbonImmutable $endDate, string $timezone): bool {
                return $days === 28
                    && $endDate->toDateString() === '2026-08-09'
                    && $timezone === 'Asia/Riyadh';
            })
            ->andReturn($report);
        $reporter->shouldReceive('exitCode')->once()->with($report)->andReturn(2);
        $snapshots = Mockery::mock(WebsitePerformanceSnapshotStore::class);
        $snapshots->shouldNotReceive('persist');
        $this->app->instance(WebsitePerformanceReporter::class, $reporter);
        $this->app->instance(WebsitePerformanceSnapshotStore::class, $snapshots);

        $this->artisan('website:performance-report', ['--no-snapshot' => true])
            ->expectsOutputToContain('"snapshot":{"status":"skipped"}')
            ->assertExitCode(2);
    }

    public function test_it_writes_a_private_snapshot_for_a_usable_report(): void
    {
        $reporter = Mockery::mock(WebsitePerformanceReporter::class);
        $report = $this->report('ok');
        $reporter->shouldReceive('report')->once()->andReturn($report);
        $reporter->shouldReceive('exitCode')->once()->with($report)->andReturn(0);
        $snapshots = Mockery::mock(WebsitePerformanceSnapshotStore::class);
        $snapshots->shouldReceive('persist')
            ->once()
            ->with($report)
            ->andReturn('website-performance/2026/08/09/report.json');
        $this->app->instance(WebsitePerformanceReporter::class, $reporter);
        $this->app->instance(WebsitePerformanceSnapshotStore::class, $snapshots);

        $this->artisan('website:performance-report', [
            '--end-date' => '2026-08-09',
        ])
            ->expectsOutputToContain('"snapshot":{"status":"written","path":"website-performance/2026/08/09/report.json"}')
            ->assertSuccessful();
    }

    public function test_a_snapshot_failure_downgrades_a_usable_report_to_partial(): void
    {
        $reporter = Mockery::mock(WebsitePerformanceReporter::class);
        $report = $this->report('ok');
        $reporter->shouldReceive('report')->once()->andReturn($report);
        $reporter->shouldReceive('exitCode')->once()->with($report)->andReturn(0);
        $snapshots = Mockery::mock(WebsitePerformanceSnapshotStore::class);
        $snapshots->shouldReceive('persist')
            ->once()
            ->andThrow(new WebsitePerformanceSourceException('snapshot_privacy_unavailable'));
        $this->app->instance(WebsitePerformanceReporter::class, $reporter);
        $this->app->instance(WebsitePerformanceSnapshotStore::class, $snapshots);

        $this->artisan('website:performance-report', [
            '--end-date' => '2026-08-09',
        ])
            ->expectsOutputToContain('"warning":"snapshot_privacy_unavailable"')
            ->assertExitCode(2);
    }

    public function test_it_snapshots_an_unavailable_report_without_changing_its_failure_exit_code(): void
    {
        $reporter = Mockery::mock(WebsitePerformanceReporter::class);
        $report = $this->report('unavailable');
        $reporter->shouldReceive('report')->once()->andReturn($report);
        $reporter->shouldReceive('exitCode')->once()->with($report)->andReturn(1);
        $snapshots = Mockery::mock(WebsitePerformanceSnapshotStore::class);
        $snapshots->shouldReceive('persist')
            ->once()
            ->with($report)
            ->andReturn('website-performance/2026/08/09/unavailable.json');
        $this->app->instance(WebsitePerformanceReporter::class, $reporter);
        $this->app->instance(WebsitePerformanceSnapshotStore::class, $snapshots);

        $this->artisan('website:performance-report', [
            '--end-date' => '2026-08-09',
        ])
            ->expectsOutputToContain('"snapshot":{"status":"written","path":"website-performance/2026/08/09/unavailable.json"}')
            ->assertExitCode(1);
    }

    public function test_it_returns_a_redacted_failure_for_invalid_period_options(): void
    {
        $reporter = Mockery::mock(WebsitePerformanceReporter::class);
        $reporter->shouldNotReceive('report');
        $snapshots = Mockery::mock(WebsitePerformanceSnapshotStore::class);
        $snapshots->shouldNotReceive('persist');
        $this->app->instance(WebsitePerformanceReporter::class, $reporter);
        $this->app->instance(WebsitePerformanceSnapshotStore::class, $snapshots);

        $this->artisan('website:performance-report', ['--days' => '0', '--no-snapshot' => true])
            ->expectsOutputToContain('"warnings":["invalid_days"]')
            ->assertFailed();
    }

    /**
     * @return array<string, mixed>
     */
    private function report(string $status): array
    {
        return [
            'schema_version' => 1,
            'generated_at' => '2026-08-09T09:00:00+00:00',
            'timezone' => 'Asia/Riyadh',
            'data_cutoff' => '2026-08-09',
            'status' => $status,
            'periods' => [],
            'sources' => [],
            'quality' => ['source_statuses' => [], 'flags' => []],
        ];
    }
}
