<?php

namespace App\Actions\Athar;

use App\Models\AtharContribution;
use App\Support\AtharAccess;
use Illuminate\Http\Request;

class RequestAtharPrivateDataDeletion
{
    public function __construct(private readonly DeleteAtharPrivateMessage $delete) {}

    public function handle(AtharContribution $contribution, Request $request): AtharContribution
    {
        abort_unless(AtharAccess::verified($request, $contribution->invitation), 403);

        return $this->delete->handle($contribution);
    }
}
