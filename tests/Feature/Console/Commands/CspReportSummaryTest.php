<?php

namespace Tests\Feature\Console\Commands;

use App\Services\Security\CspReportSignalStore;
use Tests\TestCase;

class CspReportSummaryTest extends TestCase
{
    public function test_it_outputs_only_controlled_aggregate_signal_counts(): void
    {
        app(CspReportSignalStore::class)->record('script-src', 'script');

        $this->artisan('security:csp-report-summary', ['--minutes' => 1])
            ->expectsOutputToContain('Controlled CSP report signals in the last 1 minute(s):')
            ->expectsOutputToContain('Directive | Category | Count')
            ->expectsOutputToContain('script-src | script | 1')
            ->assertExitCode(0);
    }
}
