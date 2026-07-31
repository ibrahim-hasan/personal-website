<?php

namespace App\Actions\Athar;

use App\Enums\AtharAccessChallengeResult;
use App\Enums\AtharInvitationStatus;
use App\Models\AtharAccessChallenge;
use App\Models\AtharInvitation;
use App\Support\AtharAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifyAtharAccessChallenge
{
    public function handle(AtharInvitation $invitation, string $code, Request $request): AtharAccessChallengeResult
    {
        return DB::transaction(function () use ($invitation, $code, $request): AtharAccessChallengeResult {
            $challenge = AtharAccessChallenge::query()->where('invitation_id', $invitation->getKey())->latest('id')->lockForUpdate()->first();
            if ($challenge === null) {
                return AtharAccessChallengeResult::Unavailable;
            }
            if ($challenge->isLocked()) {
                return AtharAccessChallengeResult::Locked;
            }
            if ($challenge->isExpired()) {
                return AtharAccessChallengeResult::Expired;
            }
            if ($challenge->consumed_at !== null) {
                return AtharAccessChallengeResult::Unavailable;
            }
            if (! hash_equals($challenge->code_hash, AtharAccess::codeHash($code))) {
                $challenge->increment('attempts');

                return $challenge->fresh()->isLocked()
                    ? AtharAccessChallengeResult::Locked
                    : AtharAccessChallengeResult::Invalid;
            }
            $challenge->forceFill(['consumed_at' => now()])->save();
            $invitation->forceFill([
                'verified_at' => now(),
                'status' => $invitation->status === AtharInvitationStatus::Sent ? AtharInvitationStatus::Verified : $invitation->status,
            ])->save();
            AtharAccess::grant($request, $invitation);

            return AtharAccessChallengeResult::Verified;
        });
    }
}
