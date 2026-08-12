<?php

namespace App\Services\WebsitePerformance;

use Google\Auth\HttpHandler\HttpHandlerFactory;
use GuzzleHttp\Client;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class GoogleAuthTokenHttpHandlerFactory
{
    public function __construct(private readonly ConfigRepository $config) {}

    public function make(): callable
    {
        return HttpHandlerFactory::build(new Client([
            'connect_timeout' => $this->connectTimeout(),
            'timeout' => $this->timeout(),
        ]), false);
    }

    private function connectTimeout(): int
    {
        return min(30, max(1, (int) $this->config->get('services.website_performance.connect_timeout', 5)));
    }

    private function timeout(): int
    {
        return min(120, max($this->connectTimeout(), (int) $this->config->get('services.website_performance.timeout', 30)));
    }
}
