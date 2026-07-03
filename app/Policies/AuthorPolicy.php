<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Author;
use App\Models\User;

class AuthorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any authors');
    }

    public function view(User $user, Author $author): bool
    {
        return $user->hasPermissionTo('view authors');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create authors');
    }

    public function update(User $user, Author $author): bool
    {
        return $user->hasPermissionTo('update authors');
    }

    public function delete(User $user, Author $author): bool
    {
        return $user->hasPermissionTo('delete authors')
            && ! $author->intellectualLibraries()->exists();
    }

    public function restore(User $user, Author $author): bool
    {
        return $user->hasPermissionTo('restore authors');
    }

    public function forceDelete(User $user, Author $author): bool
    {
        return $user->hasPermissionTo('force_delete authors');
    }
}
