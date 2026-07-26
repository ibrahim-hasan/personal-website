<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

#[Signature('operations:check-readiness {--url= : Absolute HTTPS readiness URL}')]
#[Description('Probe the protected readiness endpoint with only server-held configuration')]
class CheckReadiness extends Command
{
    public function handle(HttpFactory $http): int
    {
        $url = trim((string) ($this->option('url') ?: config('operations.readiness.probe_url')));
        $header = trim((string) config('operations.readiness.header'));
        $secret = (string) config('operations.readiness.secret');

        if (! $this->isValidUrl($url) || $header === '' || $secret === '') {
            return $this->unavailable();
        }

        try {
            $response = $http->withOptions(['allow_redirects' => false])
                ->withHeaders([$header => $secret])
                ->connectTimeout(5)
                ->timeout(20)
                ->get($url);
        } catch (Throwable) {
            return $this->unavailable();
        }

        if ($response->status() !== 204) {
            return $this->unavailable();
        }

        $this->components->info('Readiness probe: ready.');

        return self::SUCCESS;
    }

    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && parse_url($url, PHP_URL_SCHEME) === 'https';
    }

    private function unavailable(): int
    {
        $this->components->error('Readiness probe: unavailable.');

        return self::FAILURE;
    }
}
