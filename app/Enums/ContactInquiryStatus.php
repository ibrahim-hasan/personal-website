<?php

namespace App\Enums;

enum ContactInquiryStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Replied = 'replied';
    case Archived = 'archived';

    public function label(): string
    {
        return __("admin.contact_status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'primary',
            self::Reviewing => 'warning',
            self::Replied => 'success',
            self::Archived => 'gray',
        };
    }
}
