<?php

namespace App\Jobs;

use App\Mail\ConsultationRequestMail;
use App\Models\ContactInquiry;
use App\Services\Consultation\ConsultationNotificationDispatcher;
use App\Support\SiteContent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendConsultationNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public readonly int $inquiryId) {}

    public function handle(ConsultationNotificationDispatcher $notifications): void
    {
        $inquiry = ContactInquiry::query()->find($this->inquiryId);

        if ($inquiry === null || $inquiry->notification_sent_at !== null) {
            return;
        }

        $notifications->markAttempted($inquiry);

        try {
            Mail::to((string) SiteContent::contact()['email'])
                ->send(new ConsultationRequestMail($this->payloadFor($inquiry)));
        } catch (Throwable) {
            $notifications->markDeliveryFailure($this->inquiryId);

            return;
        }

        $freshInquiry = $inquiry->fresh();

        if ($freshInquiry !== null) {
            $notifications->markDelivered($freshInquiry);
        }
    }

    /**
     * @return array{name: string, email: string, company: string|null, role: string|null, service: string, service_label: string, challenge: string, timing: string|null, locale: string}
     */
    private function payloadFor(ContactInquiry $inquiry): array
    {
        return [
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'company' => $inquiry->company,
            'role' => $inquiry->role,
            'service' => $inquiry->service_key,
            'service_label' => $inquiry->service_label,
            'challenge' => $inquiry->challenge,
            'timing' => $inquiry->timing,
            'locale' => $inquiry->locale,
        ];
    }
}
