<?php

namespace Tests\Feature;

use App\Actions\Athar\CreateAtharInvitation;
use App\Actions\Athar\CreateContributorPublicNote;
use App\Actions\Athar\HideAtharPublication;
use App\Actions\Athar\SendAtharApproval;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharPlacement;
use App\Enums\AtharRelationship;
use App\Models\AtharAccessChallenge;
use App\Models\AtharContribution;
use App\Models\AtharInvitation;
use App\Models\User;
use App\Notifications\AtharInvitationNotification;
use App\Support\AtharAccess;
use App\Support\AtharPublicProof;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AtharFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Drive the real six-digit-code verification HTTP path for an email-mode
     * invitation so the session grant is established for subsequent writes.
     */
    private function grantAtharSession(string $token, AtharInvitation $invitation, string $code = '123456', string $locale = 'ar'): TestResponse
    {
        AtharAccessChallenge::query()->create([
            'invitation_id' => $invitation->getKey(),
            'code_hash' => AtharAccess::codeHash($code),
            'expires_at' => now()->addMinutes(10),
            'requested_at' => now(),
            'ip_hash' => hash_hmac('sha256', '127.0.0.1', (string) config('app.key')),
        ]);

        $route = $locale === 'ar' ? 'athar.verify' : $locale.'.athar.verify';

        return $this->post(route($route, ['token' => $token]), ['code' => $code])->assertRedirect();
    }

    public function test_private_flow_opens_from_the_invitation_link_and_publishes_only_the_approved_snapshot(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => 'friend@example.com',
            'recipient_name' => 'Amina Noor',
            'recipient_position' => 'Digital product builder',
            'relationship' => AtharRelationship::FormerClient,
            'personal_reason' => 'Legacy admin-only context.',
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
            'identity_display' => 'full_name',
        ]);
        $token = $created['token'];
        $invitation = $created['invitation'];
        $this->assertSame(AtharInvitationDeliveryMode::Email, $invitation->delivery_mode);
        $this->assertSame('sent', $invitation->status->value);
        $this->assertNull($invitation->relationship);
        $this->assertNull($invitation->personal_reason);
        $this->assertNull($invitation->placement_key);
        $this->assertSame('anonymous', $invitation->identity_display->value);
        Notification::assertSentOnDemand(AtharInvitationNotification::class);

        $response = $this->get(route('en.athar.show', ['token' => $token]));
        $response
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('href="'.asset('favicon.svg').'" type="image/svg+xml"', false)
            ->assertSee('href="'.asset('apple-touch-icon.png').'"', false)
            ->assertSee(__('athar.access.title'))
            ->assertSee('name="email"', false)
            ->assertSee('lang="en"', false)
            ->assertDontSee('lang="ar" hreflang="ar"', false)
            ->assertDontSee('athar-mark__feature', false)
            ->assertDontSee('هذا رابط خاص للوصول إلى الرسالة')
            ->assertDontSee('سؤال مساعد')
            ->assertDontSee('friend@example.com');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->grantAtharSession($token, $invitation, locale: 'en');

        $response = $this->get(route('en.athar.show', ['token' => $token]));
        $response
            ->assertOk()
            ->assertSee(__('athar.reflection.title'))
            ->assertSee(__('athar.reflection.body'))
            ->assertSee(__('athar.reflection.review'))
            ->assertDontSee('ما الذي بقي معك من تجربتنا؟')
            ->assertSee('lang="en"', false)
            ->assertSee('action="'.route('en.athar.submit', ['token' => $token]).'"', false)
            ->assertDontSee('name="email"', false);
        $this->assertStringContainsString('maxlength="350"', $response->getContent());
        $this->assertStringContainsString('id="freeform"', $response->getContent());
        $this->assertStringContainsString('dir="auto"', $response->getContent());
        $this->post(route('en.athar.submit', ['token' => $token]), ['freeform' => 'A thoughtful note about the work.'])->assertRedirect();
        $this->get(route('en.athar.show', ['token' => $token, 'choose' => '1']))
            ->assertOk()
            ->assertSee(__('athar.receipt.ready_body'))
            ->assertSee(__('athar.receipt.title'))
            ->assertSee('A thoughtful note about the work.', false)
            ->assertSee(__('athar.approval.edit'))
            ->assertDontSee(__('athar.public_choice.title'))
            ->assertDontSee('name="request_suggestion"', false)
            ->assertDontSee('athar.choose', false);

        $contribution = AtharContribution::query()->where('invitation_id', $invitation->getKey())->firstOrFail();
        $version = $contribution->publicationVersions()->latest('version')->firstOrFail();
        $this->assertSame('awaiting_approval', $version->status->value);
        $this->assertSame('full_name', $version->identity_display->value);
        $this->assertSame('Amina Noor', $version->display_name);
        $this->assertSame('Digital product builder', data_get($version->public_payload, 'en.display_position'));
        $this->get(route('en.athar.show', ['token' => $token]))
            ->assertOk()
            ->assertSee(__('athar.receipt.ready_body'))
            ->assertSee(__('athar.approval.words'))
            ->assertSee('class="athar-final-preview"', false)
            ->assertSee('x-text="text"', false)
            ->assertSee('name="text"', false)
            ->assertSee(__('athar.approval.edit'), false)
            ->assertSee('maxlength="350"', false)
            ->assertSee('class="athar-final-preview__quote" dir="auto"', false)
            ->assertSee('id="athar-approval-text"', false)
            ->assertSee('required dir="ltr" :dir="textDirection"', false)
            ->assertSee('dir="auto"', false)
            ->assertSee('class="athar-identity-select"', false)
            ->assertSee('role="listbox"', false)
            ->assertSee('value="Amina Noor"', false)
            ->assertSee('name="display_position"', false)
            ->assertSee('Digital product builder', false)
            ->assertDontSee('This exact text will appear only on my website.', false)
            ->assertDontSee('I agree to publish the exact text and name choice shown above only on Ibrahim’s website.', false)
            ->assertDontSee('Required only when you choose to show a full or first name.', false)
            ->assertSee('A thoughtful note about the work.', false);
        $this->post(route('en.athar.approval.draft', ['token' => $token]), [
            'text' => 'A saved endorsement draft.',
            'identity_display' => 'first_name',
            'display_name' => 'Amina Noor',
            'display_position' => 'Product builder',
        ])
            ->assertRedirect(route('en.athar.show', ['token' => $token]))
            ->assertSessionHas('status', __('athar.approval.draft_saved'));
        $this->assertSame('awaiting_approval', $version->fresh()->status->value);
        $this->assertSame('A saved endorsement draft.', data_get($version->fresh()->public_payload, 'en.text'));
        $this->assertSame('Product builder', data_get($version->fresh()->public_payload, 'en.display_position'));
        $this->get(route('en.athar.show', ['token' => $token]))
            ->assertSee('A saved endorsement draft.', false)
            ->assertSee(__('athar.approval.save_draft'))
            ->assertDontSee(__('athar.approval.private'));
        $this->from(route('en.athar.show', ['token' => $token]))
            ->post(route('en.athar.approve', ['token' => $token]))
            ->assertSessionHasErrors('consent');
        $this->assertSame('awaiting_approval', $version->fresh()->status->value);
        $this->post(route('en.athar.approve', ['token' => $token]), [
            'consent' => '1',
            'text' => 'A clearer endorsement after review.',
            'identity_display' => 'full_name',
            'display_name' => 'Amina Noor',
            'display_position' => 'Product builder | Manager at Example',
        ])->assertRedirect();
        $this->assertSame('published', $version->fresh()->status->value);
        $this->assertSame('Amina Noor', $version->fresh()->display_name);
        $this->assertSame('Amina Noor', data_get($version->fresh()->public_payload, 'en.display_name'));
        $this->assertSame('Product builder | Manager at Example', data_get($version->fresh()->public_payload, 'en.display_position'));
        $this->assertSame('completed', $invitation->fresh()->status->value);
        $this->assertDatabaseHas('athar_publication_consent_events', ['publication_version_id' => $version->getKey(), 'event_type' => 'approved']);
        $this->get(route('en.athar.show', ['token' => $token]))
            ->assertOk()
            ->assertSee(__('athar.published.words'))
            ->assertSee('class="athar-final-preview athar-final-preview--readonly"', false)
            ->assertSee('A clearer endorsement after review.', false)
            ->assertDontSee('name="text"', false);
        $this->get(route('en.about'))->assertOk()->assertSee('A clearer endorsement after review.');
        $invitation->forceFill(['recipient_name' => 'Changed by admin', 'identity_display' => 'anonymous'])->save();
        $proof = AtharPublicProof::forPlacement(AtharPlacement::About, 'en');
        $this->assertSame('Amina Noor | Product builder | Manager at Example', $proof[0]['name']);
        $this->post(route('en.athar.withdraw', ['token' => $token]), ['confirm' => '1'])->assertRedirect();
        $this->get(route('en.about'))->assertOk()->assertDontSee('A clearer endorsement after review.');
        $this->post(route('en.athar.restore', ['token' => $token]))->assertSessionHasErrors('confirm');
        $this->post(route('en.athar.restore', ['token' => $token]), ['confirm' => '1'])->assertRedirect();
        $this->assertSame('published', $version->fresh()->status->value);
        $this->get(route('en.about'))->assertOk()->assertSee('A clearer endorsement after review.');

        $this->post(route('en.athar.deletion', ['token' => $token]))->assertSessionHasErrors('confirm');
        $this->post(route('en.athar.deletion', ['token' => $token]), ['confirm' => '1'])->assertRedirect();
        $this->assertSame('deletion_requested', $invitation->contribution()->firstOrFail()->fresh()->status->value);
        $this->get(route('en.athar.show', ['token' => $token]))
            ->assertOk()
            ->assertSee(__('athar.published.deletion_pending'))
            ->assertSee(__('athar.published.deletion_cancel'));
        $this->post(route('en.athar.deletion.cancel', ['token' => $token]))->assertRedirect();
        $this->assertSame('published', $invitation->contribution()->firstOrFail()->fresh()->status->value);
    }

    public function test_invalid_or_expired_token_is_a_generic_unavailable_state(): void
    {
        $this->get(route('athar.show', ['token' => str_repeat('x', 64)]))->assertOk()->assertSee(__('athar.unavailable.title'));
    }

    public function test_a_sealed_contribution_without_a_version_opens_directly_in_approval_preview(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => '',
            'send_email' => false,
            'relationship' => AtharRelationship::Friend,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $contribution = $created['invitation']->contribution()->create([
            'status' => 'submitted',
            'sealed_payload' => ['freeform' => 'A legacy note that should be visible for approval.'],
            'source_hash' => hash('sha256', 'legacy-source'),
            'submitted_at' => now(),
        ]);

        $this->get($created['url'])
            ->assertOk()
            ->assertSee('A legacy note that should be visible for approval.', false)
            ->assertSee(__('athar.approval.edit'))
            ->assertDontSee('سيظهر النص النهائي هنا');

        $this->assertDatabaseHas('athar_publication_versions', [
            'contribution_id' => $contribution->getKey(),
            'status' => 'awaiting_approval',
        ]);
    }

    public function test_submission_seals_the_requested_contribution_when_other_contributions_exist(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $first = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => 'first@example.com',
            'recipient_name' => 'First Person',
            'relationship' => AtharRelationship::FormerClient,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $second = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => 'second@example.com',
            'recipient_name' => 'Second Person',
            'relationship' => AtharRelationship::Collaborator,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::Work,
        ]);
        $first['invitation']->contribution()->create(['status' => 'draft']);

        $this->grantAtharSession($second['token'], $second['invitation']);

        $this->post(route('athar.submit', ['token' => $second['token']]), ['freeform' => 'The note for the second person.'])
            ->assertRedirect();

        $this->assertDatabaseHas('athar_contributions', [
            'invitation_id' => $second['invitation']->getKey(),
            'status' => 'awaiting_approval',
        ]);
        $this->assertDatabaseHas('athar_contributions', [
            'invitation_id' => $first['invitation']->getKey(),
            'status' => 'draft',
        ]);
    }

    public function test_link_only_invitation_is_shareable_without_email_delivery_or_code_verification(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => '',
            'send_email' => false,
            'recipient_name' => 'Amina Noor',
            'relationship' => AtharRelationship::Collaborator,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::Work,
        ]);
        $invitation = $created['invitation']->fresh();

        $this->assertSame(AtharInvitationDeliveryMode::Link, $invitation->delivery_mode);
        $this->assertSame('ready', $invitation->status->value);
        $this->assertNull($invitation->email);
        $this->assertNull($invitation->email_hash);
        $this->assertNull($invitation->sent_at);
        $this->assertStringContainsString('/en/athar/', $created['url']);
        Notification::assertNothingSent();

        $this->get($created['url'])
            ->assertOk()
            ->assertSee(__('athar.reflection.title'))
            ->assertSee(__('athar.reflection.body'))
            ->assertSee(__('athar.reflection.review'))
            ->assertDontSee('What stayed with you?')
            ->assertSee('lang="ar"', false)
            ->assertDontSee('lang="en" hreflang="en"', false)
            ->assertDontSee('athar-mark__feature', false)
            ->assertDontSee('class="athar-kicker"', false)
            ->assertDontSee('This private link is the way to access the note')
            ->assertDontSee('A prompt, if useful')
            ->assertDontSee('name="email"', false);

        $this->post(route('athar.submit', ['token' => $created['token']]), ['freeform' => 'A thoughtful note shared by link.'])
            ->assertRedirect();
        $this->assertDatabaseHas('athar_contributions', [
            'invitation_id' => $invitation->getKey(),
            'status' => 'awaiting_approval',
        ]);
        Notification::assertNothingSent();
    }

    public function test_saving_a_private_draft_persists_it_for_the_current_invitation_after_redirect(): void
    {
        app()->setLocale('en');
        $creator = User::factory()->create();
        $first = app(CreateAtharInvitation::class)->handle($creator, [
            'send_email' => false,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $firstContribution = $first['invitation']->contribution()->create(['status' => 'draft']);
        $second = app(CreateAtharInvitation::class)->handle($creator, [
            'send_email' => false,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $draft = 'A private draft that should remain visible after saving.';

        $this->post(route('en.athar.draft', ['token' => $second['token']]), ['freeform' => $draft])
            ->assertRedirect(route('en.athar.show', ['token' => $second['token']]))
            ->assertSessionHas('status', __('athar.reflection.draft_saved'));

        $this->assertSame($draft, data_get($second['invitation']->contribution()->firstOrFail()->fresh()->draft_payload, 'freeform'));
        $this->assertNull(data_get($firstContribution->fresh()->draft_payload, 'freeform'));
        $this->get($second['url'])
            ->assertOk()
            ->assertSee($draft, false)
            ->assertSee(__('athar.reflection.draft_saved'));
    }

    public function test_email_delivery_requires_a_valid_email_when_enabled(): void
    {
        $this->expectException(ValidationException::class);

        app(CreateAtharInvitation::class)->handle(User::factory()->create(), [
            'send_email' => true,
            'relationship' => AtharRelationship::FormerClient,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
    }

    public function test_reflection_and_approval_texts_respect_their_editorial_limits(): void
    {
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => '',
            'send_email' => false,
            'relationship' => AtharRelationship::Friend,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);

        $this->post(route('athar.submit', ['token' => $created['token']]), ['freeform' => str_repeat('x', 351)])
            ->assertSessionHasErrors('freeform');
    }

    public function test_reflection_and_public_approval_use_the_same_350_character_limit(): void
    {
        app()->setLocale('en');
        $created = app(CreateAtharInvitation::class)->handle(User::factory()->create(), [
            'send_email' => false,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $privateText = str_repeat('x', 351);

        $this->post(route('en.athar.submit', ['token' => $created['token']]), ['freeform' => $privateText])
            ->assertSessionHasErrors('freeform');

        $this->post(route('en.athar.submit', ['token' => $created['token']]), ['freeform' => str_repeat('x', 350)])
            ->assertRedirect();

        $version = $created['invitation']->contribution()->firstOrFail()->publicationVersions()->latest('version')->firstOrFail();
        $this->assertSame(str_repeat('x', 350), data_get($created['invitation']->contribution()->firstOrFail()->fresh()->sealed_payload, 'freeform'));
        $this->assertSame(str_repeat('x', 350), data_get($version->public_payload, 'en.text'));
        $this->post(route('en.athar.approve', ['token' => $created['token']]), [
            'consent' => '1',
            'text' => $privateText,
            'identity_display' => 'anonymous',
        ])->assertSessionHasErrors('text');
    }

    public function test_the_contributor_must_supply_the_name_they_choose_to_publish(): void
    {
        app()->setLocale('en');
        $created = app(CreateAtharInvitation::class)->handle(User::factory()->create(), [
            'send_email' => false,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $this->post(route('en.athar.submit', ['token' => $created['token']]), ['freeform' => 'A private reflection.'])->assertRedirect();

        $this->post(route('en.athar.approve', ['token' => $created['token']]), [
            'consent' => '1',
            'text' => 'A short public endorsement.',
            'identity_display' => 'full_name',
        ])->assertSessionHasErrors('display_name');
    }

    public function test_the_public_draft_uses_the_language_of_the_page_the_contributor_used(): void
    {
        app()->setLocale('ar');
        $created = app(CreateAtharInvitation::class)->handle(User::factory()->create(), [
            'send_email' => false,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);

        $this->post(route('athar.submit', ['token' => $created['token']]), ['freeform' => 'نص قصير للمراجعة.'])->assertRedirect();

        $version = $created['invitation']->contribution()->firstOrFail()->publicationVersions()->latest('version')->firstOrFail();
        $this->assertArrayHasKey('ar', $version->public_payload);
        $this->assertArrayNotHasKey('en', $version->public_payload);
        $this->get(route('athar.show', ['token' => $created['token']]))
            ->assertOk()
            ->assertSee('required dir="rtl" :dir="textDirection"', false);
    }

    public function test_arabic_digits_are_accepted_for_the_email_access_code(): void
    {
        app()->setLocale('ar');
        $created = app(CreateAtharInvitation::class)->handle(User::factory()->create(), [
            'email' => 'friend@example.com',
            'preferred_locale' => 'ar',
            'placement' => AtharPlacement::About,
        ]);
        AtharAccessChallenge::query()->create([
            'invitation_id' => $created['invitation']->getKey(),
            'code_hash' => AtharAccess::codeHash('123456'),
            'expires_at' => now()->addMinutes(10),
            'requested_at' => now(),
            'ip_hash' => hash_hmac('sha256', '127.0.0.1', (string) config('app.key')),
        ]);

        $this->withSession(['athar.code_sent' => $created['invitation']->getKey()])
            ->get(route('athar.show', ['token' => $created['token']]))
            ->assertOk()
            ->assertSee('class="athar-code-inputs"', false)
            ->assertSee('name="code_digits[]"', false)
            ->assertSee('handlePaste', false);

        $this->post(route('athar.verify', ['token' => $created['token']]), [
            'code_digits' => ['١', '٢', '٣', '٤', '٥', '٦'],
        ])->assertRedirect();

        $this->assertSame('verified', $created['invitation']->fresh()->status->value);
    }

    public function test_email_access_code_controls_expiry_attempts_and_resending(): void
    {
        Notification::fake();
        $created = app(CreateAtharInvitation::class)->handle(User::factory()->create(), [
            'email' => 'friend@example.com',
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);

        $this->post(route('en.athar.code', ['token' => $created['token']]), ['email' => 'friend@example.com'])
            ->assertRedirect();

        $challenge = AtharAccessChallenge::query()->where('invitation_id', $created['invitation']->getKey())->latest('id')->firstOrFail();
        $this->assertSame(6, $challenge->attemptsRemaining());
        $this->get(route('en.athar.show', ['token' => $created['token']]))
            ->assertOk()
            ->assertSee('atharAccessCode', false)
            ->assertSee('attemptsRemaining', false)
            ->assertSee('resendAvailableAt', false)
            ->assertSee('The code has a limited lifetime and a limited number of attempts.', false);

        $this->post(route('en.athar.code', ['token' => $created['token']]))
            ->assertSessionHasErrors('code');

        $this->travel(61)->seconds();
        $this->post(route('en.athar.code', ['token' => $created['token']]))
            ->assertRedirect();
        $this->assertSame(2, AtharAccessChallenge::query()->where('invitation_id', $created['invitation']->getKey())->count());
        $this->travelBack();
    }

    public function test_email_access_code_locks_after_six_failed_attempts_and_requires_a_new_code(): void
    {
        Notification::fake();
        $created = app(CreateAtharInvitation::class)->handle(User::factory()->create(), [
            'email' => 'friend@example.com',
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);

        $this->post(route('en.athar.code', ['token' => $created['token']]), ['email' => 'friend@example.com'])
            ->assertRedirect();

        foreach (range(1, 6) as $attempt) {
            $this->post(route('en.athar.verify', ['token' => $created['token']]), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        $challenge = AtharAccessChallenge::query()->where('invitation_id', $created['invitation']->getKey())->latest('id')->firstOrFail();
        $this->assertSame(6, $challenge->attempts);
        $this->assertTrue($challenge->isLocked());
        $this->post(route('en.athar.verify', ['token' => $created['token']]), ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertSame(6, $challenge->fresh()->attempts);

        $this->travel(61)->seconds();
        $this->post(route('en.athar.code', ['token' => $created['token']]))
            ->assertRedirect();
        $this->assertSame(2, AtharAccessChallenge::query()->where('invitation_id', $created['invitation']->getKey())->count());
        $this->travelBack();
    }

    public function test_email_access_code_requests_are_capped_per_hour(): void
    {
        Notification::fake();
        config()->set('athar.access.max_code_requests_per_hour', 1);
        $created = app(CreateAtharInvitation::class)->handle(User::factory()->create(), [
            'email' => 'friend@example.com',
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);

        $this->post(route('en.athar.code', ['token' => $created['token']]), ['email' => 'friend@example.com'])
            ->assertRedirect();
        $this->travel(61)->seconds();
        $this->post(route('en.athar.code', ['token' => $created['token']]))
            ->assertSessionHasErrors('code')
            ->assertSessionHasErrors(['code' => __('athar.access.request_limit')]);
        $this->assertSame(1, AtharAccessChallenge::query()->where('invitation_id', $created['invitation']->getKey())->count());
        $this->travelBack();
    }

    public function test_email_access_session_remains_valid_for_three_days_and_not_longer(): void
    {
        Notification::fake();
        $created = app(CreateAtharInvitation::class)->handle(User::factory()->create(), [
            'email' => 'friend@example.com',
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $verifiedAt = now();

        $response = $this->grantAtharSession($created['token'], $created['invitation'], locale: 'en');
        $accessCookie = collect($response->headers->getCookies())->first(fn ($cookie): bool => $cookie->getName() === 'athar-verified-'.$created['invitation']->getKey());
        $this->assertNotNull($accessCookie);
        $this->flushSession();
        $this->withUnencryptedCookie($accessCookie->getName(), $accessCookie->getValue());
        $this->travelTo($verifiedAt->addDays(3)->subSecond());
        $this->get(route('en.athar.show', ['token' => $created['token']]))
            ->assertOk()
            ->assertSee(__('athar.reflection.title'));

        $this->travelTo($verifiedAt->addDays(3));
        $this->get(route('en.athar.show', ['token' => $created['token']]))
            ->assertOk()
            ->assertSee(__('athar.access.title'));

        $this->travelTo($verifiedAt->addDays(3)->addSecond());
        $this->get(route('en.athar.show', ['token' => $created['token']]))
            ->assertOk()
            ->assertSee(__('athar.access.title'));
        $this->travelBack();
    }

    public function test_a_hidden_endorsement_has_a_truthful_contributor_state_without_a_restore_control(): void
    {
        app()->setLocale('en');
        $created = app(CreateAtharInvitation::class)->handle(User::factory()->create(), [
            'send_email' => false,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $this->post(route('en.athar.submit', ['token' => $created['token']]), ['freeform' => 'A hidden endorsement.'])->assertRedirect();
        $version = $created['invitation']->contribution()->firstOrFail()->publicationVersions()->latest('version')->firstOrFail();
        $this->post(route('en.athar.approve', ['token' => $created['token']]), [
            'consent' => '1',
            'text' => 'A hidden endorsement.',
            'identity_display' => 'anonymous',
        ])->assertRedirect();
        app(HideAtharPublication::class)->handle($version->fresh());

        $this->get(route('en.athar.show', ['token' => $created['token']]))
            ->assertOk()
            ->assertSee(__('athar.published.hidden'))
            ->assertDontSee(__('athar.published.restore'));
    }

    public function test_link_only_approval_enters_awaiting_state_without_attempting_email_delivery(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => '',
            'send_email' => false,
            'recipient_name' => 'Amina Noor',
            'relationship' => AtharRelationship::Collaborator,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::Services,
        ]);
        $contribution = $created['invitation']->contribution()->create([
            'status' => 'submitted',
            'sealed_payload' => ['freeform' => 'A thoughtful note shared by link.'],
            'source_hash' => hash('sha256', 'link-only-source'),
            'submitted_at' => now(),
        ]);

        $version = app(CreateContributorPublicNote::class)->handle(
            $contribution,
            ['en' => ['text' => 'A thoughtful note shared by link.', 'context' => 'Services context.']],
        );
        app(SendAtharApproval::class)->handle($version);

        $this->assertSame('awaiting_approval', $version->fresh()->status->value);
        $this->assertSame('awaiting_approval', $contribution->fresh()->status->value);
        Notification::assertNothingSent();
    }

    public function test_the_access_code_request_is_blocked_when_the_turnstile_token_fails(): void
    {
        Notification::fake();
        config()->set('services.turnstile.secret', 'test-secret');
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
        ]);

        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => 'friend@example.com',
            'recipient_name' => 'Amina Noor',
            'relationship' => AtharRelationship::FormerClient,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);

        // Without a verified session, the access form is shown. A failing
        // Turnstile token must short-circuit before any access code is issued.
        $this->post(route('athar.code', ['token' => $created['token']]), [
            'email' => 'friend@example.com',
            'cf-turnstile-response' => 'forged-token',
        ])->assertSessionHasErrors('turnstile');

        $this->assertDatabaseMissing('athar_access_challenges', [
            'invitation_id' => $created['invitation']->getKey(),
        ]);
    }

    public function test_email_mode_writes_are_blocked_until_the_access_code_is_verified(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => 'friend@example.com',
            'relationship' => AtharRelationship::FormerClient,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);

        // Holding the token alone must not allow a write without a verified session.
        $this->post(route('athar.submit', ['token' => $created['token']]), ['freeform' => 'An unverified note.'])
            ->assertRedirect(route('athar.show', ['token' => $created['token']]))
            ->assertSessionHas('status', __('athar.access.session_expired'));

        $this->assertDatabaseMissing('athar_contributions', [
            'invitation_id' => $created['invitation']->getKey(),
            'submitted_at' => null,
        ]);

        $this->get(route('athar.show', ['token' => $created['token']]))
            ->assertOk()
            ->assertSee(__('athar.access.session_expired'));
        $this->grantAtharSession($created['token'], $created['invitation']);
        $this->get(route('athar.show', ['token' => $created['token']]))
            ->assertOk()
            ->assertSee('An unverified note.', false);
    }

    public function test_a_fingerprint_mismatch_after_grant_re_challenges_the_contributor(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => 'friend@example.com',
            'relationship' => AtharRelationship::FormerClient,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $token = $created['token'];
        $invitation = $created['invitation'];

        $this->grantAtharSession($token, $invitation);
        $this->get(route('athar.show', ['token' => $token]))->assertSee(__('athar.reflection.title'));

        // A different user agent changes the fingerprint, invalidating the grant.
        $this->withHeaders(['User-Agent' => 'a-different-browser'])
            ->get(route('athar.show', ['token' => $token]))
            ->assertSee(__('athar.access.title'))
            ->assertSee('name="email"', false);
    }

    public function test_the_consent_audit_records_the_link_verification_method_for_link_invitations(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => '',
            'send_email' => false,
            'relationship' => AtharRelationship::Collaborator,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::Work,
        ]);
        $token = $created['token'];
        $invitation = $created['invitation'];

        // Link-mode invitations bypass the code gate and reach reflection directly.
        $this->get(route('athar.show', ['token' => $token]))->assertSee(__('athar.reflection.title'));
        $this->post(route('athar.submit', ['token' => $token]), ['freeform' => 'A link-mode endorsement.'])->assertRedirect();

        $contribution = $invitation->contribution()->firstOrFail();
        $version = $contribution->publicationVersions()->latest('version')->firstOrFail();
        $this->post(route('athar.approve', ['token' => $token]), ['consent' => '1', 'text' => 'A link-mode endorsement.', 'identity_display' => 'anonymous'])->assertRedirect();

        $this->assertDatabaseHas('athar_publication_consent_events', [
            'publication_version_id' => $version->getKey(),
            'event_type' => 'approved',
            'verification_method' => 'link',
        ]);
    }

    public function test_requesting_deletion_scrubs_private_data_but_leaves_a_published_endorsement_visible(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => '',
            'send_email' => false,
            'relationship' => AtharRelationship::FormerClient,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $token = $created['token'];
        $invitation = $created['invitation'];

        $this->post(route('en.athar.submit', ['token' => $token]), ['freeform' => 'A published endorsement.'])->assertRedirect();
        $version = $invitation->contribution()->firstOrFail()->publicationVersions()->latest('version')->firstOrFail();
        $this->post(route('en.athar.approve', ['token' => $token]), ['consent' => '1', 'text' => 'A published endorsement.', 'identity_display' => 'anonymous'])->assertRedirect();

        // The endorsement is live before deletion is requested.
        $this->get(route('en.about'))->assertOk()->assertSee('A published endorsement.');

        $this->post(route('en.athar.deletion', ['token' => $token]), ['confirm' => '1'])->assertRedirect();

        $contribution = $invitation->contribution()->firstOrFail();
        $this->assertSame('deletion_requested', $contribution->fresh()->status->value);
        $this->assertNull($contribution->fresh()->sealed_payload);
        $this->assertNull($contribution->fresh()->draft_payload);
        $this->assertNull($contribution->fresh()->source_hash);
        // A published version is not soft-deleted, so the public endorsement stays.
        $this->assertNull($contribution->fresh()->deleted_at);
        $this->get(route('en.about'))->assertOk()->assertSee('A published endorsement.');
    }

    public function test_requesting_deletion_soft_deletes_a_contribution_with_no_live_publication(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => '',
            'send_email' => false,
            'relationship' => AtharRelationship::FormerClient,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $token = $created['token'];
        $invitation = $created['invitation'];

        // Publish, then withdraw so nothing remains live.
        $this->post(route('athar.submit', ['token' => $token]), ['freeform' => 'Later withdrawn.'])->assertRedirect();
        $version = $invitation->contribution()->firstOrFail()->publicationVersions()->latest('version')->firstOrFail();
        $this->post(route('athar.approve', ['token' => $token]), ['consent' => '1', 'text' => 'Later withdrawn.', 'identity_display' => 'anonymous'])->assertRedirect();
        $this->post(route('athar.withdraw', ['token' => $token]), ['confirm' => '1'])->assertRedirect();

        $this->post(route('athar.deletion', ['token' => $token]), ['confirm' => '1'])->assertRedirect();

        // No published version remains, so the contribution row is queued for purge.
        $this->assertNotNull($invitation->contribution()->firstOrFail()->fresh()->deleted_at);
    }
}
