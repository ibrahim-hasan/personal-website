<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConsultationRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array{name: string, email: string, company: string|null, service: string, service_label: string, challenge: string, locale: string} $consultation */
    public function __construct(public array $consultation) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address($this->consultation['email'], $this->consultation['name']),
            ],
            subject: __('site.consultation.mail_subject'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.consultation-request',
            with: [
                'consultation' => $this->consultation,
            ],
        );
    }
}
