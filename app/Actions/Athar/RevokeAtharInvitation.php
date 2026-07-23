<?php

namespace App\Actions\Athar;

use App\Enums\AtharInvitationStatus;
use App\Models\AtharInvitation;
use Illuminate\Support\Facades\DB;

class RevokeAtharInvitation
{
    public function handle(AtharInvitation $invitation): AtharInvitation
    {
        return DB::transaction(function () use ($invitation): AtharInvitation {
            $record = AtharInvitation::query()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(in_array($record->status, [AtharInvitationStatus::Revoked, AtharInvitationStatus::Completed], true), 422);

            $record->forceFill([
                'status' => AtharInvitationStatus::Revoked,
                'revoked_at' => now(),
            ])->save();

            return $record->fresh();
        });
    }
}
