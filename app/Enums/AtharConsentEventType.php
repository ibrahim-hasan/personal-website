<?php

namespace App\Enums;

enum AtharConsentEventType: string
{
    case Approved = 'approved';
    case Withdrawn = 'withdrawn';
    case Restored = 'restored';

    public function label(): string
    {
        return __("admin.athar.consent_events.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Approved => 'success',
            self::Withdrawn => 'danger',
            self::Restored => 'info',
        };
    }
}
