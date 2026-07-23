<?php

namespace App\Actions\Athar;

use App\Enums\AtharPublicationStatus;
use App\Models\AtharPublicationVersion;
use Illuminate\Support\Facades\DB;

class HideAtharPublication
{
    public function handle(AtharPublicationVersion $version): AtharPublicationVersion
    {
        return DB::transaction(function () use ($version): AtharPublicationVersion {
            $record = AtharPublicationVersion::query()
                ->whereKey($version->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($record->status === AtharPublicationStatus::Published, 422);

            $record->forceFill([
                'status' => AtharPublicationStatus::Hidden,
                'hidden_at' => now(),
            ])->save();

            return $record->fresh();
        });
    }
}
