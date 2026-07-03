<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class AdminResetPasswordNotification extends ResetPassword
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject(__('Reset your admin password'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('Admin')]))
            ->line(__('A password reset request was received for your Ibrahim Hasan website admin account.'))
            ->action(__('Reset password'), $url)
            ->line(__('This reset link expires in :count minutes.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(__('If you did not request a password reset, you can safely ignore this email.'));
    }
}
