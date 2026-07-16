<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any projects');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('view projects');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create projects');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('update projects');
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('delete projects');
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('restore projects');
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('force_delete projects');
    }
}
