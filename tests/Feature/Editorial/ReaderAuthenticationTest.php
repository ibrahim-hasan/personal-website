<?php

namespace Tests\Feature\Editorial;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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
        ])->assertRedirect('/reader/verify-email');

        $reader = User::query()->where('email', 'reader@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($reader);
        $this->assertFalse($reader->canAccessPanel(filament()->getPanel('admin')));
        $this->get('/admin')->assertForbidden();
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
            VerifyEmail::class,
            function (VerifyEmail $notification) use ($reader, &$verificationUrl): bool {
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
}
