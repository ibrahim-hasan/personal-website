<?php

namespace App\Actions\Athar;

use App\Models\AtharAccessChallenge;
use App\Models\AtharContribution;
use Illuminate\Support\Facades\DB;

class PurgeExpiredAtharData
{
    /** @return array{challenges: int, contributions: int} */
    public function preview(): array
    {
        return ['challenges' => AtharAccessChallenge::query()->where('expires_at', '<', now()->subDays((int) config('legal.retention.athar_challenges_days', 1)))->count(), 'contributions' => AtharContribution::query()->whereNotNull('deleted_at')->where('deleted_at', '<', now()->subDays((int) config('legal.retention.athar_deleted_notes_days', 365)))->count()];
    }

    /** @return array{challenges: int, contributions: int} */
    public function purge(): array
    {
        return DB::transaction(function (): array {
            $challenges = AtharAccessChallenge::query()->where('expires_at', '<', now()->subDays((int) config('legal.retention.athar_challenges_days', 1)))->delete();
            $contributions = AtharContribution::query()->whereNotNull('deleted_at')->where('deleted_at', '<', now()->subDays((int) config('legal.retention.athar_deleted_notes_days', 365)))->delete();

            return ['challenges' => $challenges, 'contributions' => $contributions];
        });
    }
}
