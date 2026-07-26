<?php

namespace App\Enums;

enum ProjectEvidenceState: string
{
    case Draft = 'draft';
    case Verified = 'verified';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Revoked = 'revoked';

    public function label(): string
    {
        return __("admin.project_evidence_states.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Verified => 'info',
            self::Approved => 'success',
            self::Rejected, self::Revoked => 'danger',
        };
    }
}
