<?php

namespace Tests\Feature\Operations;

use App\Services\Operations\ReleaseReadiness;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ReadinessEndpointTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('operations.readiness.secret', 'readiness-test-secret');
        config()->set('operations.readiness.rate_limit_attempts', 30);
        config()->set('operations.readiness.rate_limit_decay_seconds', 60);
    }

    public function test_readiness_requires_the_configured_secret_and_never_reveals_details(): void
    {
        $this->bindReadiness(passes: true);

        $this->get('/health/ready')
            ->assertStatus(503)
            ->assertContent('')
            ->assertHeaderContains('Cache-Control', 'no-store')
            ->assertHeader('X-Robots-Tag', 'noindex, noarchive')
            ->assertDontSee('readiness-test-secret');

        $this->get('/health/ready', ['X-Readiness-Key' => 'incorrect-secret'])
            ->assertStatus(503)
            ->assertContent('');
    }

    public function test_liveness_remains_available_without_the_readiness_secret(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_readiness_returns_no_content_only_for_a_valid_secret_and_a_passing_check(): void
    {
        $this->bindReadiness(passes: true, times: 1);

        $this->get('/health/ready', ['X-Readiness-Key' => 'readiness-test-secret'])
            ->assertNoContent()
            ->assertHeaderContains('Cache-Control', 'no-store');
    }

    public function test_readiness_returns_only_a_generic_service_unavailable_response_when_a_check_fails(): void
    {
        $this->bindReadiness(passes: false, times: 1);

        $this->get('/health/ready', ['X-Readiness-Key' => 'readiness-test-secret'])
            ->assertStatus(503)
            ->assertContent('');
    }

    public function test_readiness_rate_limits_without_changing_the_opaque_response_contract(): void
    {
        config()->set('operations.readiness.rate_limit_attempts', 1);
        $this->bindReadiness(passes: true, times: 1);

        $this->get('/health/ready', ['X-Readiness-Key' => 'readiness-test-secret'])
            ->assertNoContent();

        $this->get('/health/ready', ['X-Readiness-Key' => 'readiness-test-secret'])
            ->assertStatus(503)
            ->assertContent('');
    }

    public function test_an_invalid_secret_cannot_exhaust_the_valid_monitor_rate_limit(): void
    {
        config()->set('operations.readiness.rate_limit_attempts', 1);
        $this->bindReadiness(passes: true, times: 1);

        $this->get('/health/ready', ['X-Readiness-Key' => 'incorrect-secret'])
            ->assertStatus(503)
            ->assertContent('');

        $this->get('/health/ready', ['X-Readiness-Key' => 'readiness-test-secret'])
            ->assertNoContent();
    }

    private function bindReadiness(bool $passes, int $times = 0): void
    {
        $readiness = Mockery::mock(ReleaseReadiness::class);
        $expectation = $readiness->shouldReceive('passes')->andReturn($passes);

        if ($times > 0) {
            $expectation->times($times);
        }

        $this->app->instance(ReleaseReadiness::class, $readiness);
    }
}
