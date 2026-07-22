<?php

namespace App\Actions\Athar;

use App\Enums\AtharPublicationStatus;
use App\Models\AtharPublicationVersion;
use Illuminate\Support\Facades\DB;

class SaveAtharPublicationDraft
{
    public function handle(AtharPublicationVersion $version, string $text): AtharPublicationVersion
    {
        return DB::transaction(function () use ($version, $text): AtharPublicationVersion {
            $record = AtharPublicationVersion::query()
                ->whereKey($version->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            abort_unless(in_array($record->status, [AtharPublicationStatus::Draft, AtharPublicationStatus::AwaitingApproval], true), 422);

            $payload = $record->public_payload;
            $locale = array_key_first($payload);
            abort_unless(is_string($locale) && isset($payload[$locale]), 422);
            $payload[$locale]['text'] = $text;

            $record->forceFill([
                'public_payload' => $payload,
                'snapshot_hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
            ])->save();

            return $record->fresh();
        });
    }
}
