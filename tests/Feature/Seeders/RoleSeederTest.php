<?php

namespace Tests\Feature\Seeders;

use App\Models\Role;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RoleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_initializes_missing_roles_without_overwriting_existing_permission_customizations(): void
    {
        $this->seed(PermissionSeeder::class);

        $customPermission = Permission::query()
            ->where('name', 'view_any articles')
            ->firstOrFail();

        $admin = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
            'display_name' => ['en' => 'Customized Admin', 'ar' => 'مدير مخصص'],
        ]);
        $admin->syncPermissions([$customPermission]);

        $this->seed(RoleSeeder::class);

        $admin->refresh();
        $this->assertSame(['view_any articles', 'publish articles'], $admin->permissions()->pluck('name')->all());

        $superAdmin = Role::query()->where('name', 'super_admin')->firstOrFail();
        $editor = Role::query()->where('name', 'editor')->firstOrFail();

        $this->assertGreaterThan(0, $superAdmin->permissions()->count());
        $this->assertTrue($editor->hasPermissionTo('view_any articles'));
        $this->assertTrue($editor->hasPermissionTo('view settings'));
        $this->assertFalse($editor->hasPermissionTo('delete articles'));

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $admin->refresh();
        $this->assertSame(['view_any articles', 'publish articles'], $admin->permissions()->pluck('name')->all());
        $this->assertSame(3, Role::query()->whereIn('name', ['super_admin', 'admin', 'editor'])->count());
    }

    public function test_it_preserves_custom_permissions_for_every_existing_built_in_role(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $customPermissions = [
            'super_admin' => 'view_any articles',
            'admin' => 'view comments',
            'editor' => 'update projects',
        ];

        foreach ($customPermissions as $roleName => $permissionName) {
            Role::query()
                ->where('name', $roleName)
                ->firstOrFail()
                ->syncPermissions([$permissionName]);
        }

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        foreach ($customPermissions as $roleName => $permissionName) {
            $permissionNames = Role::query()
                ->where('name', $roleName)
                ->firstOrFail()
                ->permissions()
                ->pluck('name')
                ->all();

            $this->assertSame(
                $roleName === 'admin' ? [$permissionName, 'publish articles'] : [$permissionName],
                $permissionNames,
            );
        }
    }
}
