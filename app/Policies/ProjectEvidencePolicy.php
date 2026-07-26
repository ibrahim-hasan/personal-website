<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProjectEvidence;
use App\Models\User;

class ProjectEvidencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_any projects');
    }

    public function view(User $user, ProjectEvidence $projectEvidence): bool
    {
        return $user->hasPermissionTo('view projects');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('update projects');
    }

    public function update(User $user, ProjectEvidence $projectEvidence): bool
    {
        return $user->hasPermissionTo('update projects');
    }

    public function delete(User $user, ProjectEvidence $projectEvidence): bool
    {
        return $user->hasPermissionTo('delete projects');
    }

    public function verify(User $user, ProjectEvidence $projectEvidence): bool
    {
        return $this->mayReview($user);
    }

    public function approve(User $user, ProjectEvidence $projectEvidence): bool
    {
        return $this->mayReview($user);
    }

    public function reject(User $user, ProjectEvidence $projectEvidence): bool
    {
        return $this->mayReview($user);
    }

    public function revoke(User $user, ProjectEvidence $projectEvidence): bool
    {
        return $this->mayReview($user);
    }

    public function setPublicVisibility(User $user, ProjectEvidence $projectEvidence): bool
    {
        return $this->mayReview($user);
    }

    private function mayReview(User $user): bool
    {
        return $user->hasPermissionTo('approve project_evidence');
    }
}
