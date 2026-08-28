<?php

namespace App\Actions\Projects;

use App\Enums\ProjectAssetPermissionStatus;
use App\Models\Project;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ApproveProjectMediaForPublic
{
    private const string AUTOMATIC_PERMISSION_REFERENCE = 'Automatically approved under the public project-media policy.';

    public function handle(Media $media): void
    {
        $project = $media->model;

        if (! $project instanceof Project || ! $this->mayApproveMediaFor($project)) {
            return;
        }

        $asset = match ($media->collection_name) {
            Project::IMAGE_COLLECTION => 'image',
            Project::LOGO_COLLECTION => 'logo',
            default => null,
        };

        if ($asset === null) {
            return;
        }

        $statusAttribute = "{$asset}_permission_status";
        $referenceAttribute = "{$asset}_permission_reference";

        if (
            $project->{$statusAttribute} !== ProjectAssetPermissionStatus::Unreviewed
            || filled($project->{$referenceAttribute})
        ) {
            return;
        }

        $project->forceFill([
            $statusAttribute => ProjectAssetPermissionStatus::Approved,
            $referenceAttribute => self::AUTOMATIC_PERMISSION_REFERENCE,
        ])->save();
    }

    private function mayApproveMediaFor(Project $project): bool
    {
        return ! $project->isMediaWithheldForPublic();
    }
}
