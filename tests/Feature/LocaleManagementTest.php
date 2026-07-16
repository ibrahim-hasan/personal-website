<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\Interfaces\LocalizedUrlRoutable;
use Tests\TestCase;

class LocaleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_url_locale_overrides_authenticated_user_preference(): void
    {
        $user = User::factory()->create([
            'locale_preference' => 'ar',
        ]);

        $response = $this
            ->withSession(['locale' => 'ar'])
            ->actingAs($user)
            ->get('/en');

        $response->assertOk();
        $response->assertSee('lang="en"', false);
        $response->assertSee('dir="ltr"', false);
    }

    public function test_guest_uses_arabic_default_without_locale_prefix(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('lang="ar"', false);
        $response->assertSee('dir="rtl"', false);
    }

    public function test_guest_uses_english_when_english_prefix_is_present(): void
    {
        $response = $this->get('/en');

        $response->assertOk();
        $response->assertSee('lang="en"', false);
        $response->assertSee('dir="ltr"', false);
    }

    public function test_unprefixed_public_url_returns_to_arabic_after_english_visit(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSessionHas('locale', 'en');

        $this->get('/')
            ->assertOk()
            ->assertSee('lang="ar"', false)
            ->assertSee('dir="rtl"', false)
            ->assertSessionHas('locale', 'ar');
    }

    public function test_language_switch_links_point_to_locale_specific_current_page(): void
    {
        $this->get('/en/services')
            ->assertOk()
            ->assertSee('href="'.localized_route('services', locale: 'ar').'"', false);

        $this->get('/services')
            ->assertOk()
            ->assertSee('href="'.localized_route('services', locale: 'en').'"', false);
    }

    public function test_invalid_session_locale_falls_back_to_default_locale(): void
    {
        $defaultLocale = default_locale();
        $defaultDirection = is_rtl($defaultLocale) ? 'rtl' : 'ltr';

        $response = $this
            ->withSession(['locale' => 'zz'])
            ->get('/');

        $response->assertOk();
        $response->assertSee('lang="'.$defaultLocale.'"', false);
        $response->assertSee('dir="'.$defaultDirection.'"', false);
    }

    public function test_valid_locale_switch_updates_session_for_guest_and_redirects_back(): void
    {
        $response = $this
            ->from(route('home'))
            ->post(route('lang.switch', ['locale' => 'en']));

        $response->assertRedirect('/en');
        $response->assertSessionHas('locale', 'en');
    }

    public function test_valid_locale_switch_updates_authenticated_user_preference(): void
    {
        $user = User::factory()->create([
            'locale_preference' => 'ar',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('lang.switch', ['locale' => 'en']));

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'locale_preference' => 'en',
        ]);
    }

    public function test_admin_locale_switch_keeps_the_unlocalized_admin_url(): void
    {
        $response = $this
            ->from('/admin/articles')
            ->post(route('lang.switch', ['locale' => 'en']));

        $response->assertRedirect('/admin/articles');
        $response->assertSessionHas('locale', 'en');
    }

    public function test_localized_route_generates_urls_for_both_locales(): void
    {
        $this->assertSame(url('/').'/', localized_route('home', locale: 'ar'));
        $this->assertSame(url('/en'), localized_route('home', locale: 'en'));
        $this->assertSame('/en/services?source=test', localized_route(
            'services',
            ['source' => 'test'],
            absolute: false,
            locale: 'en',
        ));
    }

    public function test_localized_route_uses_the_models_localized_route_key(): void
    {
        $article = new class implements LocalizedUrlRoutable
        {
            public function getLocalizedRouteKey($locale): string
            {
                return $locale === 'en' ? 'english-slug' : 'arabic-slug';
            }
        };

        $this->assertSame(
            url('/en/writing/english-slug'),
            localized_route('writing.show', ['article' => $article], locale: 'en'),
        );
        $this->assertSame(
            url('/writing/arabic-slug'),
            localized_route('writing.show', ['article' => $article], locale: 'ar'),
        );
    }

    public function test_invalid_locale_switch_is_rejected_without_session_or_user_mutation(): void
    {
        $user = User::factory()->create([
            'locale_preference' => 'ar',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('home'))
            ->post(route('lang.switch', ['locale' => 'zz']));

        $response->assertRedirect(route('home'));
        $response->assertSessionMissing('locale');
        $response->assertSessionHas('error', __('Unsupported language.'));

        $user->refresh();
        $this->assertSame('ar', $user->locale_preference);
    }
}
