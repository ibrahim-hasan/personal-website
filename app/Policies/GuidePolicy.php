<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Guide;
use App\Models\User;

class GuidePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any guides');
    }

    public function view(User $user, Guide $guide): bool
    {
        return $user->hasPermissionTo('view guides');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create guides');
    }

    public function update(User $user, Guide $guide): bool
    {
        return $user->hasPermissionTo('update guides');
    }

    public function delete(User $user, Guide $guide): bool
    {
        return $user->hasPermissionTo('delete guides');
    }

    public function restore(User $user, Guide $guide): bool
    {
        return $user->hasPermissionTo('restore guides');
    }

    public function forceDelete(User $user, Guide $guide): bool
    {
        return $user->hasPermissionTo('force_delete guides');
    }
}
