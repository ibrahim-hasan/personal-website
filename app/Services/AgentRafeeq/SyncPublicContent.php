<?php

namespace App\Services\AgentRafeeq;

use App\Support\AgentRafeeqConfiguration;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SyncPublicContent
{
    public function __construct(private PublicContentExporter $exporter, private Filesystem $files) {}

    /** @return array{upserted: int, withdrawn: int} */
    public function run(): array
    {
        $baseUrl = AgentRafeeqConfiguration::safeUrl(config('services.agent_rafeeq_sync.api_url'), originOnly: true);
        $secret = config('services.agent_rafeeq_sync.secret_key');
        $publicKey = config('services.agent_rafeeq_widget.public_key');

        if ($baseUrl === null || ! is_string($secret) || ! preg_match('/\Ask_[A-Za-z0-9_-]+\z/D', $secret)
            || ! is_string($publicKey) || ! preg_match('/\Apk_[A-Za-z0-9_-]+\z/D', $publicKey)) {
            throw new RuntimeException('The server-side assistant sync configuration is incomplete or unsafe.');
        }

        $directory = storage_path('app/private/agent-rafeeq');
        $this->files->ensureDirectoryExists($directory, 0700);
        $lock = fopen($directory.'/sync.lock', 'c');

        if ($lock === false) {
            throw new RuntimeException('The assistant sync lock could not be opened.');
        }

        chmod($directory.'/sync.lock', 0600);

        try {
            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('An assistant content sync is already running.');
            }

            $destination = hash('sha256', $baseUrl."\0".$publicKey);
            $statePath = $directory.'/sync-state.json';
            $known = $this->readState($statePath, $destination);
            $manifest = $this->exporter->manifest();
            $items = $manifest['items'];
            $current = array_column($items, 'external_id');

            if ($items === [] || count($items) > 10000 || count($current) !== count($items)
                || count(array_unique($current)) !== count($current) || ! $this->validIds($current)
                || strlen(json_encode($items, JSON_THROW_ON_ERROR)) > 10 * 1024 * 1024) {
                throw new RuntimeException('The public content snapshot is empty, invalid, or exceeds the sync limit.');
            }

            // Persist intent before requests so an interrupted successful upsert
            // remains tracked and can be withdrawn if later unpublished.
            $this->writeState($statePath, $destination, array_values(array_unique([...$known, ...$current])));
            $withdrawn = array_values(array_diff($known, $current));

            foreach ($withdrawn as $externalId) {
                $response = $this->client($secret)->delete($baseUrl.'/api/v1/content/items/'.rawurlencode($externalId));

                if (! $response->successful() && $response->status() !== 404) {
                    throw new RuntimeException('Assistant content withdrawal failed (HTTP '.$response->status().').');
                }
            }

            foreach (array_chunk($items, 100) as $batch) {
                $response = $this->client($secret)->post($baseUrl.'/api/v1/content/items:upsert', ['items' => $batch]);
                $results = $response->json('items');

                if (! $response->successful()) {
                    throw new RuntimeException('Assistant content sync failed (HTTP '.$response->status().').');
                }

                $expected = array_column($batch, 'external_id');
                $received = is_array($results) ? array_column($results, 'external_id') : [];
                sort($expected);
                sort($received);

                if ($received !== $expected) {
                    throw new RuntimeException('The assistant did not acknowledge every content item.');
                }
            }

            $this->writeState($statePath, $destination, $current, completed: true);

            return ['upserted' => count($current), 'withdrawn' => count($withdrawn)];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function client(string $secret): PendingRequest
    {
        return Http::withToken($secret)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(30)
            ->withoutRedirecting();
    }

    /** @return list<string> */
    private function readState(string $path, string $destination): array
    {
        if (! $this->files->exists($path)) {
            return [];
        }

        $state = json_decode($this->files->get($path), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($state) || ($state['version'] ?? null) !== 1 || ($state['destination'] ?? null) !== $destination
            || ! is_array($state['ids'] ?? null) || ! array_is_list($state['ids']) || ! $this->validIds($state['ids'])) {
            throw new RuntimeException('The assistant sync ledger is invalid or belongs to another destination.');
        }

        return $state['ids'];
    }

    /** @param list<string> $ids */
    private function writeState(string $path, string $destination, array $ids, bool $completed = false): void
    {
        sort($ids);
        $encoded = json_encode([
            'version' => 1,
            'destination' => $destination,
            'ids' => $ids,
            'completed_at' => $completed ? now()->toIso8601String() : null,
        ], JSON_THROW_ON_ERROR);
        $this->files->replace($path, $encoded, 0600);

        if (! $this->files->exists($path) || $this->files->get($path) !== $encoded) {
            throw new RuntimeException('The assistant sync ledger could not be saved.');
        }
    }

    /** @param array<mixed> $ids */
    private function validIds(array $ids): bool
    {
        foreach ($ids as $id) {
            if (! is_string($id) || strlen($id) > 255 || preg_match('/\Aibrahim-website:(?:work|service|article):[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $id) !== 1) {
                return false;
            }
        }

        return true;
    }
}
