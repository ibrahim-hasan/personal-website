<?php

namespace App\Actions\Athar;

use App\Enums\AtharContributionStatus;
use App\Models\AtharContribution;

class CancelAtharPrivateDataDeletion
{
    public function handle(AtharContribution $contribution): AtharContribution
    {
        abort_unless($contribution->status === AtharContributionStatus::DeletionRequested, 422);

        $status = $contribution->publicationVersions()->latest('version')->first()?->status?->value;
        $contribution->forceFill([
            'status' => $status === 'withdrawn' ? AtharContributionStatus::Withdrawn : AtharContributionStatus::Published,
            'deletion_requested_at' => null,
        ])->save();

        return $contribution->fresh();
    }
}
