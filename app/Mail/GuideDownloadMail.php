<?php

namespace App\Mail;

use App\Models\GuideDownloader;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuideDownloadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GuideDownloader $downloader,
        public ?string $token = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.guide_download_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.guide-download',
            with: [
                'downloadUrl' => $this->token
                    ? route('guide.download', ['token' => $this->token])
                    : null,
            ],
        );
    }
}
