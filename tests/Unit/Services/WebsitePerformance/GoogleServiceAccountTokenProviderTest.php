<?php

namespace Tests\Unit\Services\WebsitePerformance;

use App\Services\WebsitePerformance\GoogleAuthTokenHttpHandlerFactory;
use App\Services\WebsitePerformance\GoogleServiceAccountTokenProvider;
use App\Services\WebsitePerformance\WebsitePerformanceSourceException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Config\Repository;
use Mockery;
use Psr\Http\Message\RequestInterface;
use Tests\TestCase;

class GoogleServiceAccountTokenProviderTest extends TestCase
{
    private ?string $credentialsPath = null;

    protected function tearDown(): void
    {
        if ($this->credentialsPath !== null && is_file($this->credentialsPath)) {
            unlink($this->credentialsPath);
        }

        parent::tearDown();
    }

    public function test_it_uses_the_google_auth_library_and_retries_transient_token_failures(): void
    {
        $requests = [];
        $handler = function (RequestInterface $request) use (&$requests): Response {
            $requests[] = $request;

            if (count($requests) === 1) {
                throw new ServerException(
                    'temporary',
                    new PsrRequest('POST', 'https://oauth2.googleapis.com/token'),
                    new Response(500, ['Content-Type' => 'application/json'], '{"error":"temporary"}'),
                );
            }

            return new Response(200, ['Content-Type' => 'application/json'], '{"access_token":"google-token"}');
        };
        $factories = Mockery::mock(GoogleAuthTokenHttpHandlerFactory::class);
        $factories->shouldReceive('make')->twice()->andReturn($handler);
        config()->set('services.website_performance.service_account_credentials_path', $this->credentialsFile());

        $provider = new GoogleServiceAccountTokenProvider(app(Repository::class), $factories);

        $this->assertSame('google-token', $provider->accessToken());
        $this->assertSame('google-token', $provider->accessToken());
        $this->assertCount(2, $requests);
        $this->assertSame('https://oauth2.googleapis.com/token', (string) $requests[0]->getUri());
    }

    public function test_it_reports_missing_service_account_credentials_without_attempting_authentication(): void
    {
        config()->set('services.website_performance.service_account_credentials_path', storage_path('app/private/google/missing.json'));
        $factories = Mockery::mock(GoogleAuthTokenHttpHandlerFactory::class);
        $factories->shouldNotReceive('make');
        $provider = new GoogleServiceAccountTokenProvider(app(Repository::class), $factories);

        try {
            $provider->accessToken();
            $this->fail('Missing service-account credentials must not be treated as an empty token.');
        } catch (WebsitePerformanceSourceException $exception) {
            $this->assertSame('google_credentials_unavailable', $exception->reason);
        }
    }

    public function test_it_rejects_service_account_credentials_with_group_or_world_permissions(): void
    {
        $path = $this->credentialsFile();
        chmod($path, 0644);
        config()->set('services.website_performance.service_account_credentials_path', $path);
        $factories = Mockery::mock(GoogleAuthTokenHttpHandlerFactory::class);
        $factories->shouldNotReceive('make');
        $provider = new GoogleServiceAccountTokenProvider(app(Repository::class), $factories);

        try {
            $provider->accessToken();
            $this->fail('Service-account credentials must be private to the owner.');
        } catch (WebsitePerformanceSourceException $exception) {
            $this->assertSame('google_credentials_unavailable', $exception->reason);
        }
    }

    public function test_it_does_not_retry_invalid_google_credentials(): void
    {
        $requests = [];
        $handler = function (RequestInterface $request) use (&$requests): Response {
            $requests[] = $request;

            return new Response(401, ['Content-Type' => 'application/json'], '{"error":"invalid_client"}');
        };
        $factories = Mockery::mock(GoogleAuthTokenHttpHandlerFactory::class);
        $factories->shouldReceive('make')->once()->andReturn($handler);
        config()->set('services.website_performance.service_account_credentials_path', $this->credentialsFile());
        $provider = new GoogleServiceAccountTokenProvider(app(Repository::class), $factories);

        try {
            $provider->accessToken();
            $this->fail('Invalid Google credentials must not be retried as a transient outage.');
        } catch (WebsitePerformanceSourceException $exception) {
            $this->assertSame('google_authentication_unavailable', $exception->reason);
        }

        $this->assertCount(1, $requests);
    }

    public function test_it_stops_after_two_retries_for_persistent_google_token_rate_limit_and_server_errors(): void
    {
        $path = $this->credentialsFile();
        config()->set('services.website_performance.service_account_credentials_path', $path);

        foreach ([429, 500] as $status) {
            $requests = [];
            $handler = function (RequestInterface $request) use (&$requests, $status): Response {
                $requests[] = $request;
                $response = new Response($status, ['Content-Type' => 'application/json'], '{"error":"temporary"}');

                if ($status === 429) {
                    throw new ClientException('rate_limited', $request, $response);
                }

                throw new ServerException('temporary', $request, $response);
            };
            $factories = Mockery::mock(GoogleAuthTokenHttpHandlerFactory::class);
            $factories->shouldReceive('make')->times(3)->andReturn($handler);
            $provider = new GoogleServiceAccountTokenProvider(app(Repository::class), $factories);

            try {
                $provider->accessToken();
                $this->fail('A persistent Google token error must remain unavailable.');
            } catch (WebsitePerformanceSourceException $exception) {
                $this->assertSame('google_authentication_unavailable', $exception->reason);
            }

            $this->assertCount(3, $requests, "Google token status {$status} should receive the initial request plus two retries.");
        }
    }

    private function credentialsFile(): string
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $privateKey = '';
        openssl_pkey_export($key, $privateKey);

        $path = tempnam(sys_get_temp_dir(), 'google-reporting-');

        file_put_contents($path, json_encode([
            'type' => 'service_account',
            'project_id' => 'test-project',
            'private_key_id' => 'test-key',
            'private_key' => $privateKey,
            'client_email' => 'reporting@test-project.iam.gserviceaccount.com',
            'client_id' => '123456789',
        ], JSON_THROW_ON_ERROR));

        $this->credentialsPath = $path;

        return $path;
    }
}
