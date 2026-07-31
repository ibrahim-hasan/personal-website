<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AtharApprovalNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)->subject(__('athar.mail.approval_subject', locale: $this->language))->greeting(__('athar.approval.title', locale: $this->language))->action(__('athar.mail.approval_action', locale: $this->language), $this->url);
    }
}
