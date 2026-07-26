<?php

namespace App\Actions\Athar;

use App\Enums\AtharInvitationStatus;
use App\Models\AtharInvitation;

class ExpireAtharInvitations
{
    public function handle(): int
    {
        return AtharInvitation::query()
            ->whereNull('revoked_at')
            ->whereDoesntHave('contribution')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->whereIn('status', [AtharInvitationStatus::Ready, AtharInvitationStatus::Sent, AtharInvitationStatus::Verified])
            ->update(['status' => AtharInvitationStatus::Expired]);
    }
}
