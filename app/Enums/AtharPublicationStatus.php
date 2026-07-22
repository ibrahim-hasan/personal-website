<?php

namespace App\Enums;

enum AtharPublicationStatus: string
{
    case Draft = 'draft';
    case AwaitingApproval = 'awaiting_approval';
    case Published = 'published';
    case Hidden = 'hidden';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return __("admin.athar.publication_statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::AwaitingApproval => 'warning',
            self::Published => 'success',
            self::Hidden => 'info',
            self::Withdrawn => 'danger',
        };
    }
}
