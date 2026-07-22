<?php

namespace App\Enums;

enum AtharInvitationDeliveryMode: string
{
    case Email = 'email';
    case Link = 'link';

    public function label(): string
    {
        return __("admin.athar.delivery_modes.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Email => 'info',
            self::Link => 'primary',
        };
    }
}
