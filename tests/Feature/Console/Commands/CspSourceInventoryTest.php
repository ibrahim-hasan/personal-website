<?php

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;

class CspSourceInventoryTest extends TestCase
{
    public function test_it_reports_known_inline_debt_without_failing_normal_ci(): void
    {
        $this->artisan('security:csp-inventory')
            ->expectsOutputToContain('Inline style attributes:')
            ->expectsOutputToContain('Inline event handlers:')
            ->expectsOutputToContain('Configured CSP third-party origins:')
            ->expectsOutputToContain('www.google-analytics.com')
            ->expectsOutputToContain('CSP enforcement readiness: BLOCKED.')
            ->assertExitCode(0);
    }

    public function test_it_can_be_used_as_an_explicit_enforcement_readiness_gate(): void
    {
        $this->artisan('security:csp-inventory', ['--assert-enforceable' => true])
            ->expectsOutputToContain('CSP enforcement readiness: BLOCKED.')
            ->assertExitCode(1);
    }
}
