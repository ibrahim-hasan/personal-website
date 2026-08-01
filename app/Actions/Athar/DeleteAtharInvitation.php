<?php

namespace App\Actions\Athar;

use App\Models\AtharInvitation;
use Illuminate\Support\Facades\DB;

class DeleteAtharInvitation
{
    public function handle(AtharInvitation $invitation): void
    {
        DB::transaction(function () use ($invitation): void {
            $record = AtharInvitation::query()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $record->delete();
        });
    }
}
