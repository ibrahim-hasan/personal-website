<?php

namespace Tests\Feature\Newsletter;

use App\Mail\NewsletterWelcomeMail;
use App\Models\Newsletter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterUnsubscribeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_mail_generates_unsubscribe_token_and_link_when_missing(): void
    {
        $newsletter = Newsletter::create([
            'email' => 'newsletter@example.com',
            'is_disabled' => false,
            'unsubscribe_token' => null,
        ]);

        $html = (new NewsletterWelcomeMail($newsletter))->render();

        $newsletter->refresh();

        $this->assertNotNull($newsletter->unsubscribe_token);
        $this->assertStringContainsString('newsletter/unsubscribe?email='.rawurlencode($newsletter->email), $html);
        $this->assertStringContainsString($newsletter->unsubscribe_token, $html);
    }

    public function test_unsubscribe_route_disables_newsletter_for_valid_link(): void
    {
        $newsletter = Newsletter::create([
            'email' => 'newsletter@example.com',
            'is_disabled' => false,
            'unsubscribe_token' => str_repeat('a', 64),
        ]);

        $this->get(route('newsletter.unsubscribe', [
            'email' => $newsletter->email,
            'token' => $newsletter->unsubscribe_token,
        ]))->assertOk();

        $this->assertTrue($newsletter->fresh()->is_disabled);
    }

    public function test_unsubscribe_route_rejects_invalid_link(): void
    {
        $newsletter = Newsletter::create([
            'email' => 'newsletter@example.com',
            'is_disabled' => false,
            'unsubscribe_token' => str_repeat('a', 64),
        ]);

        $this->get(route('newsletter.unsubscribe', [
            'email' => $newsletter->email,
            'token' => str_repeat('b', 64),
        ]))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error', __('newsletter.unsubscribe_invalid_link'));

        $this->assertFalse($newsletter->fresh()->is_disabled);
    }
}
