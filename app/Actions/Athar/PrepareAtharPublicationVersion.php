<?php

namespace App\Actions\Athar;

use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPlacement;
use App\Enums\AtharPublicationOrigin;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharContribution;
use App\Models\AtharPublicationVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PrepareAtharPublicationVersion
{
    /** @param array<string, array<string, string>> $payload */
    public function handle(AtharContribution $contribution, array $payload, AtharPlacement $placement, ?string $placementKey, AtharIdentityDisplay $identityDisplay, User $reviewer, AtharPublicationOrigin $origin = AtharPublicationOrigin::ContributorEdited): AtharPublicationVersion
    {
        return DB::transaction(function () use ($contribution, $payload, $placement, $placementKey, $identityDisplay, $reviewer, $origin): AtharPublicationVersion {
            $record = AtharContribution::query()
                ->whereKey($contribution->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless($record->sealed(), 404);
            $latest = $record->publicationVersions()->latest('version')->first();
            abort_unless($latest === null || in_array($latest->status, [AtharPublicationStatus::Withdrawn, AtharPublicationStatus::Hidden], true), 422);
            $version = (int) $record->publicationVersions()->max('version') + 1;
            $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

            return $record->publicationVersions()->create(['version' => $version, 'status' => AtharPublicationStatus::Draft, 'origin' => $origin, 'public_payload' => $payload, 'snapshot_hash' => $hash, 'placement' => $placement, 'placement_key' => $placementKey, 'identity_display' => $identityDisplay, 'approved_locales' => array_keys($payload), 'reviewed_by' => $reviewer->getKey()]);
        });
    }
}
