<?php

namespace Tests\Feature;

use App\Actions\Athar\CreateAtharInvitation;
use App\Actions\Athar\PrepareAtharPublicationVersion;
use App\Actions\Athar\SendAtharApproval;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharPlacement;
use App\Enums\AtharRelationship;
use App\Models\AtharContribution;
use App\Models\User;
use App\Notifications\AtharInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AtharFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_flow_opens_from_the_invitation_link_and_publishes_only_the_approved_snapshot(): void
    {
        Notification::fake();
        $creator = User::factory()->create();
        $created = app(CreateAtharInvitation::class)->handle($creator, [
            'email' => 'friend@example.com',
            'recipient_name' => 'Amina Noor',
            'relationship' => AtharRelationship::FormerClient,
            'preferred_locale' => 'en',
            'placement' => AtharPlacement::About,
        ]);
        $token = $created['token'];
        $invitation = $created['invitation'];
        $this->assertSame(AtharInvitationDeliveryMode::Email, $invitation->delivery_mode);
        Notification::assertSentOnDemand(AtharInvitationNotification::class);

        $response = $this->get(route('athar.show', ['token' => $token]));
        $response
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertSee('href="'.asset('favicon.svg').'" type="image/svg+xml"', false)
            ->assertSee('href="'.asset('apple-touch-icon.png').'"', false)
            ->assertSee(__('athar.reflection.title'))
            ->assertSee(__('athar.reflection.body'))
            ->assertSee(__('athar.reflection.review'))
            ->assertDontSee('ما الذي بقي معك من تجربتنا؟')
            ->assertSee('lang="en"', false)
            ->assertDontSee('lang="ar" hreflang="ar"', false)
            ->assertDontSee('athar-mark__feature', false)
            ->assertDontSee('class="athar-kicker"', false)
            ->assertDontSee('هذا رابط خاص للوصول إلى الرسالة')
            ->assertDontSee('سؤال مساعد')
            ->assertDontSee('friend@example.com')
            ->assertDontSee('name="email"', false);
        $this->assertStringContainsString('maxlength="2000"', $response->getContent());
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->post(route('athar.submit', ['token' => $token]), ['freeform' => 'A thoughtful note about the work.'])->assertRedirect();
        $this->get(route('athar.show', ['token' => $token, 'choose' => '1']))
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
        $this->get(route('athar.show', ['token' => $token]))
            ->assertOk()
            ->assertSee(__('athar.receipt.ready_body'))
            ->assertSee(__('athar.approval.words'))
            ->assertSee('class="athar-final-preview"', false)
            ->assertSee('x-text="text"', false)
            ->assertSee('name="text"', false)
            ->assertSee(__('athar.approval.edit'), false)
            ->assertSee('maxlength="350"', false)
            ->assertSee('A thoughtful note about the work.', false);
        $this->post(route('athar.approval.draft', ['token' => $token]), ['text' => 'A saved endorsement draft.'])
            ->assertRedirect()
            ->assertSessionHas('status', __('athar.approval.draft_saved'));
        $this->assertSame('awaiting_approval', $version->fresh()->status->value);
        $this->assertSame('A saved endorsement draft.', data_get($version->fresh()->public_payload, 'en.text'));
        $this->get(route('athar.show', ['token' => $token]))
            ->assertSee('A saved endorsement draft.', false)
            ->assertSee(__('athar.approval.save_draft'))
            ->assertDontSee(__('athar.approval.private'));
        $this->from(route('athar.show', ['token' => $token]))
            ->post(route('athar.approve', ['token' => $token]))
            ->assertSessionHasErrors('consent');
        $this->assertSame('awaiting_approval', $version->fresh()->status->value);
        $this->post(route('athar.approve', ['token' => $token]), ['consent' => '1', 'text' => 'A clearer endorsement after review.'])->assertRedirect();
        $this->assertSame('published', $version->fresh()->status->value);
        $this->assertDatabaseHas('athar_publication_consent_events', ['publication_version_id' => $version->getKey(), 'event_type' => 'approved']);
        $this->get(route('athar.show', ['token' => $token]))
            ->assertOk()
            ->assertSee(__('athar.published.words'))
            ->assertSee('class="athar-final-preview athar-final-preview--readonly"', false)
            ->assertSee('A clearer endorsement after review.', false)
            ->assertDontSee('name="text"', false);
        $this->get(route('en.about'))->assertOk()->assertSee('A clearer endorsement after review.');
        $this->post(route('athar.withdraw', ['token' => $token]), ['confirm' => '1'])->assertRedirect();
        $this->get(route('en.about'))->assertOk()->assertDontSee('A clearer endorsement after review.');
        $this->post(route('athar.restore', ['token' => $token]))->assertSessionHasErrors('confirm');
        $this->post(route('athar.restore', ['token' => $token]), ['confirm' => '1'])->assertRedirect();
        $this->assertSame('published', $version->fresh()->status->value);
        $this->get(route('en.about'))->assertOk()->assertSee('A clearer endorsement after review.');

        $this->post(route('athar.deletion', ['token' => $token]))->assertSessionHasErrors('confirm');
        $this->post(route('athar.deletion', ['token' => $token]), ['confirm' => '1'])->assertRedirect();
        $this->assertSame('deletion_requested', $invitation->contribution()->firstOrFail()->fresh()->status->value);
        $this->get(route('athar.show', ['token' => $token]))
            ->assertOk()
            ->assertSee(__('athar.published.deletion_pending'))
            ->assertSee(__('athar.published.deletion_cancel'));
        $this->post(route('athar.deletion.cancel', ['token' => $token]))->assertRedirect();
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

        $this->post(route('athar.submit', ['token' => $created['token']]), ['freeform' => str_repeat('x', 2001)])
            ->assertSessionHasErrors('freeform');
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

        $version = app(PrepareAtharPublicationVersion::class)->handle(
            $contribution,
            ['en' => ['text' => 'A thoughtful note shared by link.', 'context' => 'Services context.']],
            AtharPlacement::Services,
            null,
            AtharIdentityDisplay::Anonymous,
            $creator,
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
}
