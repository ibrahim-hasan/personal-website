<?php

namespace App\Enums;

enum ProjectEvidenceLevel: string
{
    case Qualitative = 'qualitative';
    case Documented = 'documented';
    case VerifiedQuantitative = 'verified_quantitative';

    public function label(): string
    {
        return __("admin.project_evidence_levels.{$this->value}");
    }
}
