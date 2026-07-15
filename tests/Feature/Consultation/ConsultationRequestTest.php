<?php

namespace Tests\Feature\Consultation;

use App\Livewire\Website\ConsultationRequest;
use App\Mail\ConsultationRequestMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ConsultationRequestTest extends TestCase
{
    public function test_contact_page_contains_the_livewire_consultation_form(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSeeLivewire(ConsultationRequest::class);
    }

    public function test_a_consultation_request_is_validated_and_sent(): void
    {
        Mail::fake();

        Livewire::test(ConsultationRequest::class)
            ->set('form.name', 'Ibrahim Test')
            ->set('form.email', 'project@example.com')
            ->set('form.company', 'Example Company')
            ->set('form.service', 'ai-adoption')
            ->set('form.challenge', 'We need a dependable internal AI assistant grounded in our operating knowledge.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true);

        Mail::assertSent(ConsultationRequestMail::class, function (ConsultationRequestMail $mail): bool {
            return $mail->hasTo('hello@ibrahimhasan.net')
                && $mail->consultation['email'] === 'project@example.com'
                && $mail->consultation['service'] === 'ai-adoption';
        });
    }

    public function test_required_consultation_fields_are_enforced(): void
    {
        Mail::fake();

        Livewire::test(ConsultationRequest::class)
            ->call('submit')
            ->assertHasErrors([
                'form.name' => 'required',
                'form.email' => 'required',
                'form.service' => 'required',
                'form.challenge' => 'required',
            ]);

        Mail::assertNothingSent();
    }

    public function test_arabic_validation_uses_human_field_names(): void
    {
        app()->setLocale('ar');

        Livewire::test(ConsultationRequest::class)
            ->call('submit')
            ->tap(function ($component): void {
                $this->assertSame('حقل المجال الأقرب مطلوب.', $component->errors()->first('form.service'));
                $this->assertSame('حقل وصف التحدّي مطلوب.', $component->errors()->first('form.challenge'));
            });
    }
}
