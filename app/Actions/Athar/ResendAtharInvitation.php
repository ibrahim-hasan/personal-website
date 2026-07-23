<?php

namespace App\Actions\Athar;

use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Models\AtharInvitation;
use App\Notifications\AtharInvitationNotification;
use Illuminate\Support\Facades\Notification;

class ResendAtharInvitation
{
    public function handle(AtharInvitation $invitation): void
    {
        abort_unless($invitation->delivery_mode === AtharInvitationDeliveryMode::Email, 422);
        abort_if(in_array($invitation->status, [AtharInvitationStatus::Revoked, AtharInvitationStatus::Expired], true), 422);
        abort_unless(is_string($invitation->email) && $invitation->email !== '', 422);

        $shareUrl = localized_route('athar.show', ['token' => $invitation->token_ciphertext], true, $invitation->preferred_locale);

        Notification::route('mail', $invitation->email)->notify(new AtharInvitationNotification(
            $shareUrl,
            $invitation->preferred_locale,
        ));
    }
}
