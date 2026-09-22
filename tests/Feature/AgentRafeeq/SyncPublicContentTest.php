<?php

namespace Tests\Feature\AgentRafeeq;

use App\Services\AgentRafeeq\PublicContentExporter;
use App\Services\AgentRafeeq\SyncPublicContent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SyncPublicContentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        File::deleteDirectory(storage_path('app/private/agent-rafeeq'));
        config()->set('services.agent_rafeeq_sync', [
            'enabled' => true,
            'api_url' => 'https://agent.example.com',
            'secret_key' => 'sk_live_private_test',
        ]);
        config()->set('services.agent_rafeeq_widget.public_key', 'pk_live_public_test');
        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/private/agent-rafeeq'));
        parent::tearDown();
    }

    public function test_it_sends_bounded_batches_and_withdraws_only_previously_managed_ids(): void
    {
        $this->snapshot(range(1, 101));
        $this->successfulApi();
        $first = app(SyncPublicContent::class)->run();
        $this->assertSame(['upserted' => 101, 'withdrawn' => 0], $first);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer sk_live_private_test') && count($request['items']) === 100);
        $this->assertSame(0600, fileperms($this->statePath()) & 0777);
        $this->assertStringNotContainsString('sk_live', File::get($this->statePath()));

        $this->snapshot(range(2, 101));
        $this->successfulApi();
        $second = app(SyncPublicContent::class)->run();
        $this->assertSame(['upserted' => 100, 'withdrawn' => 1], $second);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'DELETE' && str_ends_with($request->url(), 'ibrahim-website%3Aservice%3Aitem-1'));
        $this->assertCount(100, json_decode(File::get($this->statePath()), true)['ids']);
    }

    public function test_an_interrupted_partial_sync_keeps_new_ids_tracked_for_later_withdrawal(): void
    {
        $this->snapshot(range(1, 101));
        Http::fakeSequence()
            ->push(['items' => array_map(fn (int $id): array => ['external_id' => 'ibrahim-website:service:item-'.$id, 'action' => 'created'], range(1, 100))])
            ->push(['message' => 'SECRET-RESPONSE'], 503);

        try {
            app(SyncPublicContent::class)->run();
            $this->fail('The partial sync must fail.');
        } catch (RuntimeException $exception) {
            $this->assertStringNotContainsString('SECRET-RESPONSE', $exception->getMessage());
        }

        $this->assertCount(101, json_decode(File::get($this->statePath()), true)['ids']);
        $this->snapshot([101]);
        $this->successfulApi();
        $result = app(SyncPublicContent::class)->run();
        $this->assertSame(['upserted' => 1, 'withdrawn' => 100], $result);
    }

    public function test_a_redirect_or_incomplete_acknowledgement_cannot_mark_the_sync_complete(): void
    {
        $this->snapshot([1]);
        Http::fake(['*' => Http::response('', 302, ['Location' => 'https://untrusted.example'])]);
        $this->artisan('agent-rafeeq:sync')->assertFailed();
        Http::assertSentCount(1);
        $this->assertNull(json_decode(File::get($this->statePath()), true)['completed_at']);

        Http::swap(new Factory);
        Http::fake(['*' => Http::response(['items' => []])]);
        $this->artisan('agent-rafeeq:sync')->assertFailed();
        $this->assertNull(json_decode(File::get($this->statePath()), true)['completed_at']);
    }

    public function test_corrupt_or_different_destination_state_fails_without_remote_mutation(): void
    {
        $this->snapshot([1]);
        $this->successfulApi();
        app(SyncPublicContent::class)->run();
        $previous = File::get($this->statePath());
        config()->set('services.agent_rafeeq_widget.public_key', 'pk_live_different_workspace');
        Http::swap(new Factory);
        Http::fake();
        $this->artisan('agent-rafeeq:sync')->assertFailed();
        Http::assertNothingSent();
        $this->assertSame($previous, File::get($this->statePath()));

        File::put($this->statePath(), 'invalid-json');
        $this->artisan('agent-rafeeq:sync')->assertFailed();
        Http::assertNothingSent();
    }

    public function test_disabled_dry_run_and_unsafe_configuration_do_not_send_content(): void
    {
        $this->snapshot([1]);
        Http::fake();
        config()->set('services.agent_rafeeq_sync.enabled', false);
        $this->artisan('agent-rafeeq:sync')->expectsOutputToContain('disabled')->assertSuccessful();
        $this->artisan('agent-rafeeq:sync --dry-run')->expectsOutputToContain('1 items')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertFileDoesNotExist($this->statePath());

        $this->app->instance('env', 'production');
        config()->set('services.agent_rafeeq_sync.enabled', true);
        foreach ([
            'http://agent.example.com',
            'https://user:secret@agent.example.com',
            'https://agent.example.com/path',
            'https://agent.example.com?key=secret',
            'https://[::ffff:127.0.0.1]',
            'https://localhost.',
            'https://agent.local.',
            'https://127.0.0.1.',
            'https://0177.0.0.1',
            'https://agent.invalid',
            'https://agent.example',
        ] as $url) {
            config()->set('services.agent_rafeeq_sync.api_url', $url);
            $this->artisan('agent-rafeeq:sync')->assertFailed();
        }
        Http::assertNothingSent();
    }

    public function test_the_sync_is_hourly_and_protected_against_overlapping_production_runs(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event): bool => str_contains((string) $event->command, 'agent-rafeeq:sync'));
        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertSame(['production'], $event->environments);
        $this->assertTrue($event->withoutOverlapping);
    }

    /** @param list<int> $ids */
    private function snapshot(array $ids): void
    {
        $exporter = Mockery::mock(PublicContentExporter::class);
        $exporter->shouldReceive('manifest')->andReturn(['items' => array_map(fn (int $id): array => [
            'external_id' => 'ibrahim-website:service:item-'.$id,
            'type' => 'service',
            'title' => 'Public service '.$id,
        ], $ids)]);
        $this->app->instance(PublicContentExporter::class, $exporter);
    }

    private function successfulApi(): void
    {
        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => $request->method() === 'DELETE'
            ? Http::response(['message' => 'Item not found.'], 404)
            : Http::response(['items' => array_map(fn (array $item): array => ['external_id' => $item['external_id'], 'action' => 'unchanged'], $request['items'])]));
    }

    private function statePath(): string
    {
        return storage_path('app/private/agent-rafeeq/sync-state.json');
    }
}
