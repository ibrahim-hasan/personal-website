<?php

namespace App\Services\WebsitePerformance;

use App\Contracts\WebsitePerformance\GoogleAccessTokenProvider;
use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Throwable;

class GoogleServiceAccountTokenProvider implements GoogleAccessTokenProvider
{
    /** @var list<string> */
    private const SCOPES = [
        'https://www.googleapis.com/auth/analytics.readonly',
        'https://www.googleapis.com/auth/webmasters.readonly',
    ];

    private ?string $accessToken = null;

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly GoogleAuthTokenHttpHandlerFactory $handlers,
    ) {}

    public function accessToken(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $credentialsPath = trim((string) $this->config->get('services.website_performance.service_account_credentials_path'));

        if ($credentialsPath === ''
            || ! is_file($credentialsPath)
            || ! is_readable($credentialsPath)) {
            throw new WebsitePerformanceSourceException('google_credentials_unavailable');
        }

        $permissions = fileperms($credentialsPath);

        if ($permissions === false || ($permissions & 0o077) !== 0) {
            throw new WebsitePerformanceSourceException('google_credentials_unavailable');
        }

        try {
            $credentials = new ServiceAccountCredentials(self::SCOPES, $credentialsPath);
            $token = retry(
                [100, 500],
                fn (): array => $credentials->fetchAuthToken($this->handlers->make()),
                when: fn (Throwable $exception): bool => $this->shouldRetry($exception),
            );
        } catch (Throwable) {
            throw new WebsitePerformanceSourceException('google_authentication_unavailable');
        }

        $accessToken = is_array($token) ? $token['access_token'] ?? null : null;

        if (! is_string($accessToken) || trim($accessToken) === '') {
            throw new WebsitePerformanceSourceException('google_authentication_unavailable');
        }

        return $this->accessToken = trim($accessToken);
    }

    private function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectException) {
            return true;
        }

        return $exception instanceof RequestException
            && ($exception->getResponse()?->getStatusCode() === 429 || $exception->getResponse()?->getStatusCode() >= 500);
    }
}
