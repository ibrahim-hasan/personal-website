<?php

namespace App\Enums;

enum AtharConsentEventType: string
{
    case Approved = 'approved';
    case Withdrawn = 'withdrawn';
    case Restored = 'restored';
}
