<?php

namespace App\Console\Commands;

use App\Services\AgentRafeeq\PublicContentExporter;
use App\Services\AgentRafeeq\SyncPublicContent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('agent-rafeeq:sync {--dry-run : Count the current public snapshot without sending or changing sync state}')]
#[Description('Sync published website content to Agent Rafeeq and withdraw previously synced content that is no longer public')]
class SyncAgentRafeeqContent extends Command
{
    public function handle(PublicContentExporter $exporter, SyncPublicContent $sync): int
    {
        if (! $this->option('dry-run') && ! config('services.agent_rafeeq_sync.enabled')) {
            $this->components->info('Assistant content sync is disabled.');

            return self::SUCCESS;
        }

        try {
            if ($this->option('dry-run')) {
                $this->components->info('Public content snapshot: '.count($exporter->manifest()['items']).' items. No requests sent.');

                return self::SUCCESS;
            }

            $result = $sync->run();
            $this->components->info('Assistant content accepted: '.$result['upserted'].' items; withdrawn: '.$result['withdrawn'].'. Indexing runs on Agent Rafeeq.');
            Log::info('Agent Rafeeq public content sync completed.', $result);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            // Provider responses and exception messages may contain request data.
            Log::warning('Agent Rafeeq public content sync failed.', ['exception_type' => $exception::class]);
            $this->components->error('Assistant content sync failed. Check configuration, connection, and the private sync ledger; rerun after resolving the issue.');

            return self::FAILURE;
        }
    }
}
