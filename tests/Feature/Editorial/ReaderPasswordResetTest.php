<?php

namespace Tests\Feature\Editorial;

use App\Models\User;
use App\Notifications\Auth\AdminResetPasswordNotification;
use App\Notifications\Auth\ReaderResetPasswordNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ReaderPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_password_recovery_pages_are_localized(): void
    {
        $this->get('/reader/forgot-password')
            ->assertOk()
            ->assertSee('استعادة حساب القارئ', false);

        $this->get('/en/reader/forgot-password')
            ->assertOk()
            ->assertSee('Recover your reader account', false);
    }

    public function test_a_known_reader_receives_a_localized_reader_reset_link(): void
    {
        Notification::fake();
        $reader = User::factory()->create([
            'email' => 'reader@example.com',
            'locale_preference' => 'en',
        ]);

        $this->post('/reader/forgot-password', [
            'email' => ' READER@EXAMPLE.COM ',
        ])->assertSessionHas('status', __('reader_auth.reset_link_sent'));

        Notification::assertSentTo(
            $reader,
            ReaderResetPasswordNotification::class,
            function (ReaderResetPasswordNotification $notification) use ($reader): bool {
                $mail = $notification->toMail($reader);

                return $mail->subject === 'Reset your reader account password'
                    && str_contains($mail->actionUrl, '/en/reader/reset-password/')
                    && str_contains($mail->actionUrl, 'email=reader%40example.com');
            },
        );
    }

    public function test_an_unknown_email_receives_the_same_generic_response_without_a_notification(): void
    {
        Notification::fake();

        $this->post('/reader/forgot-password', [
            'email' => 'unknown@example.com',
        ])->assertSessionHas('status', __('reader_auth.reset_link_sent'));

        Notification::assertNothingSent();
    }

    public function test_a_valid_token_updates_the_password_rotates_remember_token_and_returns_to_login(): void
    {
        Event::fake([PasswordReset::class]);
        $reader = User::factory()->create([
            'email' => 'reader@example.com',
            'password' => 'old-reader-password',
        ]);
        $oldRememberToken = $reader->remember_token;
        $token = Password::broker()->createToken($reader);

        $this->get('/reader/reset-password/'.$token.'?email=reader%40example.com')
            ->assertOk()
            ->assertSee('value="'.$token.'"', false)
            ->assertSee('reader@example.com', false);

        $this->post('/reader/reset-password', [
            'token' => $token,
            'email' => 'reader@example.com',
            'password' => 'new-secure-reader-password!42',
            'password_confirmation' => 'new-secure-reader-password!42',
        ])->assertRedirect('/reader/login')
            ->assertSessionHas('status', __('reader_auth.password_reset_success'));

        $reader->refresh();
        $this->assertTrue(Hash::check('new-secure-reader-password!42', $reader->password));
        $this->assertNotSame($oldRememberToken, $reader->remember_token);
        Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($reader));
    }

    public function test_an_invalid_token_does_not_change_the_password(): void
    {
        $reader = User::factory()->create([
            'email' => 'reader@example.com',
            'password' => 'old-reader-password',
        ]);

        $this->from('/reader/reset-password/invalid-token?email=reader%40example.com')
            ->post('/reader/reset-password', [
                'token' => 'invalid-token',
                'email' => 'reader@example.com',
                'password' => 'new-secure-reader-password!42',
                'password_confirmation' => 'new-secure-reader-password!42',
            ])->assertRedirect('/reader/reset-password/invalid-token?email=reader%40example.com')
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-reader-password', $reader->fresh()->password));
    }

    public function test_admin_password_reset_requests_keep_the_admin_notification(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $this->app->instance('request', Request::create('/admin/password-reset/request', 'POST'));

        $admin->sendPasswordResetNotification('admin-reset-token');

        Notification::assertSentTo($admin, AdminResetPasswordNotification::class);
        Notification::assertNotSentTo($admin, ReaderResetPasswordNotification::class);
    }
}
