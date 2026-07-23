<?php

namespace App\Support;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Request;

/**
 * Cloudflare Turnstile integration helper.
 *
 * Performs the canonical server-side siteverify call before an existing
 * form handler runs. The secret is read from the environment as
 * TURNSTILE_SECRET and is never inlined in source.
 */
class Turnstile
{
    private const SITEVERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(private readonly HttpFactory $http) {}

    /**
     * The public site key rendered into the widget, or null when unconfigured.
     */
    public function siteKey(): ?string
    {
        $key = config('services.turnstile.site_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    /**
     * Whether the widget should render and tokens should be verified.
     *
     * Turnstile is considered enabled when both a site key and a secret are
     * present. With no secret (local dev, the test suite), verification is
     * bypassed so forms stay usable and tests stay green.
     */
    public function enabled(): bool
    {
        return $this->siteKey() !== null
            && filled(config('services.turnstile.secret'));
    }

    /**
     * Validate a Turnstile token against Cloudflare's siteverify endpoint.
     *
     * Returns true when the parsed response has `success === true`. When
     * Turnstile is not enabled (no secret configured), verification is
     * skipped and the request is allowed through.
     */
    public function verify(string $token, ?string $remoteIp = null): bool
    {
        $secret = config('services.turnstile.secret');

        if (! filled($secret)) {
            return true;
        }

        if ($token === '') {
            return false;
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        if ($remoteIp !== null && $remoteIp !== '') {
            $payload['remoteip'] = $remoteIp;
        }

        $result = $this->http
            ->asForm()
            ->post(self::SITEVERIFY_URL, $payload)
            ->json();

        return is_array($result) && ($result['success'] ?? false) === true;
    }

    /**
     * Resolve the client IP from the incoming request for siteverify's remoteip.
     */
    public function clientIp(Request $request): ?string
    {
        $ip = $request->ip();

        return is_string($ip) && $ip !== '' ? $ip : null;
    }
}
