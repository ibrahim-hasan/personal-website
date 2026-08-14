<?php

namespace App\Console\Commands;

use App\Services\WebsitePerformance\WebsitePerformanceSnapshotStore;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('website:performance-sanitize-snapshots')]
#[Description('Replace legacy website performance snapshots with strict aggregate-only projections')]
class SanitizeWebsitePerformanceSnapshots extends Command
{
    public function handle(WebsitePerformanceSnapshotStore $snapshots): int
    {
        try {
            $result = $snapshots->sanitizeSnapshots();
        } catch (Throwable) {
            $this->components->error('Website performance snapshots could not be sanitized safely.');

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Website performance snapshots: %d scanned; %d rewritten; %d already safe; %d unavailable.',
            $result['scanned'],
            $result['rewritten'],
            $result['already_safe'],
            $result['unavailable'],
        ));

        return self::SUCCESS;
    }
}
