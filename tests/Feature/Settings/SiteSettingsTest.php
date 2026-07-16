<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\ManageSiteSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_website_content_setting(): void
    {
        $user = $this->settingsAdministrator();

        Livewire::actingAs($user)
            ->test(ManageSiteSettings::class)
            ->set('data.about_biography_ar', '<p>ملخص عربي محدث</p>')
            ->set('data.about_biography_en', '<p>Updated English profile summary</p>')
            ->call('saveWebsiteContent')
            ->assertNotified();

        $stored = json_decode((string) Setting::getValue('about_biography', 'website_content'), true);

        $this->assertSame('ملخص عربي محدث', $stored['ar']);
        $this->assertSame('Updated English profile summary', $stored['en']);
    }

    public function test_biography_setting_migration_preserves_existing_profile_content(): void
    {
        $biography = json_encode([
            'ar' => '<p>نبذة محفوظة</p>',
            'en' => '<p>Preserved biography</p>',
        ], JSON_UNESCAPED_UNICODE);

        Setting::setValue('about_doctor_description', $biography, 'website_content');

        $migration = require database_path('migrations/2026_07_15_230009_rename_about_doctor_description_setting.php');
        $migration->up();

        $this->assertNull(Setting::getValue('about_doctor_description', 'website_content'));
        $this->assertSame($biography, Setting::getValue('about_biography', 'website_content'));
    }

    public function test_biography_migration_keeps_an_existing_current_setting_when_both_keys_exist(): void
    {
        Setting::setValue('about_doctor_description', ['en' => 'Legacy profile'], 'website_content');
        Setting::setValue('about_biography', ['en' => 'Current profile'], 'website_content');

        $migration = require database_path('migrations/2026_07_15_230009_rename_about_doctor_description_setting.php');
        $migration->up();

        $this->assertNull(Setting::getValue('about_doctor_description', 'website_content'));
        $this->assertSame(
            ['en' => 'Current profile'],
            json_decode((string) Setting::getValue('about_biography', 'website_content'), true),
        );
    }

    public function test_ai_settings_keep_credentials_out_of_livewire_state_and_browser_markup(): void
    {
        $user = $this->settingsAdministrator();
        $legacySecret = 'legacy-openai-secret-that-must-not-render';

        Setting::setValue('openai_api_key', $legacySecret, 'ai');
        Setting::setValue('openai_custom_url', 'https://legacy-provider.example/v1', 'ai');
        Setting::setValue('openai_model', 'unsupported-provider/model', 'ai');
        config()->set('ai.providers.openai.key', 'server-openai-secret-that-must-not-render');

        $component = Livewire::actingAs($user)
            ->test(ManageSiteSettings::class)
            ->assertDontSee($legacySecret)
            ->assertDontSee('server-openai-secret-that-must-not-render')
            ->assertDontSee('legacy-provider.example')
            ->assertDontSee('OpenRouter')
            ->assertSet('data.openai_model', 'gpt-4o-mini');

        $this->assertArrayNotHasKey('openai_api_key', $component->get('data'));
        $this->assertArrayNotHasKey('openai_custom_url', $component->get('data'));
        $this->assertArrayNotHasKey('openai_provider', $component->get('data'));
    }

    public function test_admin_can_save_only_supported_openai_ai_settings(): void
    {
        $user = $this->settingsAdministrator();

        Livewire::actingAs($user)
            ->test(ManageSiteSettings::class)
            ->set('data.openai_model', 'gpt-4.1-mini')
            ->set('data.ai_seo_enabled', true)
            ->call('saveAi')
            ->assertNotified();

        $this->assertSame('gpt-4.1-mini', Setting::getValue('openai_model', 'ai'));
        $this->assertSame('1', Setting::getValue('ai_seo_enabled', 'ai'));
        $this->assertNull(Setting::getValue('openai_provider', 'ai'));
        $this->assertNull(Setting::getValue('openai_custom_url', 'ai'));
        $this->assertNull(Setting::getValue('openai_api_key', 'ai'));
    }

    public function test_ai_cleanup_migration_removes_legacy_provider_and_secret_rows(): void
    {
        foreach ([
            'ai_seo_expert_enabled',
            'openai_api_key',
            'openai_base_url',
            'openai_custom_url',
            'openai_provider',
        ] as $key) {
            Setting::setValue($key, 'legacy-value', 'ai');
        }

        Setting::setValue('openai_model', 'gpt-4o-mini', 'ai');

        $migration = require database_path('migrations/2026_07_15_224229_remove_obsolete_ai_provider_settings.php');
        $migration->up();

        $this->assertSame('gpt-4o-mini', Setting::getValue('openai_model', 'ai'));

        foreach ([
            'ai_seo_expert_enabled',
            'openai_api_key',
            'openai_base_url',
            'openai_custom_url',
            'openai_provider',
        ] as $key) {
            $this->assertNull(Setting::getValue($key, 'ai'));
        }
    }

    public function test_ai_cleanup_migration_normalizes_an_unapproved_stored_model(): void
    {
        Setting::setValue('openai_model', 'external-provider/unsafe-model', 'ai');

        $migration = require database_path('migrations/2026_07_15_224229_remove_obsolete_ai_provider_settings.php');
        $migration->up();

        $this->assertSame('gpt-4o-mini', Setting::getValue('openai_model', 'ai'));
    }

    public function test_obsolete_personal_site_settings_migration_removes_dead_rows_only(): void
    {
        Setting::setValue('default_seo_title', ['en' => 'Unused title'], 'seo');
        Setting::setValue('default_seo_description', ['en' => 'Unused description'], 'seo');
        Setting::setValue('strategic_consultation_url', 'https://example.com/intake', 'contact');
        Setting::setValue('contact_email', 'hello@example.com', 'contact');

        $migration = require database_path('migrations/2026_07_15_235240_remove_obsolete_personal_site_settings.php');
        $migration->up();

        $this->assertNull(Setting::getValue('default_seo_title', 'seo'));
        $this->assertNull(Setting::getValue('default_seo_description', 'seo'));
        $this->assertNull(Setting::getValue('strategic_consultation_url', 'contact'));
        $this->assertSame('hello@example.com', Setting::getValue('contact_email', 'contact'));
    }

    public function test_user_without_settings_permission_cannot_write_settings(): void
    {
        $user = $this->settingsAdministrator();
        $component = Livewire::actingAs($user)
            ->test(ManageSiteSettings::class)
            ->set('data.contact_email', 'unauthorized@example.com');

        $user->revokePermissionTo('update settings');

        $component
            ->call('saveContact')
            ->assertForbidden();

        $this->assertNull(Setting::getValue('contact_email', 'contact'));
    }

    public function test_admin_cannot_save_a_javascript_social_url(): void
    {
        $user = $this->settingsAdministrator();

        Livewire::actingAs($user)
            ->test(ManageSiteSettings::class)
            ->set('data.social_linkedin', 'javascript:alert(document.domain)')
            ->call('saveSocial')
            ->assertHasErrors(['data.social_linkedin']);

        $this->assertNull(Setting::getValue('social_linkedin', 'social'));
    }

    public function test_legacy_unsafe_social_url_is_not_rendered_publicly(): void
    {
        Setting::setValue('social_linkedin', 'javascript:alert(document.domain)', 'social');

        $this->get(localized_route('home', locale: 'en'))
            ->assertOk()
            ->assertDontSee('javascript:alert', false);
    }

    private function settingsAdministrator(): User
    {
        $permission = Permission::findOrCreate('update settings');
        $user = User::factory()->create();
        $user->givePermissionTo($permission);

        return $user;
    }
}
