<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\IntellectualLibrary;
use App\Models\User;

class IntellectualLibraryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any intellectual_libraries');
    }

    public function view(User $user, IntellectualLibrary $intellectualLibrary): bool
    {
        return $user->hasPermissionTo('view intellectual_libraries');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create intellectual_libraries');
    }

    public function update(User $user, IntellectualLibrary $intellectualLibrary): bool
    {
        return $user->hasPermissionTo('update intellectual_libraries');
    }

    public function delete(User $user, IntellectualLibrary $intellectualLibrary): bool
    {
        return $user->hasPermissionTo('delete intellectual_libraries');
    }

    public function restore(User $user, IntellectualLibrary $intellectualLibrary): bool
    {
        return $user->hasPermissionTo('restore intellectual_libraries');
    }

    public function forceDelete(User $user, IntellectualLibrary $intellectualLibrary): bool
    {
        return $user->hasPermissionTo('force_delete intellectual_libraries');
    }
}
