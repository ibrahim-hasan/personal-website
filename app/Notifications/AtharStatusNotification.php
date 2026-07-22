<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AtharStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $message, private readonly string $language = 'ar')
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
        return (new MailMessage)->subject(__('athar.mail.status_subject', locale: $this->language))->line($this->message);
    }
}
