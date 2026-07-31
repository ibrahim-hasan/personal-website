<?php

namespace App\Actions\Athar;

use App\Enums\AtharInvitationStatus;
use App\Models\AtharInvitation;
use App\Support\AtharAccess;
use Illuminate\Http\Request;

class GrantAtharEmailAccess
{
    public function handle(AtharInvitation $invitation, Request $request): void
    {
        $invitation->forceFill([
            'verified_at' => now(),
            'status' => $invitation->status === AtharInvitationStatus::Sent
                ? AtharInvitationStatus::Verified
                : $invitation->status,
        ])->save();

        AtharAccess::grant($request, $invitation, 'email_link');
    }
}
