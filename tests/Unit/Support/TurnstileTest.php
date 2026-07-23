<?php

namespace Tests\Unit\Support;

use App\Rules\TurnstileToken;
use App\Support\Turnstile;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    public function test_verification_is_bypassed_when_no_secret_is_configured(): void
    {
        config()->set('services.turnstile.secret', null);

        $turnstile = app(Turnstile::class);

        $this->assertFalse($turnstile->enabled());
        // Any token (or none) passes when Turnstile is not configured.
        $this->assertTrue($turnstile->verify('anything'));
        $this->assertTrue($turnstile->verify(''));
    }

    public function test_verify_returns_true_when_siteverify_reports_success(): void
    {
        config()->set('services.turnstile.secret', 'test-secret');

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $turnstile = app(Turnstile::class);

        $this->assertTrue($turnstile->enabled());
        $this->assertTrue($turnstile->verify('valid-token', '203.0.113.9'));

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
                && str_contains((string) $request->body(), 'secret=test-secret')
                && str_contains((string) $request->body(), 'response=valid-token')
                && str_contains((string) $request->body(), 'remoteip=203.0.113.9');
        });
    }

    public function test_verify_returns_false_when_siteverify_reports_failure(): void
    {
        config()->set('services.turnstile.secret', 'test-secret');

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $this->assertFalse(app(Turnstile::class)->verify('bad-or-expired-token'));
    }

    public function test_an_empty_token_is_rejected_once_a_secret_is_configured(): void
    {
        config()->set('services.turnstile.secret', 'test-secret');

        Http::fake();

        $this->assertFalse(app(Turnstile::class)->verify(''));

        Http::assertNothingSent();
    }

    public function test_the_rule_rejects_a_failed_verification(): void
    {
        config()->set('services.turnstile.secret', 'test-secret');

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
        ]);

        $failed = false;

        app(TurnstileToken::class)->validate(
            'cf-turnstile-response',
            'bad-token',
            function (string $message) use (&$failed): void {
                $failed = true;
                $this->assertSame('validation.turnstile', $message);
            },
        );

        $this->assertTrue($failed);
    }

    public function test_the_rule_enabled_flag_matches_the_service(): void
    {
        config()->set('services.turnstile.secret', null);
        $this->assertFalse(app(TurnstileToken::class)->enabled());

        config()->set('services.turnstile.secret', 'test-secret');
        $this->assertTrue(app(TurnstileToken::class)->enabled());
    }
}
