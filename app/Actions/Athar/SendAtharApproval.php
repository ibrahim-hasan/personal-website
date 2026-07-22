<?php

namespace App\Actions\Athar;

use App\Enums\AtharContributionStatus;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharPublicationVersion;
use App\Notifications\AtharApprovalNotification;
use Illuminate\Support\Facades\Notification;

class SendAtharApproval
{
    public function handle(AtharPublicationVersion $version): AtharPublicationVersion
    {
        abort_unless($version->status === AtharPublicationStatus::Draft, 422);
        $version->forceFill(['status' => AtharPublicationStatus::AwaitingApproval, 'sent_for_approval_at' => now()])->save();
        $invitation = $version->contribution->invitation;
        if (filled($invitation->email)) {
            Notification::route('mail', $invitation->email)->notify(new AtharApprovalNotification(
                localized_route('athar.show', ['token' => $invitation->token_ciphertext], true, $invitation->preferred_locale),
                $invitation->preferred_locale,
                $version->placement->value,
            ));
        }
        $version->contribution->forceFill(['status' => AtharContributionStatus::AwaitingApproval])->save();

        return $version->fresh();
    }
}
