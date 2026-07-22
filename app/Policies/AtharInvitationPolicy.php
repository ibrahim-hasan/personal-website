<?php

namespace App\Policies;

use App\Models\AtharInvitation;
use App\Models\User;

class AtharInvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any athar_invitations');
    }

    public function view(User $user, AtharInvitation $invitation): bool
    {
        return $user->can('view athar_invitations');
    }

    public function create(User $user): bool
    {
        return $user->can('create athar_invitations');
    }

    public function update(User $user, AtharInvitation $invitation): bool
    {
        return $user->can('review athar_publications');
    }

    public function delete(User $user, AtharInvitation $invitation): bool
    {
        return $user->can('manage athar_retention');
    }
}
