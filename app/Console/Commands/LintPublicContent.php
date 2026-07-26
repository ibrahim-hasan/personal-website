<?php

namespace App\Console\Commands;

use App\Support\ContentLint\PublicContentLinter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('content:lint')]
#[Description('Lint public source copy and literal translation references without mutating content')]
class LintPublicContent extends Command
{
    public function handle(PublicContentLinter $linter): int
    {
        $report = $linter->inspectApplication();

        $this->components->info(sprintf(
            'Checked %d scoped public sources, %d literal translation keys, and %d Arabic plural strings.',
            $report->sourceCount,
            $report->translationKeyCount,
            $report->pluralKeyCount,
        ));

        foreach ($report->findings as $finding) {
            $this->components->error("[{$finding->rule}] {$finding->source}: {$finding->message}");
        }

        if ($report->hasFailures()) {
            $this->components->warn('Static lint checks public templates and loaded public translation groups. Publication validators cover persisted Service and Project content.');

            return self::FAILURE;
        }

        $this->components->info('Public content lint passed.');

        return self::SUCCESS;
    }
}
