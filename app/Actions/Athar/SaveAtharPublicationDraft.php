<?php

namespace App\Actions\Athar;

use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharPublicationVersion;
use App\Support\AtharPublicationSnapshot;
use Illuminate\Support\Facades\DB;

class SaveAtharPublicationDraft
{
    public function handle(AtharPublicationVersion $version, string $text, AtharIdentityDisplay $identityDisplay, string $displayName): AtharPublicationVersion
    {
        return DB::transaction(function () use ($version, $text, $identityDisplay, $displayName): AtharPublicationVersion {
            $record = AtharPublicationVersion::query()
                ->whereKey($version->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless(in_array($record->status, [AtharPublicationStatus::Draft, AtharPublicationStatus::AwaitingApproval], true), 422);

            $payload = $record->public_payload;
            $locale = array_key_first($payload);
            abort_unless(is_string($locale) && isset($payload[$locale]), 422);
            abort_unless(AtharPublicationSnapshot::matches($record), 409);
            abort_unless(AtharPublicationSnapshot::matchesDestination($record), 409);
            $payload[$locale]['text'] = $text;
            $payload[$locale]['identity_display'] = $identityDisplay->value;
            $payload[$locale]['display_name'] = $displayName;

            $record->forceFill([
                'public_payload' => $payload,
                'identity_display' => $identityDisplay,
                'display_name' => $displayName !== '' ? $displayName : null,
                'snapshot_hash' => AtharPublicationSnapshot::hash($payload),
            ])->save();

            return $record->fresh();
        });
    }
}
