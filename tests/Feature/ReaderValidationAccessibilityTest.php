<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ReaderValidationAccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_reader_forms_render_localized_focusable_error_summaries(): void
    {
        $scenarios = [
            [
                'path' => '/reader/login',
                'summaryId' => 'reader-login-error-summary',
                'fieldId' => 'reader-login-email',
                'locale' => 'ar',
            ],
            [
                'path' => '/en/reader/register',
                'summaryId' => 'reader-register-error-summary',
                'fieldId' => 'reader-register-name',
                'locale' => 'en',
            ],
            [
                'path' => '/reader/forgot-password',
                'summaryId' => 'reader-forgot-password-error-summary',
                'fieldId' => 'reader-forgot-password-email',
                'locale' => 'ar',
            ],
        ];

        foreach ($scenarios as $scenario) {
            $this->from($scenario['path'])
                ->post($scenario['path'])
                ->assertRedirect($scenario['path']);

            $this->assertValidationSummary(
                $this->get($scenario['path']),
                $scenario['summaryId'],
                $scenario['fieldId'],
                $scenario['locale'],
            );
        }
    }

    public function test_reset_and_terms_forms_link_validation_summaries_to_their_controls(): void
    {
        $resetPath = '/en/reader/reset-password/invalid-token?email=reader%40example.com';

        $this->from($resetPath)
            ->post('/en/reader/reset-password', [
                'token' => 'invalid-token',
                'email' => 'not-an-email',
            ])
            ->assertRedirect($resetPath);

        $this->assertValidationSummary(
            $this->get($resetPath),
            'reader-reset-password-error-summary',
            'reader-reset-password-email',
            'en',
        );

        $reader = User::factory()->create(['terms_version' => '2026-07-01']);

        $this->actingAs($reader)
            ->from('/reader/terms/accept')
            ->post('/reader/terms/accept')
            ->assertRedirect('/reader/terms/accept');

        $this->assertValidationSummary(
            $this->actingAs($reader)->get('/reader/terms/accept'),
            'reader-terms-acceptance-error-summary',
            'reader-terms-acceptance-checkbox',
            'ar',
        );
    }

    public function test_reader_account_respects_each_named_error_bag_and_keeps_guidance_connected(): void
    {
        $reader = User::factory()->create([
            'email' => 'reader@example.com',
            'password' => 'reader-password',
        ]);

        $this->actingAs($reader)
            ->from('/en/reader/account')
            ->patch('/en/reader/account', [
                'name' => $reader->name,
                'email' => 'new-reader@example.com',
            ])
            ->assertRedirect('/en/reader/account');

        $this->assertValidationSummary(
            $this->actingAs($reader)->get('/en/reader/account'),
            'reader-profile-error-summary',
            'reader-profile-current-password',
            'en',
        );

        $this->actingAs($reader)
            ->from('/en/reader/account')
            ->put('/en/reader/account/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-secure-reader-password!42',
                'password_confirmation' => 'new-secure-reader-password!42',
            ])
            ->assertRedirect('/en/reader/account');

        $this->assertValidationSummary(
            $this->actingAs($reader)->get('/en/reader/account'),
            'reader-password-error-summary',
            'reader-password-current-password',
            'en',
        );

        $this->actingAs($reader)
            ->from('/en/reader/account')
            ->delete('/en/reader/account', [
                'current_password' => 'wrong-password',
                'acknowledgement' => '1',
            ])
            ->assertRedirect('/en/reader/account');

        $this->assertValidationSummary(
            $this->actingAs($reader)->get('/en/reader/account'),
            'reader-account-deletion-error-summary',
            'reader-account-deletion-current-password',
            'en',
        );
    }

    public function test_password_guidance_is_available_in_both_locales_and_reader_password_flows(): void
    {
        foreach (['ar' => '/reader/register', 'en' => '/en/reader/register'] as $locale => $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee(__('reader_auth.password_guidance', locale: $locale), false)
                ->assertSee('aria-describedby="reader-register-password-guidance"', false);
        }

        $this->get('/reader/reset-password/token?email=reader%40example.com')
            ->assertOk()
            ->assertSee(__('reader_auth.password_guidance', locale: 'ar'), false)
            ->assertSee('aria-describedby="reader-reset-password-password-guidance"', false);

        $reader = User::factory()->create();

        $this->actingAs($reader)
            ->get('/en/reader/account')
            ->assertOk()
            ->assertSee(__('reader_auth.password_guidance', locale: 'en'), false)
            ->assertSee('aria-describedby="reader-password-new-password-guidance"', false);
    }

    private function assertValidationSummary(TestResponse $response, string $summaryId, string $fieldId, string $locale): void
    {
        $response
            ->assertOk()
            ->assertSee('id="'.$summaryId.'"', false)
            ->assertSee('id="'.$summaryId.'-title" tabindex="-1" x-data x-init="$nextTick(() => $el.focus())"', false)
            ->assertSee(__('reader_auth.error_summary_title', locale: $locale), false)
            ->assertSee('href="#'.$fieldId.'"', false)
            ->assertSee('id="'.$fieldId.'"', false)
            ->assertSee('aria-invalid="true"', false);
    }
}
