<?php

namespace App\Policies;

use App\Models\AtharContribution;
use App\Models\User;

class AtharContributionPolicy
{
    public function view(User $user, AtharContribution $contribution): bool
    {
        return $user->can('view_private athar_contributions');
    }

    public function update(User $user, AtharContribution $contribution): bool
    {
        return false;
    }
}
