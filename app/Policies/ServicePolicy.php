<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any services');
    }

    public function view(User $user, Service $service): bool
    {
        return $user->hasPermissionTo('view services');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create services');
    }

    public function update(User $user, Service $service): bool
    {
        return $user->hasPermissionTo('update services');
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->hasPermissionTo('delete services');
    }

    public function restore(User $user, Service $service): bool
    {
        return $user->hasPermissionTo('restore services');
    }

    public function forceDelete(User $user, Service $service): bool
    {
        return $user->hasPermissionTo('force_delete services');
    }
}
