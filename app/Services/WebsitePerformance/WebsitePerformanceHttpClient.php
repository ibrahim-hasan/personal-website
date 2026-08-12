<?php

namespace App\Services\WebsitePerformance;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Throwable;

abstract class WebsitePerformanceHttpClient
{
    public function __construct(
        protected readonly HttpFactory $http,
        protected readonly ConfigRepository $config,
    ) {}

    protected function request(): PendingRequest
    {
        return $this->http->acceptJson()
            ->connectTimeout($this->connectTimeout())
            ->timeout($this->timeout())
            ->retry(
                [100, 500],
                0,
                fn (Throwable $exception): bool => $this->shouldRetry($exception),
                false,
            );
    }

    protected function connectTimeout(): int
    {
        return min(30, max(1, (int) $this->config->get('services.website_performance.connect_timeout', 5)));
    }

    protected function timeout(): int
    {
        return min(120, max($this->connectTimeout(), (int) $this->config->get('services.website_performance.timeout', 30)));
    }

    protected function shouldRetry(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException
            && ($exception->response->status() === 429 || $exception->response->serverError());
    }

    protected function isSecureUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_SCHEME) === 'https';
    }
}
