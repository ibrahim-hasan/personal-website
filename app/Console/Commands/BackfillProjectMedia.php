<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('projects:backfill-media')]
#[Description('Import legacy project image paths into their Media Library collections')]
class BackfillProjectMedia extends Command
{
    public function handle(): int
    {
        $imported = 0;
        $skipped = 0;

        Project::query()->eachById(function (Project $project) use (&$imported, &$skipped): void {
            $imported += $this->importLegacyFile($project, 'image', Project::IMAGE_COLLECTION);
            $imported += $this->importLegacyFile($project, 'logo', Project::LOGO_COLLECTION);

            if (! $project->hasMedia(Project::IMAGE_COLLECTION) && filled($project->image)) {
                $skipped++;
            }

            if (! $project->hasMedia(Project::LOGO_COLLECTION) && filled($project->logo)) {
                $skipped++;
            }
        });

        $this->components->info("Imported {$imported} project media files; skipped {$skipped} missing or unsafe paths.");

        return self::SUCCESS;
    }

    private function importLegacyFile(Project $project, string $attribute, string $collection): int
    {
        if ($project->hasMedia($collection)) {
            return 0;
        }

        $legacyPath = $project->getAttribute($attribute);

        if (! is_string($legacyPath) || blank($legacyPath)) {
            return 0;
        }

        $publicDirectory = realpath(public_path());
        $sourcePath = realpath(public_path($legacyPath));

        if ($publicDirectory === false || $sourcePath === false || ! str_starts_with($sourcePath, $publicDirectory.DIRECTORY_SEPARATOR)) {
            return 0;
        }

        $project
            ->addMedia($sourcePath)
            ->preservingOriginal()
            ->toMediaCollection($collection);

        return 1;
    }
}
