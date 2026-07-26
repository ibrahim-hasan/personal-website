<?php

namespace Tests\Feature\Operations;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckReadinessTest extends TestCase
{
    public function test_it_probes_the_protected_endpoint_with_server_held_configuration_only(): void
    {
        config()->set('operations.readiness.header', 'X-Readiness-Key');
        config()->set('operations.readiness.secret', 'server-only-readiness-secret');
        config()->set('operations.readiness.probe_url', 'https://readiness.example.test/health/ready');
        Http::fake([
            'https://readiness.example.test/health/ready' => Http::response('', 204),
        ]);

        $this->artisan('operations:check-readiness')
            ->expectsOutputToContain('Readiness probe: ready.')
            ->assertSuccessful();

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://readiness.example.test/health/ready'
                && $request->hasHeader('X-Readiness-Key', 'server-only-readiness-secret');
        });
    }

    public function test_it_fails_without_sending_a_request_when_required_server_configuration_is_missing(): void
    {
        config()->set('operations.readiness.header', 'X-Readiness-Key');
        config()->set('operations.readiness.secret', '');
        config()->set('operations.readiness.probe_url', 'https://readiness.example.test/health/ready');
        Http::fake();

        $this->artisan('operations:check-readiness')
            ->expectsOutputToContain('Readiness probe: unavailable.')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_it_rejects_non_https_urls_without_exposing_configuration(): void
    {
        config()->set('operations.readiness.header', 'X-Readiness-Key');
        config()->set('operations.readiness.secret', 'server-only-readiness-secret');
        config()->set('operations.readiness.probe_url', 'http://readiness.example.test/health/ready');
        Http::fake();

        $this->artisan('operations:check-readiness')
            ->expectsOutputToContain('Readiness probe: unavailable.')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_it_treats_every_response_other_than_no_content_as_unavailable(): void
    {
        config()->set('operations.readiness.header', 'X-Readiness-Key');
        config()->set('operations.readiness.secret', 'server-only-readiness-secret');
        config()->set('operations.readiness.probe_url', 'https://readiness.example.test/health/ready');
        Http::fake([
            'https://readiness.example.test/health/ready' => Http::response('', 503),
        ]);

        $this->artisan('operations:check-readiness')
            ->expectsOutputToContain('Readiness probe: unavailable.')
            ->assertFailed();

        Http::assertSentCount(1);
    }
}
