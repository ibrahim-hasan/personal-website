<?php

namespace App\Actions\Athar;

use App\Enums\AtharContributionStatus;
use App\Enums\AtharPublicationOrigin;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharContribution;
use App\Models\AtharPublicationVersion;
use Illuminate\Support\Facades\DB;

class CreateContributorPublicNote
{
    /** @param array<string, array<string, string>> $payload */
    public function handle(AtharContribution $contribution, array $payload, AtharPublicationOrigin $origin = AtharPublicationOrigin::ContributorSelected): AtharPublicationVersion
    {
        return DB::transaction(function () use ($contribution, $payload, $origin): AtharPublicationVersion {
            $record = AtharContribution::query()
                ->whereKey($contribution->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($record->sealed(), 404);
            $version = (int) $record->publicationVersions()->max('version') + 1;
            $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $publication = $record->publicationVersions()->create(['version' => $version, 'status' => AtharPublicationStatus::Draft, 'origin' => $origin, 'public_payload' => $payload, 'snapshot_hash' => $hash, 'placement' => $record->invitation->placement, 'placement_key' => $record->invitation->placement_key, 'approved_locales' => array_keys($payload), 'identity_display' => $record->invitation->identity_display]);
            $record->forceFill(['status' => AtharContributionStatus::AwaitingApproval])->save();

            return $publication;
        });
    }
}
