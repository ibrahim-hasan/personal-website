<?php

namespace Tests\Feature\Console\Commands;

use App\Services\Operations\ReleaseReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ReleaseCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_checks_database_and_migrations_and_skips_release_only_dependencies_locally(): void
    {
        config()->set('operations.release_environment', false);

        $this->artisan('app:release-check')
            ->expectsOutputToContain('Database: ready.')
            ->expectsOutputToContain('Migrations: ready.')
            ->expectsOutputToContain('Redis: not required in this environment.')
            ->expectsOutputToContain('Horizon: not required in this environment.')
            ->assertSuccessful();
    }

    public function test_it_returns_a_redacted_failure_when_a_required_check_fails(): void
    {
        $readiness = Mockery::mock(ReleaseReadiness::class);
        $report = [
            'database' => ['required' => true, 'passed' => false],
        ];
        $readiness->shouldReceive('report')->once()->andReturn($report);
        $readiness->shouldReceive('passes')->once()->with($report)->andReturnFalse();
        $this->app->instance(ReleaseReadiness::class, $readiness);

        $this->artisan('app:release-check')
            ->expectsOutputToContain('Database: unavailable.')
            ->assertFailed();
    }
}
