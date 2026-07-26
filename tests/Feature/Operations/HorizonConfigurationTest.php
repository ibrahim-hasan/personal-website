<?php

namespace Tests\Feature\Operations;

use Tests\TestCase;

class HorizonConfigurationTest extends TestCase
{
    public function test_horizon_uses_dedicated_default_and_audio_supervisors(): void
    {
        $defaults = config('horizon.defaults');

        $this->assertSame(['default'], $defaults['supervisor-default']['queue']);
        $this->assertFalse($defaults['supervisor-default']['balance']);
        $this->assertSame(1, $defaults['supervisor-default']['maxProcesses']);
        $this->assertSame(256, $defaults['supervisor-default']['memory']);
        $this->assertSame(3, $defaults['supervisor-default']['tries']);
        $this->assertSame(300, $defaults['supervisor-default']['timeout']);

        $this->assertSame(['article-audio'], $defaults['supervisor-article-audio']['queue']);
        $this->assertFalse($defaults['supervisor-article-audio']['balance']);
        $this->assertSame(1, $defaults['supervisor-article-audio']['maxProcesses']);
        $this->assertSame(512, $defaults['supervisor-article-audio']['memory']);
        $this->assertSame(1, $defaults['supervisor-article-audio']['tries']);
        $this->assertSame(1620, $defaults['supervisor-article-audio']['timeout']);
    }

    public function test_horizon_configures_staging_and_production_with_the_same_initial_isolation(): void
    {
        foreach (['production', 'staging'] as $environment) {
            $supervisors = config('horizon.environments.'.$environment);

            $this->assertSame(1, $supervisors['supervisor-default']['maxProcesses']);
            $this->assertSame(1, $supervisors['supervisor-article-audio']['maxProcesses']);
        }
    }

    public function test_audio_timeout_chain_prevents_a_job_from_being_reclaimed_while_horizon_runs_it(): void
    {
        $jobTimeout = (int) config('services.elevenlabs.job_timeout');
        $horizonTimeout = (int) config('horizon.defaults.supervisor-article-audio.timeout');
        $retryAfter = (int) config('queue.connections.redis.retry_after');

        $this->assertSame(1620, $horizonTimeout);
        $this->assertSame(1800, $retryAfter);
        $this->assertGreaterThan(0, $jobTimeout);
        $this->assertLessThanOrEqual(1560, $jobTimeout);
        $this->assertLessThan($horizonTimeout, $jobTimeout);
        $this->assertLessThan($retryAfter, $horizonTimeout);
    }

    public function test_horizon_wait_thresholds_reflect_the_default_and_audio_operating_contract(): void
    {
        $this->assertSame(60, config('horizon.waits.redis:default'));
        $this->assertSame(300, config('horizon.waits.redis:article-audio'));
    }

    public function test_horizon_metrics_snapshots_are_scheduled_every_five_minutes(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('horizon:snapshot')
            ->assertExitCode(0);
    }
}
