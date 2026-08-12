<?php

namespace App\Services\WebsitePerformance;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Str;
use JsonException;

class WebsitePerformanceSnapshotStore
{
    private const SnapshotDirectoryPrefix = 'website-performance';

    public function __construct(
        private readonly FilesystemFactory $storage,
        private readonly ConfigRepository $config,
    ) {}

    /**
     * @param  array<string, mixed>  $report
     */
    public function persist(array $report): string
    {
        $this->assertAggregateOnly($report);
        $generatedAt = CarbonImmutable::parse((string) ($report['generated_at'] ?? now()));
        $directory = $this->directory();

        $path = sprintf(
            '%s/%s/%s-%s.json',
            $directory,
            $generatedAt->utc()->format('Y/m/d'),
            $generatedAt->utc()->format('Ymd\\THis.u\\Z'),
            (string) Str::uuid(),
        );

        try {
            $json = json_encode(
                $report,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            throw new WebsitePerformanceSourceException('snapshot_serialization_unavailable');
        }

        $disk = $this->disk();

        if (! $disk->put($path, $json, 'private')) {
            throw new WebsitePerformanceSourceException('snapshot_storage_unavailable');
        }

        $this->prune($directory, $generatedAt);

        return $path;
    }

    private function prune(string $directory, CarbonImmutable $generatedAt): void
    {
        $disk = $this->disk();
        $before = $generatedAt->subMonthsNoOverflow(13)->getTimestamp();

        foreach ($disk->allFiles($directory) as $path) {
            if ($disk->lastModified($path) < $before) {
                $disk->delete($path);
            }
        }
    }

    private function disk(): FilesystemAdapter
    {
        $diskName = (string) $this->config->get('services.website_performance.snapshot_disk', 'local');
        $root = rtrim((string) $this->config->get('filesystems.disks.local.root'), DIRECTORY_SEPARATOR);
        $privateRoot = rtrim(storage_path('app/private'), DIRECTORY_SEPARATOR);

        if ($diskName !== 'local'
            || $root === ''
            || ($root !== $privateRoot && ! str_starts_with($root, $privateRoot.DIRECTORY_SEPARATOR))) {
            throw new WebsitePerformanceSourceException('snapshot_configuration_unavailable');
        }

        return $this->storage->disk('local');
    }

    private function directory(): string
    {
        $directory = trim((string) $this->config->get(
            'services.website_performance.snapshot_directory',
            self::SnapshotDirectoryPrefix,
        ), '/');

        if (preg_match(
            '/\Awebsite-performance(?:\/[A-Za-z0-9][A-Za-z0-9_-]{0,63})*\z/',
            $directory,
        ) !== 1) {
            throw new WebsitePerformanceSourceException('snapshot_configuration_unavailable');
        }

        return $directory;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function assertAggregateOnly(array $report): void
    {
        foreach ($report as $key => $value) {
            $key = strtolower((string) $key);

            if (in_array($key, [
                'name',
                'email',
                'phone',
                'company',
                'role',
                'challenge',
                'timing',
                'message',
                'notes',
                'id',
                'reference',
                'hash',
                'public_reference',
                'submission_hash',
                'submission_id',
                'record_id',
                'inquiry_id',
            ], true)
                || str_ends_with($key, '_id')
                || str_ends_with($key, '_hash')
                || str_ends_with($key, '_reference')
                || str_ends_with($key, '_identifier')) {
                throw new WebsitePerformanceSourceException('snapshot_privacy_unavailable');
            }

            if (is_array($value)) {
                $this->assertAggregateOnly($value);
            }
        }
    }
}
