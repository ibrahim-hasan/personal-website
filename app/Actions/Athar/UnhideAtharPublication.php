<?php

namespace App\Actions\Athar;

use App\Enums\AtharPublicationStatus;
use App\Models\AtharPublicationVersion;
use Illuminate\Support\Facades\DB;

class UnhideAtharPublication
{
    public function handle(AtharPublicationVersion $version): AtharPublicationVersion
    {
        return DB::transaction(function () use ($version): AtharPublicationVersion {
            $record = AtharPublicationVersion::query()
                ->whereKey($version->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($record->status === AtharPublicationStatus::Hidden, 422);

            $record->forceFill([
                'status' => AtharPublicationStatus::Published,
                'hidden_at' => null,
            ])->save();

            return $record->fresh();
        });
    }
}
