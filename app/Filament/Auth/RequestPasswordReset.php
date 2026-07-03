<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected string $view = 'filament.auth.request-password-reset';
}
