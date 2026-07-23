<?php

namespace Tests\Feature\Editorial;

use App\Models\User;
use App\Notifications\Auth\ReaderVerifyEmailNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ReaderAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reader_can_register_but_cannot_enter_filament(): void
    {
        Notification::fake();

        $this->post('/reader/register', [
            'name' => 'Thoughtful Reader',
            'email' => 'reader@example.com',
            'password' => 'A-secure-reader-password!42',
            'password_confirmation' => 'A-secure-reader-password!42',
            'terms_accepted' => '1',
        ])->assertRedirect('/reader/verify-email');

        $reader = User::query()->where('email', 'reader@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($reader);
        $this->assertNotNull($reader->terms_accepted_at);
        $this->assertSame(config('legal.terms_version'), $reader->terms_version);
        $this->assertFalse($reader->canAccessPanel(filament()->getPanel('admin')));
        $this->get('/admin')->assertForbidden();
    }

    public function test_a_reader_must_explicitly_accept_the_current_terms_to_register(): void
    {
        $this->post('/en/reader/register', [
            'name' => 'Thoughtful Reader',
            'email' => 'reader@example.com',
            'password' => 'A-secure-reader-password!42',
            'password_confirmation' => 'A-secure-reader-password!42',
        ])->assertSessionHasErrors('terms_accepted');

        $this->assertDatabaseMissing('users', ['email' => 'reader@example.com']);
        $this->assertGuest();
    }

    public function test_login_returns_a_verified_reader_to_the_original_article(): void
    {
        $reader = User::factory()->create([
            'email' => 'reader@example.com',
            'password' => 'reader-password',
        ]);

        $this->get('/reader/login?return=/writing/an-article')->assertOk();

        $this->post('/reader/login', [
            'email' => $reader->email,
            'password' => 'reader-password',
        ])->assertRedirect('/writing/an-article');
    }

    public function test_english_reader_library_login_preserves_the_intended_locale(): void
    {
        $reader = User::factory()->create([
            'email' => 'english-reader@example.com',
            'password' => 'reader-password',
            'locale_preference' => 'ar',
        ]);

        $this->get('/en/reader/library')
            ->assertRedirect('/en/reader/login')
            ->assertSessionHas('url.intended', url('/en/reader/library'));

        $this->post('/en/reader/login', [
            'email' => $reader->email,
            'password' => 'reader-password',
        ])->assertRedirect('/en/reader/library');

        $this->assertAuthenticatedAs($reader);
        $this->assertSame('en', $reader->fresh()->locale_preference);
    }

    public function test_english_verification_flow_returns_the_reader_to_the_library(): void
    {
        Notification::fake();
        $reader = User::factory()->unverified()->create([
            'email' => 'unverified-reader@example.com',
            'password' => 'reader-password',
            'locale_preference' => 'ar',
        ]);

        $this->get('/en/reader/library')->assertRedirect('/en/reader/login');
        $this->post('/en/reader/login', [
            'email' => $reader->email,
            'password' => 'reader-password',
        ])->assertRedirect('/en/reader/verify-email');

        $this->from('/en/reader/verify-email')
            ->post('/en/reader/email/verification-notification')
            ->assertRedirect('/en/reader/verify-email')
            ->assertSessionHas('status', 'verification-link-sent');

        $reader->refresh();
        $verificationUrl = null;
        Notification::assertSentTo(
            $reader,
            ReaderVerifyEmailNotification::class,
            function (ReaderVerifyEmailNotification $notification) use ($reader, &$verificationUrl): bool {
                $verificationUrl = $notification->toMail($reader)->actionUrl;

                return is_string($verificationUrl)
                    && str_contains($verificationUrl, '/en/reader/email/verify/');
            },
        );

        $this->assertIsString($verificationUrl);
        $this->get($verificationUrl)->assertRedirect('/en/reader/library');
        $this->assertTrue($reader->fresh()->hasVerifiedEmail());
    }

    public function test_a_signed_verification_link_verifies_the_reader(): void
    {
        Event::fake([Verified::class]);
        $reader = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
            'id' => $reader->getKey(),
            'hash' => sha1($reader->getEmailForVerification()),
        ]);

        $this->actingAs($reader)->get($url)->assertRedirect('/writing');

        $this->assertTrue($reader->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_the_reading_list_requires_a_verified_email(): void
    {
        $reader = User::factory()->unverified()->create();

        $this->actingAs($reader)
            ->get('/reader/library')
            ->assertRedirect('/reader/verify-email');
    }

    public function test_a_guest_is_routed_to_reader_login_before_protected_reader_pages(): void
    {
        $this->get('/reader/library')->assertRedirect('/reader/login');
        $this->get('/login')->assertRedirect('/reader/login');
    }

    public function test_an_inactive_reader_session_is_terminated(): void
    {
        $reader = User::factory()->create();
        $reader->forceFill(['is_active' => false])->save();

        $this->actingAs($reader)
            ->get('/en/reader/library')
            ->assertRedirect('/en/reader/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_inactive_reader_cannot_start_a_new_session(): void
    {
        $reader = User::factory()->create([
            'email' => 'inactive-reader@example.com',
            'password' => 'reader-password',
            'is_active' => false,
        ]);

        $this->post('/reader/login', [
            'email' => $reader->email,
            'password' => 'reader-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_reader_can_register_with_a_valid_turnstile_token_when_enabled(): void
    {
        Notification::fake();
        config()->set('services.turnstile.secret', 'test-secret');
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);

        $this->post('/reader/register', [
            'name' => 'Human Reader',
            'email' => 'human@example.com',
            'password' => 'A-secure-reader-password!42',
            'password_confirmation' => 'A-secure-reader-password!42',
            'terms_accepted' => '1',
            'cf-turnstile-response' => 'verified-by-cloudflare',
        ])->assertRedirect('/reader/verify-email');

        $this->assertDatabaseHas('users', ['email' => 'human@example.com']);
    }

    public function test_registration_is_rejected_when_the_turnstile_token_fails_verification(): void
    {
        config()->set('services.turnstile.secret', 'test-secret');
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
        ]);

        $this->post('/reader/register', [
            'name' => 'Bot Reader',
            'email' => 'bot@example.com',
            'password' => 'A-secure-reader-password!42',
            'password_confirmation' => 'A-secure-reader-password!42',
            'terms_accepted' => '1',
            'cf-turnstile-response' => 'forged-token',
        ])->assertSessionHasErrors('cf-turnstile-response');

        $this->assertDatabaseMissing('users', ['email' => 'bot@example.com']);
        $this->assertGuest();
    }

    public function test_registration_is_rejected_without_a_turnstile_token_when_enabled(): void
    {
        config()->set('services.turnstile.secret', 'test-secret');
        Http::fake();

        $this->post('/reader/register', [
            'name' => 'Tokenless Reader',
            'email' => 'tokenless@example.com',
            'password' => 'A-secure-reader-password!42',
            'password_confirmation' => 'A-secure-reader-password!42',
            'terms_accepted' => '1',
        ])->assertSessionHasErrors('cf-turnstile-response');

        Http::assertNothingSent();
        $this->assertGuest();
    }
}
