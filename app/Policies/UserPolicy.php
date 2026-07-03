<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('view users');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('update users');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('delete users');
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasPermissionTo('restore users');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasPermissionTo('force_delete users');
    }
}
