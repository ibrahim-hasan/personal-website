<?php

namespace App\Support;

use App\Models\AtharInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AtharAccess
{
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

    public static function verified(Request $request, AtharInvitation $invitation): bool
    {
        return $invitation->isAccessible();
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
