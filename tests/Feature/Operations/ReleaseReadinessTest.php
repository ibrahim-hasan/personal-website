<?php

namespace Tests\Feature\Operations;

use App\Services\Operations\ReleaseReadiness;
use App\Services\Operations\SchedulerHeartbeat;
use Illuminate\Contracts\Redis\Connection as RedisConnection;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Mockery;
use Tests\TestCase;

class ReleaseReadinessTest extends TestCase
{
    use RefreshDatabase;

    private string $revisionPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->revisionPath = storage_path('framework/testing/release-readiness-revision');
        app(Filesystem::class)->ensureDirectoryExists(dirname($this->revisionPath));
        $this->configureReleaseEnvironment();
    }

    protected function tearDown(): void
    {
        app(Filesystem::class)->delete($this->revisionPath);

        parent::tearDown();
    }

    public function test_a_release_environment_requires_a_fresh_scheduler_heartbeat(): void
    {
        config()->set('cache.default', 'array');

        $readiness = app(ReleaseReadiness::class);

        $this->assertTrue($readiness->report()['scheduler']['required']);
        $this->assertFalse($readiness->report()['scheduler']['passed']);

        app(SchedulerHeartbeat::class)->record();

        $this->assertTrue(app(ReleaseReadiness::class)->report()['scheduler']['passed']);
    }

    public function test_it_accepts_a_complete_redacted_release_environment_without_exposing_any_values(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('.healthcheck', 'ready');
        app(Filesystem::class)->put($this->revisionPath, str_repeat('a', 40));
        $this->bindHealthyRedis();
        $this->bindRunningHorizon();
        $this->bindFreshSchedulerHeartbeat();

        $report = app(ReleaseReadiness::class)->report();

        foreach ($report as $check) {
            $this->assertTrue($check['required']);
            $this->assertTrue($check['passed']);
        }
        $this->assertTrue(app(ReleaseReadiness::class)->passes($report));
    }

    private function configureReleaseEnvironment(): void
    {
        config()->set('operations.release_environment', true);
        config()->set('operations.required_storage_disks', ['public']);
        config()->set('operations.storage_probe_path', '.healthcheck');
        config()->set('operations.build_revision_path', $this->revisionPath);
        config()->set('operations.readiness.header', 'X-Readiness-Key');
        config()->set('operations.readiness.secret', 'server-only-readiness-secret');
        config()->set('app.debug', false);
        config()->set('queue.default', 'redis');
        config()->set('cache.default', 'redis');
        config()->set('queue.connections.redis.retry_after', 1800);
        config()->set('services.elevenlabs.job_timeout', 1560);
        config()->set('horizon.defaults.supervisor-default', [
            'connection' => 'redis',
            'queue' => ['default'],
        ]);
        config()->set('horizon.defaults.supervisor-article-audio', [
            'connection' => 'redis',
            'queue' => ['article-audio'],
            'timeout' => 1620,
        ]);
    }

    public function test_a_release_environment_requires_a_readiness_header_and_secret(): void
    {
        config()->set('operations.readiness.secret', '');

        $report = app(ReleaseReadiness::class)->report();

        $this->assertTrue($report['configuration']['required']);
        $this->assertFalse($report['configuration']['passed']);
    }

    private function bindHealthyRedis(): void
    {
        $connection = Mockery::mock(RedisConnection::class);
        $connection->shouldReceive('command')->once()->with('ping')->andReturn('PONG');
        $redis = Mockery::mock(RedisFactory::class);
        $redis->shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $this->app->instance(RedisFactory::class, $redis);
    }

    private function bindRunningHorizon(): void
    {
        $horizon = Mockery::mock(MasterSupervisorRepository::class);
        $horizon->shouldReceive('names')->once()->andReturn(['horizon-test']);

        $this->app->instance(MasterSupervisorRepository::class, $horizon);
    }

    private function bindFreshSchedulerHeartbeat(): void
    {
        $heartbeat = Mockery::mock(SchedulerHeartbeat::class);
        $heartbeat->shouldReceive('isFresh')->once()->andReturnTrue();

        $this->app->instance(SchedulerHeartbeat::class, $heartbeat);
    }
}
