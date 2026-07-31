<?php

namespace Tests\Feature\Consultation;

use App\Enums\ContactInquiryStatus;
use App\Jobs\SendConsultationNotification;
use App\Livewire\Website\ConsultationRequest;
use App\Models\ContactInquiry;
use App\Services\Consultation\ConsultationNotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ContactInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_consultation_is_saved_before_the_notification_is_sent(): void
    {
        Queue::fake();

        Livewire::test(ConsultationRequest::class)
            ->set('form.name', 'Decision Maker')
            ->set('form.email', 'decision@example.com')
            ->set('form.company', 'Example Company')
            ->set('form.service', 'general')
            ->set('form.challenge', 'We need to turn a risky AI experiment into a dependable operating workflow.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $inquiry = ContactInquiry::query()->sole();

        $this->assertSame('Decision Maker', $inquiry->name);
        $this->assertSame('decision@example.com', $inquiry->email);
        $this->assertSame('general', $inquiry->service_key);
        $this->assertSame(ContactInquiryStatus::New, $inquiry->status);
        $this->assertSame('ar', $inquiry->locale);
        $this->assertSame('queued', $inquiry->notification_status);

        Queue::assertPushed(SendConsultationNotification::class, function (SendConsultationNotification $job) use ($inquiry): bool {
            return $job->inquiryId === $inquiry->getKey();
        });
    }

    public function test_the_honeypot_does_not_persist_or_send_an_inquiry(): void
    {
        Queue::fake();

        Livewire::test(ConsultationRequest::class)
            ->set('form.website', 'https://spam.example')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('contact_inquiries', 0);
        Queue::assertNothingPushed();
    }

    public function test_a_saved_inquiry_is_not_lost_when_notification_dispatch_fails(): void
    {
        Queue::fake();
        Log::shouldReceive('critical')
            ->once()
            ->with('Consultation notification delivery requires attention.', [
                'channel' => 'consultation',
                'event' => 'notification_delivery_failed',
                'retry_scheduled' => true,
            ]);
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('Mail transport unavailable.'));

        Livewire::test(ConsultationRequest::class)
            ->set('form.name', 'Persistent Lead')
            ->set('form.email', 'persistent@example.com')
            ->set('form.service', 'general')
            ->set('form.challenge', 'We need a reliable route from operational ambiguity to a measurable product decision.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        $inquiry = ContactInquiry::query()->sole();

        Queue::assertPushed(SendConsultationNotification::class, function (SendConsultationNotification $job) use ($inquiry): bool {
            return $job->inquiryId === $inquiry->getKey();
        });

        (new SendConsultationNotification((int) $inquiry->getKey()))
            ->handle(app(ConsultationNotificationDispatcher::class));

        $this->assertDatabaseHas('contact_inquiries', [
            'email' => 'persistent@example.com',
            'service_key' => 'general',
            'notification_status' => 'failed',
            'notification_attempts' => 1,
        ]);
    }
}
