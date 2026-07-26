<?php

namespace Tests\Unit\Security;

use App\Services\Security\CspReportSanitizer;
use PHPUnit\Framework\TestCase;

class CspReportSanitizerTest extends TestCase
{
    public function test_it_reduces_a_legacy_report_to_a_safe_controlled_signal(): void
    {
        $signals = (new CspReportSanitizer)->signals([
            'csp-report' => [
                'document-uri' => 'https://private.example.test/reader?token=very-secret',
                'blocked-uri' => 'https://untrusted.example.test/script.js?email=person@example.test',
                'script-sample' => 'window.secret = "do-not-store"',
                'violated-directive' => "script-src-elem 'self'",
            ],
        ]);

        $this->assertSame([
            ['directive' => 'script-src', 'category' => 'script'],
        ], $signals);

        $serializedSignals = json_encode($signals, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('private.example.test', $serializedSignals);
        $this->assertStringNotContainsString('very-secret', $serializedSignals);
        $this->assertStringNotContainsString('untrusted.example.test', $serializedSignals);
        $this->assertStringNotContainsString('do-not-store', $serializedSignals);
    }

    public function test_it_accepts_reporting_api_batches_without_retaining_unknown_fields(): void
    {
        $signals = (new CspReportSanitizer)->signals([
            [
                'type' => 'csp-violation',
                'body' => [
                    'effectiveDirective' => 'style-src-attr',
                    'documentURL' => 'https://private.example.test/?session=secret',
                ],
            ],
            [
                'type' => 'network-error',
                'body' => ['phase' => 'application'],
            ],
            [
                'type' => 'csp-violation',
                'body' => [
                    'effectiveDirective' => 'future-directive',
                    'blockedURL' => 'https://other.example.test/?private=value',
                ],
            ],
        ]);

        $this->assertSame([
            ['directive' => 'style-src', 'category' => 'style'],
            ['directive' => 'other', 'category' => 'other'],
        ], $signals);
    }
}
