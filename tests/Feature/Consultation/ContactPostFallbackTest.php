<?php

namespace Tests\Feature\Consultation;

use App\Actions\Consultation\ConsultationSubmissionToken;
use App\Jobs\SendConsultationNotification;
use App\Models\ContactInquiry;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContactPostFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ServiceSeeder::class);
    }

    public function test_standard_post_persists_the_same_qualified_consultation_payload_and_redirects_without_query_data(): void
    {
        Queue::fake();
        $submissionToken = app(ConsultationSubmissionToken::class)->current();

        $response = $this->post('/contact', $this->validPayload([
            'submission_token' => $submissionToken,
            'role' => 'Operations lead',
            'timing' => 'Within six weeks',
        ]));

        $response
            ->assertRedirect('/contact#consultation')
            ->assertSessionHas('consultation.submitted.public_reference')
            ->assertSessionHas('consultation.submitted.analytics_success', true);

        $inquiry = ContactInquiry::query()->sole();

        $this->assertSame('Operations lead', $inquiry->role);
        $this->assertSame('Within six weeks', $inquiry->timing);
        $this->assertMatchesRegularExpression('/^IH-[0-9A-HJKMNP-TV-Z]{12}$/', (string) $inquiry->public_reference);
        $this->assertSame(hash_hmac('sha256', $submissionToken, (string) config('app.key')), $inquiry->submission_hash);
        Queue::assertPushed(SendConsultationNotification::class, function (SendConsultationNotification $job) use ($inquiry): bool {
            return $job->inquiryId === $inquiry->getKey();
        });
    }

    public function test_reusing_a_submission_token_returns_the_existing_inquiry_without_another_notification(): void
    {
        Queue::fake();
        $submissionToken = app(ConsultationSubmissionToken::class)->current();
        $payload = $this->validPayload(['submission_token' => $submissionToken]);

        $this->post('/contact', $payload)->assertRedirect('/contact#consultation');
        $this->post('/contact', $payload)
            ->assertRedirect('/contact#consultation')
            ->assertSessionHas('consultation.submitted.public_reference');

        $this->assertDatabaseCount('contact_inquiries', 1);
        Queue::assertPushed(SendConsultationNotification::class, 1);
    }

    public function test_standard_post_validation_preserves_only_safe_fields_and_exposes_no_token_in_the_redirect(): void
    {
        $submissionToken = app(ConsultationSubmissionToken::class)->current();

        $response = $this->from('/contact#consultation')->post('/contact', $this->validPayload([
            'submission_token' => $submissionToken,
            'challenge' => 'Too short.',
        ]));

        $response
            ->assertRedirect('/contact#consultation')
            ->assertSessionHasErrors('challenge')
            ->assertSessionHasInput('name', 'Decision Maker')
            ->assertSessionMissing('old_input.submission_token')
            ->assertSessionMissing('old_input.cf-turnstile-response');

        $this->assertDatabaseCount('contact_inquiries', 0);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('data-analytics-consultation-error="validation"', false);
    }

    public function test_standard_post_success_marks_only_human_submissions_for_analytics(): void
    {
        $this->withSession(['consultation.submitted' => [
            'analytics_success' => true,
            'public_reference' => 'IH-0123456789AB',
        ]])->get('/contact')
            ->assertOk()
            ->assertSee('data-analytics-consultation-success', false)
            ->assertDontSee('data-analytics-public-reference', false);

        $this->withSession(['consultation.submitted' => [
            'analytics_success' => false,
            'public_reference' => null,
        ]])->get('/contact')
            ->assertOk()
            ->assertDontSee('data-analytics-consultation-success', false);
    }

    public function test_contact_markup_contains_a_real_post_form_and_no_javascript_alternatives(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('name="submission_token"', false)
            ->assertSee('<noscript>', false)
            ->assertSee('name="role"', false)
            ->assertSee('name="timing"', false)
            ->assertDontSee('site-footer__cta', false);
    }

    /** @return array<string, string> */
    private function validPayload(array $overrides = []): array
    {
        return [
            'name' => 'Decision Maker',
            'email' => 'decision@example.com',
            'company' => 'Example Company',
            'role' => '',
            'service' => 'ai-adoption',
            'challenge' => 'We need a dependable internal AI assistant grounded in the way our operating team actually works.',
            'timing' => '',
            'website' => '',
            'cf-turnstile-response' => '',
            ...$overrides,
        ];
    }
}
