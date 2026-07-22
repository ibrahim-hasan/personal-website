<?php

namespace App\Console\Commands;

use App\Models\EditorialApiAuditLog;
use App\Models\EditorialApiIdempotencyKey;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:purge-editorial-api-audit-logs')]
#[Description('Purge editorial API audit events beyond their retention period')]
class PurgeEditorialApiAuditLogs extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = EditorialApiAuditLog::query()
            ->where('occurred_at', '<', now()->subDays((int) config('editorial-api.audit_retention_days')))
            ->delete();
        EditorialApiIdempotencyKey::query()->where('expires_at', '<=', now())->delete();

        $this->info("Purged {$deleted} editorial API audit event(s).");

        return self::SUCCESS;
    }
}
