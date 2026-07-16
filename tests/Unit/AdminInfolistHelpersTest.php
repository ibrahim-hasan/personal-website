<?php

namespace Tests\Unit;

use App\Filament\Components\TranslatableInfolistTabs;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInfolistHelpersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_yes_no_label_returns_translated_values(): void
    {
        app()->setLocale('en');

        $this->assertSame('Yes', admin_yes_no_label(true));
        $this->assertSame('No', admin_yes_no_label(false));

        app()->setLocale('ar');

        $this->assertSame('نعم', admin_yes_no_label(true));
        $this->assertSame('لا', admin_yes_no_label(false));
    }

    public function test_localized_model_attribute_returns_current_locale_with_fallback(): void
    {
        $project = Project::factory()->create([
            'title' => ['ar' => 'عنوان عربي', 'en' => 'English Title'],
        ]);

        app()->setLocale('ar');
        $this->assertSame('عنوان عربي', localized_model_attribute($project, 'title'));

        app()->setLocale('en');
        $this->assertSame('English Title', localized_model_attribute($project, 'title'));
    }

    public function test_translatable_infolist_tabs_builds_tabs_component(): void
    {
        $tabs = TranslatableInfolistTabs::make([
            'title' => [],
            'description' => ['columnSpanFull' => true],
        ]);

        $this->assertSame(__('admin.sections.translations'), $tabs->getLabel());
        $this->assertStringContainsString('fi-tabs-translatable', $tabs->getExtraAttributes()['class'] ?? '');
    }
}
