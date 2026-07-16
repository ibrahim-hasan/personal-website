<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\AdminContentStats;
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
            ->assertOk()
            ->assertSee('data-admin-tools', escape: false)
            ->assertSee(__('admin.navigation.utilities'));
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

    public function test_content_stats_widget_balances_each_permitted_stat_count(): void
    {
        $widget = new class extends AdminContentStats
        {
            /**
             * @return int|array<string, int>
             */
            public function columnsFor(int $statCount): int|array
            {
                return $this->columnsForStatCount($statCount);
            }
        };

        $this->assertSame(1, $widget->columnsFor(1));
        $this->assertSame(2, $widget->columnsFor(2)['@md']);
        $this->assertSame(3, $widget->columnsFor(3)['@3xl']);
        $this->assertSame(4, $widget->columnsFor(4)['@4xl']);
        $this->assertSame(5, $widget->columnsFor(5)['@5xl']);
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
