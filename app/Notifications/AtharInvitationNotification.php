<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AtharInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $url, private readonly string $language = 'ar')
    {
        $this->locale($this->language);
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject(__('athar.mail.invitation_subject', locale: $this->language))->greeting(__('athar.access.eyebrow', locale: $this->language))->line(__('athar.access.body', locale: $this->language))->action(__('athar.access.open_invitation', locale: $this->language), $this->url);
    }
}
