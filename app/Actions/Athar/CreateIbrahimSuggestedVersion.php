<?php

namespace App\Actions\Athar;

use App\Enums\AtharPublicationOrigin;
use App\Models\AtharContribution;
use App\Models\AtharPublicationVersion;

class CreateIbrahimSuggestedVersion
{
    public function __construct(private readonly CreateContributorPublicNote $createContributorPublicNote) {}

    /** @param array<string, array<string, string>> $payload */
    public function handle(AtharContribution $contribution, array $payload): AtharPublicationVersion
    {
        return $this->createContributorPublicNote->handle($contribution, $payload, AtharPublicationOrigin::IbrahimSuggested);
    }
}
