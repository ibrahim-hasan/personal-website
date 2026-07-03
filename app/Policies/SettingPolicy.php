<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any settings');
    }

    public function view(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('view settings');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create settings');
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('update settings');
    }

    public function delete(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('delete settings');
    }

    public function restore(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('restore settings');
    }

    public function forceDelete(User $user, Setting $setting): bool
    {
        return $user->hasPermissionTo('force_delete settings');
    }
}
