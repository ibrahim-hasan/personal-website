<?php

namespace App\Mail;

use App\Models\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    protected ?string $customUnsubscribeUrl;

    public function __construct(public Newsletter $newsletter, ?string $unsubscribeUrl = null)
    {
        $this->customUnsubscribeUrl = $unsubscribeUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.newsletter_welcome_subject'),
        );
    }

    public function content(): Content
    {
        $unsubscribeToken = $this->newsletter->ensureUnsubscribeToken();
        $unsubscribeUrl = filled($this->customUnsubscribeUrl)
            ? $this->customUnsubscribeUrl
            : route('newsletter.unsubscribe', [
                'email' => $this->newsletter->email,
                'token' => $unsubscribeToken,
            ]);

        return new Content(
            view: 'emails.newsletter-welcome',
            with: [
                'unsubscribeUrl' => $unsubscribeUrl,
            ],
        );
    }
}
