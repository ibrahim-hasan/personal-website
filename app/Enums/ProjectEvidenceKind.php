<?php

namespace App\Enums;

enum ProjectEvidenceKind: string
{
    case Qualitative = 'qualitative';
    case Exact = 'exact';
    case Range = 'range';
    case Threshold = 'threshold';

    public function label(): string
    {
        return __("admin.project_evidence_kinds.{$this->value}");
    }
}
