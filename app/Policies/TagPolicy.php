<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Spatie\Tags\Tag;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any tags');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('view tags');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create tags');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('update tags');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('delete tags');
    }

    public function restore(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('restore tags');
    }

    public function forceDelete(User $user, Tag $tag): bool
    {
        return $user->hasPermissionTo('force_delete tags');
    }
}
