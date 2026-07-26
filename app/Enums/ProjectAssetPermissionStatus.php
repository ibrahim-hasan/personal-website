<?php

namespace App\Enums;

enum ProjectAssetPermissionStatus: string
{
    case Unreviewed = 'unreviewed';
    case InternalOnly = 'internal_only';
    case Approved = 'approved';
    case Revoked = 'revoked';

    public function label(): string
    {
        return __("admin.project_asset_permission_statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Unreviewed => 'gray',
            self::InternalOnly => 'warning',
            self::Approved => 'success',
            self::Revoked => 'danger',
        };
    }
}
