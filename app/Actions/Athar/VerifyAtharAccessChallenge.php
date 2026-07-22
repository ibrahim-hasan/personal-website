<?php

namespace App\Actions\Athar;

use App\Models\AtharAccessChallenge;
use App\Models\AtharInvitation;
use App\Support\AtharAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifyAtharAccessChallenge
{
    public function handle(AtharInvitation $invitation, string $code, Request $request): bool
    {
        return DB::transaction(function () use ($invitation, $code, $request): bool {
            $challenge = AtharAccessChallenge::query()->where('invitation_id', $invitation->getKey())->latest('id')->lockForUpdate()->first();
            if ($challenge === null || ! $challenge->isUsable()) {
                return false;
            }
            if (! hash_equals($challenge->code_hash, AtharAccess::codeHash($code))) {
                $challenge->increment('attempts');

                return false;
            }
            $challenge->forceFill(['consumed_at' => now()])->save();
            $invitation->forceFill(['verified_at' => now()])->save();
            AtharAccess::grant($request, $invitation);

            return true;
        });
    }
}
