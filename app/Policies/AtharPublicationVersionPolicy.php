<?php

namespace App\Policies;

use App\Models\AtharPublicationVersion;
use App\Models\User;

class AtharPublicationVersionPolicy
{
    public function view(User $user, AtharPublicationVersion $version): bool
    {
        return $user->can('review athar_publications');
    }

    public function update(User $user, AtharPublicationVersion $version): bool
    {
        return $user->can('review athar_publications');
    }

    public function publish(User $user, AtharPublicationVersion $version): bool
    {
        return $user->can('publish athar_publications');
    }
}
