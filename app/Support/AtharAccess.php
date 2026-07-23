<?php

namespace App\Support;

use App\Enums\AtharInvitationDeliveryMode;
use App\Models\AtharInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AtharAccess
{
    /**
     * How long an email-code session grant remains valid, in minutes.
     */
    public const int SESSION_TTL_MINUTES = 60;

    public static function tokenHash(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }

    public static function codeHash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    public static function newToken(): string
    {
        return Str::random(64);
    }

    public static function invitation(string $token): ?AtharInvitation
    {
        return AtharInvitation::query()->where('token_hash', self::tokenHash($token))->first();
    }

    public static function grant(Request $request, AtharInvitation $invitation): void
    {
        $request->session()->put(self::sessionKey($invitation), [
            'verified_at' => now()->timestamp,
            'fingerprint' => self::fingerprint($request),
        ]);
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
            return false;
        }

        $verifiedAt = $grant['verified_at'] ?? null;
        $fingerprint = $grant['fingerprint'] ?? null;

        if (! is_int($verifiedAt) || ! is_string($fingerprint)) {
            return false;
        }

        if (now()->timestamp - $verifiedAt > self::SESSION_TTL_MINUTES * 60) {
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
    }

    private static function sessionKey(AtharInvitation $invitation): string
    {
        return 'athar.verified.'.$invitation->getKey();
    }

    private static function fingerprint(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->userAgent().'|'.$request->ip(), (string) config('app.key'));
    }
}
