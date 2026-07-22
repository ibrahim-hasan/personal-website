<?php

namespace App\Console\Commands\Privacy;

use App\Actions\Athar\PurgeExpiredAtharData;
use App\Actions\Privacy\PurgeExpiredPersonalData as Purger;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('privacy:purge-expired-data {--force : Permanently delete eligible records after reviewing the preview}')]
#[Description('Preview or purge archived inquiries and resolved moderation reports beyond their retention period')]
class PurgeExpiredPersonalData extends Command
{
    public function handle(Purger $purger, PurgeExpiredAtharData $athar): int
    {
        $preview = $purger->preview($athar);

        $this->components->info(sprintf(
            'Eligible records: %d archived inquiries; %d resolved or dismissed reports.',
            $preview['archived_inquiries'],
            $preview['resolved_reports'],
        ));
        $this->components->info(sprintf(
            'Athar eligible records: %d challenges; %d deleted notes.',
            $preview['athar_challenges'],
            $preview['athar_contributions'],
        ));

        if (! $this->option('force')) {
            $this->components->warn('Preview only. Re-run with --force after enabling PRIVACY_RETENTION_ENABLED=true.');

            return self::SUCCESS;
        }

        if (! config('legal.retention.enabled')) {
            $this->components->error('Deletion is disabled. Set PRIVACY_RETENTION_ENABLED=true before using --force.');

            return self::FAILURE;
        }

        $purged = $purger->purge($athar);

        $this->components->info(sprintf(
            'Purged %d archived inquiries and %d resolved or dismissed reports.',
            $purged['archived_inquiries'],
            $purged['resolved_reports'],
        ));
        $this->components->info(sprintf(
            'Purged %d Athar challenges and %d deleted notes.',
            $purged['athar_challenges'],
            $purged['athar_contributions'],
        ));

        return self::SUCCESS;
    }
}
