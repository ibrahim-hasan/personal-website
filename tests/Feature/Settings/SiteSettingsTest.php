<?php

namespace Tests\Feature\Settings;

use App\Filament\Pages\ManageSiteSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_website_content_setting(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ManageSiteSettings::class)
            ->set('data.about_doctor_description_ar', '<p>ملخص عربي محدث</p>')
            ->set('data.about_doctor_description_en', '<p>Updated English profile summary</p>')
            ->call('saveWebsiteContent')
            ->assertNotified();

        $stored = json_decode((string) Setting::getValue('about_doctor_description', 'website_content'), true);

        $this->assertSame('<p>ملخص عربي محدث</p>', $stored['ar']);
        $this->assertSame('<p>Updated English profile summary</p>', $stored['en']);
    }

    public function test_admin_can_save_project_start_url(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ManageSiteSettings::class)
            ->set('data.strategic_consultation_url', 'mailto:hello@ibrahimhasan.dev')
            ->call('saveContact')
            ->assertNotified();

        $this->assertSame(
            'mailto:hello@ibrahimhasan.dev',
            Setting::getValue('strategic_consultation_url', 'contact'),
        );
    }
}
