<?php

namespace Tests\Feature\Api;

use App\Enums\ContactInquiryStatus;
use App\Http\Middleware\EnsureApiScope;
use App\Models\ContactInquiry;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Exceptions\AuthenticationException;
use Laravel\Passport\Passport;
use League\OAuth2\Server\ResourceServer;
use Mockery;
use Psr\Http\Message\ServerRequestInterface;
use Tests\TestCase;

class WebsiteMetricsApiTest extends TestCase
{
    use RefreshDatabase;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::factory()->asClientCredentials()->create([
            'scopes' => ['analytics:read'],
        ]);
        config()->set('services.website_performance.metrics_api_client_id', (string) $this->client->getKey());
    }

    public function test_client_credentials_can_read_aggregate_inquiry_metrics_without_personal_data(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-12 12:00:00', 'Asia/Riyadh'));

        $firstInquiry = ContactInquiry::factory()->create([
            'name' => 'Private Inquiry Name',
            'email' => 'private-inquiry@example.test',
            'company' => 'Private Company',
            'challenge' => 'Private consultation challenge',
            'public_reference' => 'IH-PRIVATE-REF',
            'submission_hash' => str_repeat('a', 64),
            'notes' => 'Private operational note',
            'locale' => 'ar',
            'service_key' => 'ai-adoption',
            'status' => ContactInquiryStatus::New,
            'received_at' => CarbonImmutable::parse('2026-07-31 21:00:00', 'UTC'),
        ]);
        ContactInquiry::factory()->create([
            'locale' => 'en',
            'service_key' => 'decision-systems',
            'status' => ContactInquiryStatus::Archived,
            'received_at' => CarbonImmutable::parse('2026-08-02 19:30:00', 'UTC'),
            'replied_at' => CarbonImmutable::parse('2026-08-03 08:00:00', 'UTC'),
        ]);
        ContactInquiry::factory()->create([
            'received_at' => CarbonImmutable::parse('2026-08-03 21:00:00', 'UTC'),
        ]);

        $response = $this->asMetricsClient()
            ->getJson($this->metricsUrl())
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, noarchive')
            ->assertJsonPath('data.timezone', 'Asia/Riyadh')
            ->assertJsonPath('data.start_date', '2026-08-01')
            ->assertJsonPath('data.end_date', '2026-08-03')
            ->assertJsonPath('data.inquiries.total', 2)
            ->assertJsonPath('data.inquiries.by_day', [
                ['date' => '2026-08-01', 'count' => 1],
                ['date' => '2026-08-02', 'count' => 1],
                ['date' => '2026-08-03', 'count' => 0],
            ])
            ->assertJsonPath('data.inquiries.by_locale', [
                ['locale' => 'ar', 'count' => 1],
                ['locale' => 'en', 'count' => 1],
            ])
            ->assertJsonPath('data.inquiries.by_service', [
                ['service_key' => 'ai-adoption', 'count' => 1],
                ['service_key' => 'decision-systems', 'count' => 1],
            ])
            ->assertJsonPath('data.inquiries.by_status', [
                ['status' => 'new', 'count' => 1],
                ['status' => 'reviewing', 'count' => 0],
                ['status' => 'replied', 'count' => 0],
                ['status' => 'archived', 'count' => 1],
            ])
            ->assertJsonPath('data.inquiries.responded', 1)
            ->assertJsonPath('data.inquiries.response_rate', 0.5)
            ->assertJsonPath('meta.privacy', 'aggregate_only');

        $inquiries = $response->json('data.inquiries');

        $this->assertIsArray($inquiries);
        $this->assertArrayNotHasKey('id', $inquiries);
        $this->assertArrayNotHasKey('name', $inquiries);
        $this->assertArrayNotHasKey('email', $inquiries);
        $this->assertArrayNotHasKey('company', $inquiries);
        $this->assertArrayNotHasKey('challenge', $inquiries);
        $this->assertArrayNotHasKey('notes', $inquiries);
        $this->assertArrayNotHasKey('public_reference', $inquiries);
        $this->assertArrayNotHasKey('submission_hash', $inquiries);
        $this->assertStringNotContainsString($firstInquiry->name, $response->getContent());
        $this->assertStringNotContainsString($firstInquiry->email, $response->getContent());
        $this->assertStringNotContainsString((string) $firstInquiry->public_reference, $response->getContent());
        $this->assertStringNotContainsString((string) $firstInquiry->submission_hash, $response->getContent());
    }

    public function test_client_credentials_are_required_even_when_another_client_has_the_scope(): void
    {
        $nonMachineClient = Client::factory()->create([
            'scopes' => ['analytics:read'],
        ]);

        $this->asMetricsClient($nonMachineClient)
            ->getJson($this->metricsUrl())
            ->assertUnauthorized();
    }

    public function test_only_an_explicitly_scoped_dedicated_machine_client_can_read_metrics(): void
    {
        $unscopedMachineClient = Client::factory()->asClientCredentials()->create();
        config()->set('services.website_performance.metrics_api_client_id', (string) $unscopedMachineClient->getKey());

        $this->asMetricsClient($unscopedMachineClient)
            ->getJson($this->metricsUrl())
            ->assertUnauthorized();
    }

    public function test_a_user_subject_token_is_rejected_for_the_metrics_scope(): void
    {
        $server = Mockery::mock(ResourceServer::class);
        $server->shouldReceive('validateAuthenticatedRequest')
            ->once()
            ->andReturnUsing(fn (ServerRequestInterface $request): ServerRequestInterface => $request
                ->withAttribute('oauth_client_id', $this->client->getKey())
                ->withAttribute('oauth_scopes', ['analytics:read'])
                ->withAttribute('oauth_user_id', 'website-user'));
        app()->instance(ResourceServer::class, $server);

        $request = Request::create('/api/v1/metrics/website', 'GET');
        $request->headers->set('Authorization', 'Bearer user-subject-token');

        $this->expectException(AuthenticationException::class);

        (new EnsureApiScope(app(ClientRepository::class)))->handle(
            $request,
            static fn () => response()->json(),
            'analytics:read',
        );
    }

    public function test_the_metrics_scope_is_required(): void
    {
        $this->asMetricsClient(scopes: ['articles:read'])
            ->getJson($this->metricsUrl())
            ->assertForbidden();
    }

    public function test_a_metrics_request_requires_a_bearer_token(): void
    {
        $this->getJson($this->metricsUrl())
            ->assertUnauthorized();
    }

    public function test_metrics_error_responses_keep_the_endpoint_private(): void
    {
        $this->getJson($this->metricsUrl())
            ->assertUnauthorized()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, noarchive');

        $this->asMetricsClient(scopes: ['articles:read'])
            ->getJson($this->metricsUrl())
            ->assertForbidden()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, noarchive');

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->asMetricsClient()
                ->getJson($this->metricsUrl())
                ->assertOk();
        }

        $this->asMetricsClient()
            ->getJson($this->metricsUrl())
            ->assertTooManyRequests()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Robots-Tag', 'noindex, noarchive');
    }

    public function test_failed_requests_are_logged_without_query_values_or_metrics(): void
    {
        Log::spy();

        $this->getJson($this->metricsUrl())
            ->assertUnauthorized();

        $this->asMetricsClient(scopes: ['articles:read'])
            ->getJson($this->metricsUrl())
            ->assertForbidden();

        $this->asMetricsClient()
            ->getJson('/api/v1/metrics/website?start=2026-08-03&end=2026-08-01')
            ->assertUnprocessable();

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $event, array $context): bool {
                return $event === 'website_metrics_api_access'
                    && is_string($context['request_id'] ?? null)
                    && array_key_exists('client_id', $context)
                    && $context['route'] === 'api.v1.metrics.website'
                    && ! array_key_exists('authorization', $context)
                    && ! array_key_exists('start', $context)
                    && ! array_key_exists('end', $context)
                    && ! array_key_exists('metrics', $context);
            })
            ->times(3);

        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $event, array $context): bool => $event === 'website_metrics_api_access'
                && $context['status'] === 401
                && $context['outcome'] === 'unauthenticated')
            ->once();
        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $event, array $context): bool => $event === 'website_metrics_api_access'
                && $context['client_id'] === $this->client->getKey()
                && $context['status'] === 403
                && $context['outcome'] === 'forbidden')
            ->once();
        Log::shouldHaveReceived('info')
            ->withArgs(fn (string $event, array $context): bool => $event === 'website_metrics_api_access'
                && $context['client_id'] === $this->client->getKey()
                && $context['status'] === 422
                && $context['outcome'] === 'invalid_request')
            ->once();
    }

    public function test_date_validation_uses_riyadh_dates_and_the_366_day_limit(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-12 12:00:00', 'Asia/Riyadh'));

        $this->asMetricsClient()
            ->getJson('/api/v1/metrics/website?start=2025-08-12&end=2026-08-12')
            ->assertOk();

        $this->asMetricsClient()
            ->getJson('/api/v1/metrics/website?start=2026-08-13&end=2026-08-13')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start', 'end'])
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->asMetricsClient()
            ->getJson('/api/v1/metrics/website?start=2026-08-03&end=2026-08-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start']);

        $this->asMetricsClient()
            ->getJson('/api/v1/metrics/website?start=2025-08-11&end=2026-08-12')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end']);
    }

    public function test_the_rate_limit_is_isolated_by_oauth_client_and_ip_address(): void
    {
        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->asMetricsClient()
                ->getJson($this->metricsUrl())
                ->assertOk();
        }

        $this->asMetricsClient()
            ->getJson($this->metricsUrl())
            ->assertTooManyRequests();

        $this->asMetricsClient()
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->getJson($this->metricsUrl())
            ->assertOk();

        $secondClient = Client::factory()->asClientCredentials()->create([
            'scopes' => ['analytics:read'],
        ]);
        config()->set('services.website_performance.metrics_api_client_id', (string) $secondClient->getKey());

        $this->asMetricsClient($secondClient)
            ->getJson($this->metricsUrl())
            ->assertOk();
    }

    public function test_successful_access_is_logged_without_query_values_or_metrics(): void
    {
        Log::spy();

        $this->asMetricsClient()
            ->getJson($this->metricsUrl())
            ->assertOk();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $event, array $context): bool {
                return $event === 'website_metrics_api_access'
                    && is_string($context['request_id'] ?? null)
                    && $context['client_id'] === $this->client->getKey()
                    && $context['route'] === 'api.v1.metrics.website'
                    && $context['status'] === 200
                    && $context['outcome'] === 'success'
                    && ! array_key_exists('authorization', $context)
                    && ! array_key_exists('start', $context)
                    && ! array_key_exists('end', $context)
                    && ! array_key_exists('metrics', $context);
            });
    }

    private function metricsUrl(): string
    {
        return '/api/v1/metrics/website?start=2026-08-01&end=2026-08-03';
    }

    /** @param list<string> $scopes */
    private function asMetricsClient(?Client $client = null, array $scopes = ['analytics:read']): static
    {
        Passport::actingAsClient($client ?? $this->client, $scopes);
        $this->withToken('website-metrics-test-token');

        return $this;
    }
}
