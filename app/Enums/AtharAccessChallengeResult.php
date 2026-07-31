<?php

namespace App\Enums;

enum AtharAccessChallengeResult: string
{
    case Verified = 'verified';
    case Invalid = 'invalid';
    case Expired = 'expired';
    case Locked = 'locked';
    case Unavailable = 'unavailable';
}
