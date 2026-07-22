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

        $resources = ['services', 'projects', 'articles', 'comments', 'contact_inquiries', 'settings', 'users', 'roles'];
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "{$action} {$resource}", 'guard_name' => 'web'],
                );
            }
        }

        Permission::firstOrCreate(['name' => 'publish articles', 'guard_name' => 'web']);
    }
}
