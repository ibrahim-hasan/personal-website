<?php

namespace App\Actions\Athar;

use App\Enums\AtharContributionStatus;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPublicationOrigin;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharContribution;
use App\Models\AtharPublicationVersion;
use App\Support\AtharPlacementDestination;
use App\Support\AtharPublicationSnapshot;
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
            $placementKey = AtharPlacementDestination::validatedKey(
                $record->invitation->placement,
                $record->invitation->placement_key,
            );
            $displayName = trim((string) $record->invitation->recipient_name);
            $displayPosition = trim((string) $record->invitation->recipient_position);
            $payload = AtharPublicationSnapshot::withDestination($record->invitation, $payload);
            $payload = collect($payload)
                ->map(function (array $entry) use ($displayName, $displayPosition): array {
                    $entry['identity_display'] = AtharIdentityDisplay::FullName->value;
                    $entry['display_name'] = $displayName;
                    $entry['display_position'] = $displayPosition;

                    return $entry;
                })
                ->all();
            $version = (int) $record->publicationVersions()->max('version') + 1;
            $hash = AtharPublicationSnapshot::hash($payload);
            $publication = $record->publicationVersions()->create([
                'version' => $version,
                'status' => AtharPublicationStatus::Draft,
                'origin' => $origin,
                'public_payload' => $payload,
                'snapshot_hash' => $hash,
                'placement' => $record->invitation->placement,
                'placement_key' => $placementKey,
                'approved_locales' => array_keys($payload),
                'identity_display' => AtharIdentityDisplay::FullName,
                'display_name' => $displayName !== '' ? $displayName : null,
            ]);
            $record->forceFill(['status' => AtharContributionStatus::AwaitingApproval])->save();

            return $publication;
        });
    }
}
