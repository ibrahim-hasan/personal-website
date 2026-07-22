<?php

namespace App\Enums;

enum AtharResponseMode: string
{
    case Freeform = 'freeform';
    case Prompted = 'prompted';

    public function label(): string
    {
        return __("admin.athar.response_modes.{$this->value}");
    }
}
