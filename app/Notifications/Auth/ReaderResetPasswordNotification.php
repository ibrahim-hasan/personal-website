<?php

namespace App\Notifications\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use InvalidArgumentException;

class ReaderResetPasswordNotification extends ResetPassword
{
    use Queueable;

    public function toMail(mixed $notifiable): MailMessage
    {
        $reader = $this->reader($notifiable);
        $locale = $this->localeFor($reader);

        return (new MailMessage)
            ->subject(__('reader_auth.reset_email_subject', locale: $locale))
            ->greeting(__('reader_auth.reset_email_greeting', ['name' => $reader->name], $locale))
            ->line(__('reader_auth.reset_email_line', locale: $locale))
            ->action(__('reader_auth.reset_action', locale: $locale), $this->resetUrl($reader))
            ->line(__('reader_auth.reset_expires', [
                'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ], $locale))
            ->line(__('reader_auth.reset_ignore', locale: $locale));
    }

    protected function resetUrl(mixed $notifiable): string
    {
        $reader = $this->reader($notifiable);

        return localized_route('reader.password.reset', [
            'token' => $this->token,
            'email' => $reader->getEmailForPasswordReset(),
        ], true, $this->localeFor($reader));
    }

    private function localeFor(User $reader): string
    {
        $locale = $reader->locale_preference;

        return is_string($locale) && array_key_exists($locale, supported_locales())
            ? $locale
            : default_locale();
    }

    private function reader(mixed $notifiable): User
    {
        if (! $notifiable instanceof User) {
            throw new InvalidArgumentException('Reader password reset notifications require a user recipient.');
        }

        return $notifiable;
    }
}
