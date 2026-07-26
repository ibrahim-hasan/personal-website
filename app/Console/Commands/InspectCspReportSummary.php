<?php

namespace App\Console\Commands;

use App\Services\Security\CspReportSignalStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('security:csp-report-summary {--minutes=15 : Number of recent minutes to summarize, up to 15}')]
#[Description('Show the redacted aggregate CSP report signal counts')]
class InspectCspReportSummary extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CspReportSignalStore $signals): int
    {
        $minutes = min(15, max(1, (int) $this->option('minutes')));
        $counts = $signals->recentCounts($minutes);

        if ($counts === []) {
            $this->components->info("No controlled CSP report signals in the last {$minutes} minute(s).");

            return self::SUCCESS;
        }

        $this->components->info("Controlled CSP report signals in the last {$minutes} minute(s):");
        $this->line('Directive | Category | Count');

        foreach ($counts as $signal) {
            $this->line("{$signal['directive']} | {$signal['category']} | {$signal['count']}");
        }

        return self::SUCCESS;
    }
}
