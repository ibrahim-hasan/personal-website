<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use InvalidArgumentException;

class ReaderVerifyEmailNotification extends VerifyEmail
{
    public function toMail(mixed $notifiable): MailMessage
    {
        $reader = $this->reader($notifiable);
        $locale = $reader->preferredLocale();

        return (new MailMessage)
            ->subject(__('reader_auth.verification_email_subject', locale: $locale))
            ->greeting(__('reader_auth.verification_email_greeting', ['name' => $reader->name], $locale))
            ->line(__('reader_auth.verification_email_line', locale: $locale))
            ->action(__('reader_auth.verification_email_action', locale: $locale), $this->verificationUrl($reader))
            ->line(__('reader_auth.verification_email_ignore', locale: $locale));
    }

    private function reader(mixed $notifiable): User
    {
        if (! $notifiable instanceof User) {
            throw new InvalidArgumentException('Reader verification notifications require a user recipient.');
        }

        return $notifiable;
    }
}
