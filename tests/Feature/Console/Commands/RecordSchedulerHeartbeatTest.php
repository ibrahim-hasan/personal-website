<?php

namespace Tests\Feature\Console\Commands;

use App\Services\Operations\SchedulerHeartbeat;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class RecordSchedulerHeartbeatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_records_a_scheduler_heartbeat(): void
    {
        $this->artisan('app:record-scheduler-heartbeat')
            ->expectsOutputToContain('Scheduler heartbeat recorded.')
            ->assertSuccessful();

        $this->assertTrue(app(SchedulerHeartbeat::class)->isFresh());
    }

    public function test_it_returns_a_generic_failure_when_the_heartbeat_cannot_be_recorded(): void
    {
        $heartbeat = Mockery::mock(SchedulerHeartbeat::class);
        $heartbeat->shouldReceive('record')->once()->andReturnFalse();
        $this->app->instance(SchedulerHeartbeat::class, $heartbeat);

        $this->artisan('app:record-scheduler-heartbeat')
            ->expectsOutputToContain('Scheduler heartbeat could not be recorded.')
            ->assertFailed();

        $this->artisan('schedule:list')
            ->expectsOutputToContain('app:record-scheduler-heartbeat')
            ->assertSuccessful();
    }
}
