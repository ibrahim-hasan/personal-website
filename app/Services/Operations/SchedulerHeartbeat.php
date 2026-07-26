<?php

namespace App\Services\Operations;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class SchedulerHeartbeat
{
    public function __construct(
        private CacheRepository $cache,
        private ConfigRepository $config,
    ) {}

    public function record(): bool
    {
        return $this->cache->put(
            $this->key(),
            now()->getTimestamp(),
            now()->addSeconds($this->ttlSeconds()),
        );
    }

    public function isFresh(): bool
    {
        $recordedAt = $this->cache->get($this->key());

        if (! is_int($recordedAt) && ! (is_string($recordedAt) && ctype_digit($recordedAt))) {
            return false;
        }

        return (int) $recordedAt >= now()->subSeconds($this->maxAgeSeconds())->getTimestamp();
    }

    private function key(): string
    {
        return (string) $this->config->get('operations.scheduler_heartbeat.key');
    }

    private function maxAgeSeconds(): int
    {
        return max(1, (int) $this->config->get('operations.scheduler_heartbeat.max_age_seconds'));
    }

    private function ttlSeconds(): int
    {
        return max($this->maxAgeSeconds(), (int) $this->config->get('operations.scheduler_heartbeat.ttl_seconds'));
    }
}
