<?php

namespace App\Actions\Consultation;

use App\Models\ContactInquiry;

class ConsultationSubmissionResult
{
    public function __construct(
        public readonly ContactInquiry $inquiry,
        public readonly bool $wasCreated,
    ) {}
}
