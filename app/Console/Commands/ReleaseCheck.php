<?php

namespace App\Console\Commands;

use App\Services\Operations\ReleaseReadiness;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:release-check')]
#[Description('Run redacted readiness checks for a release candidate')]
class ReleaseCheck extends Command
{
    public function handle(ReleaseReadiness $readiness): int
    {
        $report = $readiness->report();

        foreach ($report as $name => $check) {
            $label = str_replace('_', ' ', $name);

            if (! $check['required']) {
                $this->components->warn(ucfirst($label).': not required in this environment.');

                continue;
            }

            if ($check['passed']) {
                $this->components->info(ucfirst($label).': ready.');

                continue;
            }

            $this->components->error(ucfirst($label).': unavailable.');
        }

        return $readiness->passes($report) ? self::SUCCESS : self::FAILURE;
    }
}
