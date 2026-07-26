<?php

namespace Tests\Feature\Consultation;

use App\Jobs\SendConsultationNotification;
use App\Mail\ConsultationRequestMail;
use App\Models\ContactInquiry;
use App\Services\Consultation\ConsultationNotificationDispatcher;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ConsultationNotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_notification_job_carries_only_an_inquiry_identifier_and_marks_delivery_as_sent(): void
    {
        Mail::fake();
        $inquiry = ContactInquiry::factory()->create([
            'notification_status' => 'queued',
        ]);
        $job = new SendConsultationNotification((int) $inquiry->getKey());

        $job->handle(app(ConsultationNotificationDispatcher::class));

        Mail::assertSent(ConsultationRequestMail::class, function (ConsultationRequestMail $mail) use ($inquiry): bool {
            return $mail->hasTo('hello@ibrahimhasan.net')
                && $mail->consultation['email'] === $inquiry->email
                && $mail->consultation['challenge'] === $inquiry->challenge;
        });

        $inquiry->refresh();

        $this->assertSame('sent', $inquiry->notification_status);
        $this->assertSame(1, $inquiry->notification_attempts);
        $this->assertNotNull($inquiry->notification_last_attempted_at);
        $this->assertNotNull($inquiry->notification_sent_at);
        $this->assertStringNotContainsString($inquiry->email, serialize($job));
        $this->assertStringNotContainsString($inquiry->name, serialize($job));
        $this->assertStringNotContainsString($inquiry->challenge, serialize($job));
    }

    public function test_a_mail_transport_failure_is_recorded_for_retry_without_logging_inquiry_content(): void
    {
        config()->set('operations.consultation_notifications.max_attempts', 3);
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new RuntimeException('Could not send persistent@example.com.'));
        Log::shouldReceive('critical')
            ->once()
            ->with('Consultation notification delivery requires attention.', [
                'channel' => 'consultation',
                'event' => 'notification_delivery_failed',
                'retry_scheduled' => true,
            ]);
        $inquiry = ContactInquiry::factory()->create([
            'email' => 'persistent@example.com',
            'challenge' => 'A private consultation challenge that must not enter an operational log.',
            'notification_status' => 'queued',
        ]);

        (new SendConsultationNotification((int) $inquiry->getKey()))->handle(app(ConsultationNotificationDispatcher::class));

        $inquiry->refresh();

        $this->assertSame('failed', $inquiry->notification_status);
        $this->assertSame(1, $inquiry->notification_attempts);
        $this->assertNotNull($inquiry->notification_failed_at);
        $this->assertNotNull($inquiry->notification_next_retry_at);
    }

    public function test_due_failures_are_requeued_by_the_operational_command_without_replaying_historical_inquiries(): void
    {
        Queue::fake();
        $due = ContactInquiry::factory()->create([
            'notification_status' => 'failed',
            'notification_attempts' => 1,
            'notification_next_retry_at' => now()->subSecond(),
        ]);
        ContactInquiry::factory()->create([
            'notification_status' => 'not_requested',
            'notification_attempts' => 0,
        ]);

        $this->artisan('consultation:retry-notifications')
            ->expectsOutputToContain('Queued 1 consultation notification retries.')
            ->assertSuccessful();

        Queue::assertPushed(SendConsultationNotification::class, function (SendConsultationNotification $job) use ($due): bool {
            return $job->inquiryId === $due->getKey();
        });

        $due->refresh();

        $this->assertSame('queued', $due->notification_status);
        $this->assertNull($due->notification_next_retry_at);
    }

    public function test_the_operational_retry_command_is_scheduled_every_five_minutes(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('consultation:retry-notifications')
            ->assertSuccessful();
    }

    public function test_a_queue_dispatch_failure_leaves_a_redacted_retry_record_for_operations(): void
    {
        config()->set('operations.consultation_notifications.max_attempts', 1);
        $bus = Mockery::mock(Dispatcher::class);
        $bus->shouldReceive('dispatch')
            ->once()
            ->andThrow(new RuntimeException('Queue backend unavailable.'));
        Log::shouldReceive('critical')
            ->once()
            ->with('Consultation notification delivery requires attention.', [
                'channel' => 'consultation',
                'event' => 'notification_dispatch_failed',
                'retry_scheduled' => false,
            ]);
        $inquiry = ContactInquiry::factory()->create([
            'notification_status' => 'pending',
        ]);

        $dispatched = (new ConsultationNotificationDispatcher($bus))->dispatch($inquiry);

        $inquiry->refresh();

        $this->assertFalse($dispatched);
        $this->assertSame('failed', $inquiry->notification_status);
        $this->assertSame(1, $inquiry->notification_attempts);
        $this->assertNull($inquiry->notification_next_retry_at);
    }
}
