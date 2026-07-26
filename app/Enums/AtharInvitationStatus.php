<?php

namespace App\Enums;

enum AtharInvitationStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Sent = 'sent';
    case Verified = 'verified';
    case Completed = 'completed';
    case Revoked = 'revoked';
    case Expired = 'expired';

    public function label(): string
    {
        return __("admin.athar.invitation_statuses.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Ready => 'info',
            self::Sent => 'info',
            self::Verified => 'primary',
            self::Completed => 'success',
            self::Revoked => 'danger',
            self::Expired => 'warning',
        };
    }
}
