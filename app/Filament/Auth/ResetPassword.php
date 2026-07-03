<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;

class ResetPassword extends BaseResetPassword
{
    protected string $view = 'filament.auth.reset-password';
}
