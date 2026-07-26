<?php

declare(strict_types=1);

namespace App\Actions\Projects;

use App\Enums\ProjectEvidenceState;
use App\Models\ProjectEvidence;
use App\Models\User;
use App\Services\Projects\ProjectEvidenceWorkflowValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class TransitionProjectEvidence
{
    public function __construct(private readonly ProjectEvidenceWorkflowValidator $validator) {}

    public function draft(User $actor, ProjectEvidence $evidence): ProjectEvidence
    {
        Gate::forUser($actor)->authorize('update', $evidence);

        return DB::transaction(function () use ($evidence): ProjectEvidence {
            $lockedEvidence = $this->lockedEvidence($evidence);
            $this->assertState($lockedEvidence, [ProjectEvidenceState::Draft, ProjectEvidenceState::Rejected]);

            $lockedEvidence->forceFill([
                'state' => ProjectEvidenceState::Draft,
                'is_public' => false,
                'verified_by' => null,
                'verified_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'revoked_at' => null,
            ])->save();

            return $lockedEvidence->refresh();
        });
    }

    public function verify(User $actor, ProjectEvidence $evidence): ProjectEvidence
    {
        Gate::forUser($actor)->authorize('verify', $evidence);

        return DB::transaction(function () use ($actor, $evidence): ProjectEvidence {
            $lockedEvidence = $this->lockedEvidence($evidence);
            $this->assertState($lockedEvidence, [ProjectEvidenceState::Draft]);
            $this->validator->assertReadyForVerification($lockedEvidence);

            $lockedEvidence->forceFill([
                'state' => ProjectEvidenceState::Verified,
                'is_public' => false,
                'verified_by' => $actor->getKey(),
                'verified_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'revoked_at' => null,
            ])->save();

            return $lockedEvidence->refresh();
        });
    }

    public function approve(User $actor, ProjectEvidence $evidence): ProjectEvidence
    {
        Gate::forUser($actor)->authorize('approve', $evidence);

        return DB::transaction(function () use ($actor, $evidence): ProjectEvidence {
            $lockedEvidence = $this->lockedEvidence($evidence);
            $this->assertState($lockedEvidence, [ProjectEvidenceState::Verified]);
            $this->validator->assertReadyForApproval($lockedEvidence);

            $lockedEvidence->forceFill([
                'state' => ProjectEvidenceState::Approved,
                'is_public' => false,
                'approved_by' => $actor->getKey(),
                'approved_at' => now(),
                'revoked_at' => null,
            ])->save();

            return $lockedEvidence->refresh();
        });
    }

    public function reject(User $actor, ProjectEvidence $evidence): ProjectEvidence
    {
        Gate::forUser($actor)->authorize('reject', $evidence);

        return DB::transaction(function () use ($evidence): ProjectEvidence {
            $lockedEvidence = $this->lockedEvidence($evidence);
            $this->assertState($lockedEvidence, [ProjectEvidenceState::Draft, ProjectEvidenceState::Verified]);

            $lockedEvidence->forceFill([
                'state' => ProjectEvidenceState::Rejected,
                'is_public' => false,
                'approved_by' => null,
                'approved_at' => null,
                'revoked_at' => null,
            ])->save();

            return $lockedEvidence->refresh();
        });
    }

    public function revoke(User $actor, ProjectEvidence $evidence): ProjectEvidence
    {
        Gate::forUser($actor)->authorize('revoke', $evidence);

        return DB::transaction(function () use ($evidence): ProjectEvidence {
            $lockedEvidence = $this->lockedEvidence($evidence);
            $this->assertState($lockedEvidence, [
                ProjectEvidenceState::Verified,
                ProjectEvidenceState::Approved,
            ]);

            $lockedEvidence->forceFill([
                'state' => ProjectEvidenceState::Revoked,
                'is_public' => false,
                'revoked_at' => now(),
            ])->save();

            return $lockedEvidence->refresh();
        });
    }

    public function setPublicVisibility(User $actor, ProjectEvidence $evidence, bool $isPublic): ProjectEvidence
    {
        Gate::forUser($actor)->authorize('setPublicVisibility', $evidence);

        return DB::transaction(function () use ($evidence, $isPublic): ProjectEvidence {
            $lockedEvidence = $this->lockedEvidence($evidence);

            if ($isPublic) {
                $this->assertState($lockedEvidence, [ProjectEvidenceState::Approved]);
                $this->validator->assertReadyForPublic($lockedEvidence);
            }

            $lockedEvidence->forceFill(['is_public' => $isPublic])->save();

            return $lockedEvidence->refresh();
        });
    }

    private function lockedEvidence(ProjectEvidence $evidence): ProjectEvidence
    {
        return ProjectEvidence::query()
            ->lockForUpdate()
            ->findOrFail($evidence->getKey());
    }

    /** @param list<ProjectEvidenceState> $allowedStates */
    private function assertState(ProjectEvidence $evidence, array $allowedStates): void
    {
        if (! in_array($evidence->state, $allowedStates, true)) {
            throw ValidationException::withMessages([
                'state' => [__('project_evidence.errors.transition_not_allowed')],
            ]);
        }
    }
}
