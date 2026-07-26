<?php

namespace App\Services\Operations;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Throwable;

class ReleaseReadiness
{
    public function __construct(
        private RedisFactory $redis,
        private Migrator $migrator,
        private SchedulerHeartbeat $schedulerHeartbeat,
        private FilesystemFactory $storage,
        private Filesystem $files,
        private ConfigRepository $config,
        private MasterSupervisorRepository $masterSupervisors,
    ) {}

    /**
     * @return array<string, array{required: bool, passed: bool}>
     */
    public function report(): array
    {
        $releaseEnvironment = (bool) $this->config->get('operations.release_environment');

        return [
            'database' => [
                'required' => true,
                'passed' => $this->probe(fn (): bool => $this->databaseIsAvailable()),
            ],
            'redis' => [
                'required' => $releaseEnvironment,
                'passed' => ! $releaseEnvironment || $this->probe(fn (): bool => $this->redisIsAvailable()),
            ],
            'scheduler' => [
                'required' => $releaseEnvironment,
                'passed' => ! $releaseEnvironment || $this->probe(fn (): bool => $this->schedulerHeartbeat->isFresh()),
            ],
            'storage' => [
                'required' => $releaseEnvironment,
                'passed' => ! $releaseEnvironment || $this->probe(fn (): bool => $this->requiredStorageIsAvailable()),
            ],
            'migrations' => [
                'required' => true,
                'passed' => $this->probe(fn (): bool => $this->hasNoPendingMigrations()),
            ],
            'horizon' => [
                'required' => $releaseEnvironment,
                'passed' => ! $releaseEnvironment || $this->probe(fn (): bool => $this->horizonIsRunning()),
            ],
            'configuration' => [
                'required' => $releaseEnvironment,
                'passed' => ! $releaseEnvironment || $this->probe(fn (): bool => $this->criticalConfigurationIsValid()),
            ],
            'build_revision' => [
                'required' => $releaseEnvironment,
                'passed' => ! $releaseEnvironment || $this->probe(fn (): bool => $this->buildRevisionIsValid()),
            ],
        ];
    }

    /**
     * @param  array<string, array{required: bool, passed: bool}>|null  $report
     */
    public function passes(?array $report = null): bool
    {
        foreach ($report ?? $this->report() as $check) {
            if ($check['required'] && ! $check['passed']) {
                return false;
            }
        }

        return true;
    }

    private function databaseIsAvailable(): bool
    {
        return retry(3, fn (): bool => $this->migrator->usingConnection(
            null,
            fn (): bool => $this->migrator->repositoryExists(),
        ), 100);
    }

    private function redisIsAvailable(): bool
    {
        $connection = $this->redis->connection(
            (string) $this->config->get('queue.connections.redis.connection', 'default'),
        );
        $response = $connection->command('ping');

        return $response === true || strtoupper((string) $response) === 'PONG';
    }

    private function requiredStorageIsAvailable(): bool
    {
        $disks = $this->config->get('operations.required_storage_disks', []);
        $probePath = ltrim((string) $this->config->get('operations.storage_probe_path'), '/');

        if (! is_array($disks) || $disks === [] || $probePath === '') {
            return false;
        }

        foreach ($disks as $disk) {
            if (! is_string($disk) || $disk === '' || ! $this->storage->disk($disk)->exists($probePath)) {
                return false;
            }
        }

        return true;
    }

    private function hasNoPendingMigrations(): bool
    {
        return retry(3, fn (): bool => $this->migrator->usingConnection(null, function (): bool {
            if (! $this->migrator->repositoryExists()) {
                return false;
            }

            $paths = array_values(array_unique([
                ...$this->migrator->paths(),
                database_path('migrations'),
            ]));
            $available = array_keys($this->migrator->getMigrationFiles($paths));
            $ran = $this->migrator->getRepository()->getRan();

            return array_diff($available, $ran) === [];
        }), 100);
    }

    private function horizonIsRunning(): bool
    {
        return $this->masterSupervisors->names() !== [];
    }

    private function criticalConfigurationIsValid(): bool
    {
        $defaultSupervisor = (array) $this->config->get('horizon.defaults.supervisor-default', []);
        $audioSupervisor = (array) $this->config->get('horizon.defaults.supervisor-article-audio', []);
        $audioJobTimeout = (int) $this->config->get('services.elevenlabs.job_timeout');
        $retryAfter = (int) $this->config->get('queue.connections.redis.retry_after');
        $audioSupervisorTimeout = (int) ($audioSupervisor['timeout'] ?? 0);
        $readinessHeader = $this->config->get('operations.readiness.header');
        $readinessSecret = $this->config->get('operations.readiness.secret');

        return filled($this->config->get('app.key'))
            && ! (bool) $this->config->get('app.debug')
            && filled($readinessHeader)
            && filled($readinessSecret)
            && $this->config->get('queue.default') === 'redis'
            && $this->config->get('cache.default') === 'redis'
            && ($defaultSupervisor['connection'] ?? null) === 'redis'
            && ($defaultSupervisor['queue'] ?? null) === ['default']
            && ($audioSupervisor['connection'] ?? null) === 'redis'
            && ($audioSupervisor['queue'] ?? null) === ['article-audio']
            && $audioJobTimeout > 0
            && $audioJobTimeout < $audioSupervisorTimeout
            && $audioSupervisorTimeout < $retryAfter;
    }

    private function buildRevisionIsValid(): bool
    {
        $path = (string) $this->config->get('operations.build_revision_path');

        if ($path === '' || ! $this->files->isFile($path) || ! $this->files->isReadable($path)) {
            return false;
        }

        return preg_match('/\\A[0-9a-f]{7,64}\\s*\\z/i', $this->files->get($path)) === 1;
    }

    /** @param Closure(): bool $probe */
    private function probe(Closure $probe): bool
    {
        try {
            return $probe();
        } catch (Throwable) {
            return false;
        }
    }
}
