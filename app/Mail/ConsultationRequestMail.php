<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ConsultationRequestMail extends Mailable
{
    /** @param array{name: string, email: string, company: string|null, role: string|null, service: string, service_label: string, challenge: string, timing: string|null, locale: string} $consultation */
    public function __construct(public array $consultation)
    {
        $this->locale($this->consultation['locale']);
    }

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
            markdown: 'emails.consultation-request',
            with: [
                'consultation' => $this->consultation,
            ],
        );
    }
}
