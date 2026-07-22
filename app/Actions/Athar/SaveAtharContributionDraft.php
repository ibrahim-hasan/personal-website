<?php

namespace App\Actions\Athar;

use App\Models\AtharContribution;
use Illuminate\Support\Facades\DB;

class SaveAtharContributionDraft
{
    /** @param array<string, mixed> $payload */
    public function handle(AtharContribution $contribution, array $payload): AtharContribution
    {
        if ($contribution->sealed()) {
            return $contribution;
        }

        return DB::transaction(fn (): AtharContribution => tap($contribution->lockForUpdate()->firstOrFail(), function (AtharContribution $record) use ($payload): void {
            $record->forceFill(['draft_payload' => $payload, 'draft_updated_at' => now()])->save();
        }));
    }
}
