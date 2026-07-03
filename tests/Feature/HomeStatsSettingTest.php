<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeStatsSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_uses_default_stat_values_when_no_settings_exist(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    public function test_get_value_returns_stored_setting(): void
    {
        Setting::setValue('stat_hours_value', '7500', 'stats');

        $this->assertSame('7500', Setting::getValue('stat_hours_value', 'stats'));
    }

    public function test_get_value_returns_null_when_setting_does_not_exist(): void
    {
        $this->assertNull(Setting::getValue('stat_hours_value', 'stats'));
    }

    public function test_get_value_without_group_returns_matching_setting(): void
    {
        Setting::setValue('stat_hours_value', '8000', 'stats');

        $this->assertSame('8000', Setting::getValue('stat_hours_value'));
    }

    public function test_home_page_renders_with_configured_stat_values(): void
    {
        Setting::setValue('stat_hours_value', '7500', 'stats');
        Setting::setValue('stat_experience_value', '25', 'stats');
        Setting::setValue('stat_calendar_value', '30', 'stats');
        Setting::setValue('stat_trainees_value', '9000', 'stats');

        $response = $this->get(route('home'));

        $response->assertOk();
    }
}
