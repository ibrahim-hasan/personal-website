<?php

namespace App\Enums;

enum AtharIdentityDisplay: string
{
    case FullName = 'full_name';
    case FirstName = 'first_name';
    case Anonymous = 'anonymous';

    public function label(): string
    {
        return __("admin.athar.identity_display.{$this->value}");
    }
}
