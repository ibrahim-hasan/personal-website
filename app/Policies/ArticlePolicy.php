<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('view_any articles');
    }

    public function view(User $user, Article $article): bool
    {
        return $user->hasRole('super_admin') || $user->can('view articles');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('create articles');
    }

    public function update(User $user, Article $article): bool
    {
        return $user->hasRole('super_admin') || $user->can('update articles');
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->hasRole('super_admin') || $user->can('publish articles');
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->hasRole('super_admin') || $user->can('delete articles');
    }

    public function restore(User $user, Article $article): bool
    {
        return $user->hasRole('super_admin') || $user->can('restore articles');
    }

    public function forceDelete(User $user, Article $article): bool
    {
        return false;
    }
}
