<?php

namespace App\Actions\Athar;

use App\Enums\AtharContributionStatus;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharContribution;

class CancelAtharPrivateDataDeletion
{
    public function handle(AtharContribution $contribution): AtharContribution
    {
        abort_unless($contribution->status === AtharContributionStatus::DeletionRequested, 422);

        $versionStatus = $contribution->publicationVersions()->latest('version')->first()?->status;

        $status = match ($versionStatus) {
            AtharPublicationStatus::Withdrawn => AtharContributionStatus::Withdrawn,
            AtharPublicationStatus::Hidden => AtharContributionStatus::Published,
            AtharPublicationStatus::AwaitingApproval, AtharPublicationStatus::Draft => AtharContributionStatus::AwaitingApproval,
            default => AtharContributionStatus::Published,
        };

        $contribution->forceFill([
            'status' => $status,
            'deletion_requested_at' => null,
            // No published version had been left when deletion was requested, so
            // the row was queued for purge. Cancelling keeps it around.
            'deleted_at' => null,
        ])->save();

        return $contribution->fresh();
    }
}
