<?php

namespace Tests\Feature\Auth;

use App\Livewire\Website\ArticleCommunity;
use App\Models\Article;
use App\Models\ArticleBookmark;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReaderTermsAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stale_reader_is_redirected_to_accept_the_current_terms_in_the_active_locale(): void
    {
        $reader = User::factory()->create([
            'terms_version' => '2026-07-01',
        ]);

        $this->actingAs($reader)
            ->get('/en/reader/account')
            ->assertRedirect('/en/reader/terms/accept')
            ->assertSessionHas('url.intended', url('/en/reader/account'));
    }

    public function test_accepting_current_terms_records_the_server_owned_version_and_returns_to_the_intended_page(): void
    {
        $reader = User::factory()->create([
            'terms_version' => '2026-07-01',
            'terms_accepted_at' => now()->subDay(),
        ]);

        $this->actingAs($reader)
            ->withSession(['url.intended' => url('/en/reader/library')])
            ->post('/en/reader/terms/accept', ['terms_accepted' => '1'])
            ->assertRedirect('/en/reader/library');

        $reader->refresh();

        $this->assertTrue($reader->hasAcceptedCurrentTerms());
        $this->assertSame(config('legal.terms_version'), $reader->terms_version);
        $this->assertNotNull($reader->terms_accepted_at);
    }

    public function test_accepting_current_terms_requires_an_explicit_terms_checkbox(): void
    {
        $reader = User::factory()->create(['terms_version' => '2026-07-01']);

        $this->actingAs($reader)
            ->post('/reader/terms/accept')
            ->assertSessionHasErrors('terms_accepted');

        $this->assertFalse($reader->fresh()->hasAcceptedCurrentTerms());
    }

    public function test_stale_terms_prevent_reader_community_mutations_and_offer_the_acceptance_route(): void
    {
        app()->setLocale('en');

        $article = Article::factory()->create();
        $reader = User::factory()->create(['terms_version' => '2026-07-01']);

        Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key])
            ->assertSee('Review updated Terms')
            ->call('toggleBookmark')
            ->assertHasErrors('auth');

        $this->assertDatabaseCount(ArticleBookmark::class, 0);
    }

    public function test_logout_and_account_deletion_routes_remain_available_for_a_stale_reader(): void
    {
        $reader = User::factory()->create([
            'terms_version' => '2026-07-01',
            'password' => 'reader-password',
        ]);

        $this->actingAs($reader)
            ->post('/en/reader/logout')
            ->assertRedirect('/en/writing');

        $this->assertGuest();

        $this->actingAs($reader)
            ->delete('/en/reader/account', [
                'current_password' => 'incorrect-password',
                'acknowledgement' => '1',
            ])
            ->assertSessionHasErrorsIn('accountDeletion', ['current_password']);
    }
}
