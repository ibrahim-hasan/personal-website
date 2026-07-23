<?php

namespace App\Actions\Athar;

use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SyncAtharPermissions
{
    /** @return list<string> */
    public function handle(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['view_any athar_invitations', 'view athar_invitations', 'create athar_invitations', 'update athar_invitations', 'send athar_invitations', 'revoke athar_invitations', 'view_private athar_contributions', 'review athar_publications', 'publish athar_publications', 'manage athar_retention'];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $all = Permission::query()->whereIn('name', $permissions)->pluck('name')->all();
        Role::query()->whereIn('name', ['super_admin', 'admin'])->each(fn (Role $role): mixed => $role->givePermissionTo($all));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $all;
    }
}
