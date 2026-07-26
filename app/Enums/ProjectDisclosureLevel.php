<?php

namespace App\Enums;

enum ProjectDisclosureLevel: string
{
    case Named = 'named';
    case PartiallyAnonymized = 'partially_anonymized';
    case Anonymized = 'anonymized';

    public function label(): string
    {
        return __("admin.project_disclosure_levels.{$this->value}");
    }
}
