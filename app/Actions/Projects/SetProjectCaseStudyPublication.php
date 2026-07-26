<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;
use App\Services\Projects\ProjectCaseStudyPublicationValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SetProjectCaseStudyPublication
{
    public function __construct(private readonly ProjectCaseStudyPublicationValidator $validator) {}

    public function publish(User $actor, Project $project): Project
    {
        Gate::forUser($actor)->authorize('publish', $project);

        return DB::transaction(function () use ($project): Project {
            $lockedProject = $this->lockedProject($project);
            $lockedProject->forceFill(['is_detailed_case_study' => true]);

            $eligibility = $this->validator->validate($lockedProject);

            if (! $eligibility->isEligible()) {
                throw ValidationException::withMessages([
                    'case_study' => $this->publicationMessages($eligibility->violations()),
                ]);
            }

            $lockedProject->save();

            return $lockedProject->refresh();
        });
    }

    public function unpublish(User $actor, Project $project): Project
    {
        Gate::forUser($actor)->authorize('publish', $project);

        return DB::transaction(function () use ($project): Project {
            $lockedProject = $this->lockedProject($project);

            $lockedProject->forceFill(['is_detailed_case_study' => false])->save();

            return $lockedProject->refresh();
        });
    }

    private function lockedProject(Project $project): Project
    {
        return Project::withTrashed()
            ->with(['evidence', 'services', 'articles'])
            ->lockForUpdate()
            ->findOrFail($project->getKey());
    }

    /**
     * Convert validator identifiers into safe operator guidance without exposing
     * private evidence or permission references.
     *
     * @param  list<string>  $violations
     * @return list<string>
     */
    private function publicationMessages(array $violations): array
    {
        return array_values(array_unique(array_map(
            function (string $violation): string {
                return match (true) {
                    $violation === 'project.inactive' => __('project_admin.publication.inactive'),
                    $violation === 'project.deleted' => __('project_admin.publication.deleted'),
                    $violation === 'project.review_missing' => __('project_admin.publication.review_missing'),
                    $violation === 'project.delivery_entity_missing' => __('project_admin.publication.delivery_entity_missing'),
                    $violation === 'project.evidence_level_missing' => __('project_admin.publication.evidence_level_missing'),
                    $violation === 'project.permission_reference_missing' => __('project_admin.publication.permission_reference_missing'),
                    $violation === 'project.disclosure_missing',
                    $violation === 'project.disclosure_incompatible',
                    $violation === 'project.permission_not_approved' => __('project_admin.publication.permission'),
                    str_starts_with($violation, 'translation.') => __('project_admin.publication.translations'),
                    str_starts_with($violation, 'sections.') => __('project_admin.publication.sections'),
                    str_starts_with($violation, 'relation.service.') => __('project_admin.publication.related_service'),
                    str_starts_with($violation, 'relation.article.') => __('project_admin.publication.related_article'),
                    str_starts_with($violation, 'image.'),
                    str_starts_with($violation, 'logo.') => __('project_admin.publication.media_permission'),
                    str_starts_with($violation, 'evidence.level.') => __('project_admin.publication.evidence_level'),
                    str_starts_with($violation, 'evidence.') => __('project_admin.publication.evidence'),
                    default => __('project_admin.publication.incomplete'),
                };
            },
            $violations,
        )));
    }
}
