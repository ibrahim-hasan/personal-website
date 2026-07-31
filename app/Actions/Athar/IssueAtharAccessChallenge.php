<?php

namespace App\Actions\Athar;

use App\Models\AtharAccessChallenge;
use App\Models\AtharInvitation;
use App\Notifications\AtharAccessCodeNotification;
use App\Support\AtharAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class IssueAtharAccessChallenge
{
    public function handle(AtharInvitation $invitation, Request $request): AtharAccessChallenge
    {
        $code = (string) random_int(100000, 999999);

        return DB::transaction(function () use ($code, $invitation, $request): AtharAccessChallenge {
            AtharAccessChallenge::query()->where('invitation_id', $invitation->getKey())->whereNull('consumed_at')->update(['consumed_at' => now()]);
            $challenge = AtharAccessChallenge::query()->create(['invitation_id' => $invitation->getKey(), 'code_hash' => AtharAccess::codeHash($code), 'expires_at' => now()->addMinutes((int) config('athar.access.code_ttl_minutes', 10)), 'requested_at' => now(), 'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'))]);
            Notification::route('mail', $invitation->email)->notifyNow(new AtharAccessCodeNotification($code, $invitation->preferred_locale));

            return $challenge;
        });
    }
}
