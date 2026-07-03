<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $resources = ['services', 'intellectual_libraries', 'authors', 'guides', 'settings', 'users', 'roles', 'tags'];
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "{$action} {$resource}", 'guard_name' => 'web'],
                );
            }
        }
    }
}
