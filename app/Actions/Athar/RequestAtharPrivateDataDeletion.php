<?php

namespace App\Actions\Athar;

use App\Enums\AtharContributionStatus;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharContribution;
use App\Support\AtharAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestAtharPrivateDataDeletion
{
    public function handle(AtharContribution $contribution, Request $request): AtharContribution
    {
        abort_unless(AtharAccess::verified($request, $contribution->invitation), 403);

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
                // The contributor's private reflection data is deleted at request
                // time rather than deferred to the retention sweep. The public
                // endorsement is unaffected until the contributor separately
                // withdraws it.
                'sealed_payload' => null,
                'draft_payload' => null,
                'source_hash' => null,
                // When nothing remains published, the contribution row itself is
                // scheduled for hard deletion by the retention purge.
                'deleted_at' => $hasPublishedVersion ? $record->deleted_at : now(),
            ])->save();

            return $record->fresh();
        });
    }
}
