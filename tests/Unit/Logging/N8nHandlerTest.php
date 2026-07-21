<?php

namespace Tests\Unit\Logging;

use App\Logging\N8nHandler;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Monolog\Level;
use Monolog\Logger;
use RuntimeException;
use Tests\TestCase;

class N8nHandlerTest extends TestCase
{
    public function test_external_alerts_do_not_disclose_request_or_user_secrets(): void
    {
        Http::fake();

        $request = Request::create(
            'https://ibrahimhasan.net/reader/reset-password/sensitive-token?email=private@example.com',
            'GET',
            server: [
                'REMOTE_ADDR' => '203.0.113.10',
                'HTTP_USER_AGENT' => 'Private Browser Fingerprint',
                'HTTP_X_REQUEST_ID' => 'request-id<script>',
            ],
        );
        $route = new Route(['GET'], 'reader/reset-password/{token}', fn () => null);
        $route->name('reader.password.reset');
        $request->setRouteResolver(fn (): Route => $route);
        app()->instance('request', $request);

        $user = User::factory()->make([
            'id' => 42,
            'email' => 'private@example.com',
        ]);
        Auth::setUser($user);

        $logger = new Logger('n8n-redaction-test');
        $logger->pushHandler(new N8nHandler([
            'url' => 'https://alerts.example.test/webhook',
            'token' => 'webhook-token',
            'project' => 'Privacy Test',
            'timeout' => 1,
        ], Level::Warning));
        $logger->warning('Password reset failed for private@example.com', [
            'exception' => new RuntimeException('sensitive-token'),
        ]);
        $logger->warning('Private Browser Fingerprint used by private@example.com from 203.0.113.10');
        $logger->warning('Another sensitive warning for private@example.com');

        $payloads = Http::recorded()
            ->map(static fn (array $recorded): array => $recorded[0]->data());

        foreach ($payloads as $payload) {
            $serialized = json_encode($payload, JSON_THROW_ON_ERROR);

            $this->assertSame('reader.password.reset', $payload['route']);
            $this->assertSame('/reader/reset-password/{token}', $payload['path_template']);
            $this->assertSame('Authenticated', $payload['user_state']);
            $this->assertSame('request-idscript', $payload['request_id']);
            $this->assertArrayNotHasKey('trace', $payload);
            $this->assertArrayNotHasKey('url', $payload);
            $this->assertArrayNotHasKey('ip', $payload);
            $this->assertArrayNotHasKey('user_agent', $payload);
            $this->assertStringNotContainsString('sensitive-token', $serialized);
            $this->assertStringNotContainsString('private@example.com', $serialized);
            $this->assertStringNotContainsString('203.0.113.10', $serialized);
            $this->assertStringNotContainsString('Private Browser Fingerprint', $serialized);
            $this->assertStringNotContainsString('Another sensitive warning', $serialized);
        }

        $eventFingerprints = $payloads
            ->pluck('event_fingerprint')
            ->filter()
            ->values();

        $this->assertNull($payloads[0]['event_fingerprint']);
        $this->assertCount(2, $eventFingerprints);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $eventFingerprints[0]);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{16}$/', $eventFingerprints[1]);
        $this->assertNotSame($eventFingerprints[0], $eventFingerprints[1]);
        Http::assertSentCount(3);
    }
}
