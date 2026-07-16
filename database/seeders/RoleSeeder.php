<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(
            [
                'name' => 'super_admin',
                'guard_name' => 'web',
            ],
            [
                'display_name' => ['en' => 'Super Admin', 'ar' => 'مدير عام'],
            ]
        );
        $admin = Role::firstOrCreate(
            [
                'name' => 'admin',
                'guard_name' => 'web',
            ],
            [
                'display_name' => ['en' => 'Admin', 'ar' => 'مدير'],
            ]
        );
        $editor = Role::firstOrCreate(
            [
                'name' => 'editor',
                'guard_name' => 'web',
            ],
            [
                'display_name' => ['en' => 'Editor', 'ar' => 'محرر'],
            ]
        );

        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();

        if ($superAdmin->wasRecentlyCreated) {
            $superAdmin->syncPermissions($allPermissions);
        }

        $adminPermissions = collect($allPermissions)
            ->filter(fn (string $permission): bool => ! str_contains($permission, 'force_delete'))
            ->values()
            ->toArray();
        if ($admin->wasRecentlyCreated) {
            $admin->syncPermissions($adminPermissions);
        }

        $editorResources = ['services', 'projects', 'articles', 'comments', 'contact_inquiries'];
        $editorActions = ['view_any', 'view', 'create', 'update'];
        $editorViewOnlyResources = ['settings'];
        $editorViewOnlyActions = ['view_any', 'view'];

        $editorPermissions = collect($editorResources)
            ->crossJoin($editorActions)
            ->map(fn (array $pair): string => "{$pair[1]} {$pair[0]}")
            ->merge(
                collect($editorViewOnlyResources)
                    ->crossJoin($editorViewOnlyActions)
                    ->map(fn (array $pair): string => "{$pair[1]} {$pair[0]}")
            )
            ->filter(fn (string $permission): bool => in_array($permission, $allPermissions, true))
            ->toArray();
        if ($editor->wasRecentlyCreated) {
            $editor->syncPermissions($editorPermissions);
        }
    }
}
