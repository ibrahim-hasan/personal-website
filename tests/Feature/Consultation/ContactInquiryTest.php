<?php

namespace Tests\Feature\Consultation;

use App\Enums\ContactInquiryStatus;
use App\Livewire\Website\ConsultationRequest;
use App\Mail\ConsultationRequestMail;
use App\Models\ContactInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ContactInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_consultation_is_saved_before_the_notification_is_sent(): void
    {
        Mail::fake();

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

        Mail::assertQueued(ConsultationRequestMail::class);
    }

    public function test_the_honeypot_does_not_persist_or_send_an_inquiry(): void
    {
        Mail::fake();

        Livewire::test(ConsultationRequest::class)
            ->set('form.website', 'https://spam.example')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseCount('contact_inquiries', 0);
        Mail::assertNothingOutgoing();
    }

    public function test_a_saved_inquiry_is_not_lost_when_notification_dispatch_fails(): void
    {
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

        $this->assertDatabaseHas('contact_inquiries', [
            'email' => 'persistent@example.com',
            'service_key' => 'general',
        ]);
    }
}
