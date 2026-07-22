<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ManageOAuthClients;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthClientManagementLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_the_oauth_client_management_page_is_fully_localized_in_arabic(): void
    {
        app()->setLocale('ar');
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->bootAdminPanel();

        $this->actingAs($superAdmin)
            ->get(ManageOAuthClients::getUrl())
            ->assertOk()
            ->assertSee('عملاء OAuth')
            ->assertSee('إنشاء عميل OAuth')
            ->assertSee('قراءة المقالات والمسودات')
            ->assertSee('أحدث أحداث تدقيق واجهة API التحريرية')
            ->assertDontSee('O Auth Clients');
    }

    public function test_scope_labels_follow_the_active_locale(): void
    {
        app()->setLocale('en');
        $this->assertSame('Read articles and drafts', ManageOAuthClients::scopeLabels()['articles:read']);

        app()->setLocale('ar');
        $this->assertSame('قراءة المقالات والمسودات', ManageOAuthClients::scopeLabels()['articles:read']);
    }

    private function bootAdminPanel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }
}
