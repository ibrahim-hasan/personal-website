<?php

namespace App\Actions\Athar;

use App\Enums\AtharContributionStatus;
use App\Models\AtharContribution;
use App\Support\AtharAccess;
use Illuminate\Http\Request;

class RequestAtharPrivateDataDeletion
{
    public function handle(AtharContribution $contribution, Request $request): AtharContribution
    {
        abort_unless(AtharAccess::verified($request, $contribution->invitation), 403);
        $contribution->forceFill(['status' => AtharContributionStatus::DeletionRequested, 'deletion_requested_at' => now()])->save();

        return $contribution->fresh();
    }
}
