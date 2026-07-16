<?php

namespace App\Policies;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin') || $user->can('view_any comments');
    }

    public function view(User $user, Comment $comment): bool
    {
        return $comment->status === CommentStatus::Approved
            || $comment->user_id === $user->getKey()
            || $user->hasRole('super_admin')
            || $user->can('view comments');
    }

    public function create(User $user): bool
    {
        return (bool) $user->is_active && $user->hasVerifiedEmail();
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('update comments')
            || ($comment->user_id === $user->getKey()
                && $comment->status === CommentStatus::Pending
                && $comment->created_at?->isAfter(now()->subMinutes(15)));
    }

    public function delete(User $user, Comment $comment): bool
    {
        if ($user->hasRole('super_admin') || $user->can('delete comments')) {
            return true;
        }

        if (! $user->is_active || $comment->user_id !== $user->getKey()) {
            return false;
        }

        return $comment->parent_id !== null || ! $comment->replies()->exists();
    }

    public function restore(User $user, Comment $comment): bool
    {
        return $user->hasRole('super_admin') || $user->can('restore comments');
    }

    public function forceDelete(User $user, Comment $comment): bool
    {
        return $user->hasRole('super_admin') || $user->can('force_delete comments');
    }
}
