<?php

namespace App\Actions\Athar;

use App\Enums\AtharContributionStatus;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharContribution;
use Illuminate\Support\Facades\DB;

class DeleteAtharPrivateMessage
{
    public function handle(AtharContribution $contribution): AtharContribution
    {
        return DB::transaction(function () use ($contribution): AtharContribution {
            $record = AtharContribution::query()
                ->whereKey($contribution->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $hasPublishedVersion = $record->publicationVersions()
                ->where('status', AtharPublicationStatus::Published)
                ->exists();

            $record->forceFill([
                'status' => AtharContributionStatus::DeletionRequested,
                'deletion_requested_at' => now(),
                'sealed_payload' => null,
                'draft_payload' => null,
                'source_hash' => null,
                'deleted_at' => $hasPublishedVersion ? $record->deleted_at : now(),
            ])->save();

            return $record->fresh();
        });
    }
}
