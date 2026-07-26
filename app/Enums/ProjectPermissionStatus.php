<?php

namespace App\Enums;

enum ProjectPermissionStatus: string
{
    case Unreviewed = 'unreviewed';
    case InternalOnly = 'internal_only';
    case ApprovedAnonymized = 'approved_anonymized';
    case ApprovedNamed = 'approved_named';
    case Revoked = 'revoked';

    public function label(): string
    {
        return __("admin.project_permission_statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Unreviewed => 'gray',
            self::InternalOnly => 'warning',
            self::ApprovedAnonymized, self::ApprovedNamed => 'success',
            self::Revoked => 'danger',
        };
    }
}
