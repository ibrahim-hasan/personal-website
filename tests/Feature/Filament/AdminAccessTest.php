<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\AdminContentStats;
use App\Filament\Widgets\AdminPageHealth;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_admin_dashboard_is_accessible_for_user_with_panel_permission(): void
    {
        $user = $this->makeUserWithPermissions(['view_any settings']);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_content_stats_widget_visibility_depends_on_permissions(): void
    {
        $allowedUser = $this->makeUserWithPermissions(['view_any services']);
        $blockedUser = User::factory()->create();

        $this->actingAs($allowedUser);
        $this->assertTrue(AdminContentStats::canView());

        $this->actingAs($blockedUser);
        $this->assertFalse(AdminContentStats::canView());
    }

    public function test_page_health_widget_visibility_depends_on_permissions(): void
    {
        $allowedUser = $this->makeUserWithPermissions(['view_any settings']);
        $blockedUser = User::factory()->create();

        $this->actingAs($allowedUser);
        $this->assertTrue(AdminPageHealth::canView());

        $this->actingAs($blockedUser);
        $this->assertFalse(AdminPageHealth::canView());
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function makeUserWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => fake()->unique()->word(), 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
