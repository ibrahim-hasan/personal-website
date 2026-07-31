<?php

namespace App\Support;

use App\Enums\AtharInvitationDeliveryMode;
use App\Models\AtharAccessChallenge;
use App\Models\AtharInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class AtharAccess
{
    /**
     * How long an email-code session grant remains valid, in minutes.
     */
    public static function sessionTtlMinutes(): int
    {
        return max(1, (int) config('athar.access.session_ttl_minutes', 3 * 24 * 60));
    }

    public static function resendCooldownSeconds(): int
    {
        return max(1, (int) config('athar.access.resend_cooldown_seconds', 60));
    }

    public static function maxCodeRequestsPerHour(): int
    {
        return max(1, (int) config('athar.access.max_code_requests_per_hour', 5));
    }

    public static function tokenHash(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }

    public static function codeHash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    public static function normalizeCode(string $code): string
    {
        return preg_replace('/\s+/u', '', strtr(trim($code), [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ])) ?? '';
    }

    public static function newToken(): string
    {
        return Str::random(64);
    }

    public static function invitation(string $token): ?AtharInvitation
    {
        return AtharInvitation::query()->where('token_hash', self::tokenHash($token))->first();
    }

    public static function latestChallenge(AtharInvitation $invitation): ?AtharAccessChallenge
    {
        return AtharAccessChallenge::query()
            ->where('invitation_id', $invitation->getKey())
            ->latest('id')
            ->first();
    }

    public static function resendAvailableAt(?AtharAccessChallenge $challenge): ?int
    {
        if ($challenge?->requested_at === null) {
            return null;
        }

        return $challenge->requested_at->addSeconds(self::resendCooldownSeconds())->timestamp;
    }

    public static function codeRequestRateLimitKey(Request $request, AtharInvitation $invitation): string
    {
        $ipHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));

        return 'athar-code-request|'.$invitation->getKey().'|'.$ipHash;
    }

    public static function grant(Request $request, AtharInvitation $invitation): void
    {
        $grant = [
            'verified_at' => now()->timestamp,
            'fingerprint' => self::fingerprint($request),
        ];
        $request->session()->put(self::sessionKey($invitation), $grant);
        Cookie::queue(cookie(
            self::cookieName($invitation),
            json_encode($grant, JSON_THROW_ON_ERROR),
            self::sessionTtlMinutes(),
            config('session.path', '/'),
            config('session.domain'),
            (bool) (config('session.secure') ?? $request->isSecure()),
            true,
            false,
            config('session.same_site', 'lax'),
        ));
    }

    /**
     * Whether the holder of the token may act on the invitation.
     *
     * A link-mode invitation is accessible solely by holding the token (the
     * link is the credential). An email-mode invitation additionally requires
     * a live, fingerprint-bound session grant established by verifying the
     * six-digit access code — possession of the token alone is not enough.
     */
    public static function verified(Request $request, AtharInvitation $invitation): bool
    {
        if (! $invitation->isAccessible()) {
            return false;
        }

        if ($invitation->delivery_mode === AtharInvitationDeliveryMode::Link) {
            return true;
        }

        $grant = $request->session()->get(self::sessionKey($invitation));
        if (! is_array($grant)) {
            $grant = self::cookieGrant($request, $invitation);
        }

        if (! is_array($grant)) {
            return false;
        }

        $verifiedAt = $grant['verified_at'] ?? null;
        $fingerprint = $grant['fingerprint'] ?? null;

        if (! is_int($verifiedAt) || ! is_string($fingerprint)) {
            return false;
        }

        if (now()->timestamp - $verifiedAt >= self::sessionTtlMinutes() * 60) {
            return false;
        }

        return hash_equals(self::fingerprint($request), $fingerprint);
    }

    /**
     * The consent-audit attestation of how this invitation was accessed.
     */
    public static function verificationMethod(AtharInvitation $invitation): string
    {
        return $invitation->delivery_mode === AtharInvitationDeliveryMode::Link ? 'link' : 'email_code';
    }

    public static function forget(Request $request, AtharInvitation $invitation): void
    {
        $request->session()->forget(self::sessionKey($invitation));
        Cookie::queue(Cookie::forget(self::cookieName($invitation), config('session.path', '/'), config('session.domain')));
    }

    private static function sessionKey(AtharInvitation $invitation): string
    {
        return 'athar.verified.'.$invitation->getKey();
    }

    private static function cookieName(AtharInvitation $invitation): string
    {
        return 'athar-verified-'.$invitation->getKey();
    }

    private static function cookieGrant(Request $request, AtharInvitation $invitation): ?array
    {
        $value = $request->cookie(self::cookieName($invitation));
        if (! is_string($value) || $value === '') {
            return null;
        }

        $grant = json_decode($value, true);

        return is_array($grant) ? $grant : null;
    }

    private static function fingerprint(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->userAgent().'|'.$request->ip(), (string) config('app.key'));
    }
}
