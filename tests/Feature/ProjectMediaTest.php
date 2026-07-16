<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_uses_legacy_paths_until_media_is_uploaded(): void
    {
        $project = Project::factory()->create([
            'image' => 'images/projects/atlas/legacy.webp',
            'logo' => 'images/brands/projects/legacy.webp',
        ]);

        $portfolioProject = $project->toPortfolioArray('en');

        $this->assertSame('images/projects/atlas/legacy.webp', $portfolioProject['image']);
        $this->assertSame('images/brands/projects/legacy.webp', $portfolioProject['logo']);
    }

    public function test_project_media_replaces_legacy_paths_and_generates_conversions(): void
    {
        Storage::fake('public');

        $project = Project::factory()->create([
            'image' => null,
            'logo' => null,
        ]);

        $image = $project
            ->addMedia(UploadedFile::fake()->image('project.jpg', 1680, 1080))
            ->toMediaCollection(Project::IMAGE_COLLECTION);
        $logo = $project
            ->addMedia(UploadedFile::fake()->image('logo.png', 800, 400))
            ->toMediaCollection(Project::LOGO_COLLECTION);

        $portfolioProject = $project->fresh()->toPortfolioArray('en');

        $this->assertStringContainsString('/storage/', $portfolioProject['image']);
        $this->assertStringContainsString(Project::IMAGE_CONVERSION, $portfolioProject['image']);
        $this->assertStringContainsString('/storage/', $portfolioProject['logo']);
        $this->assertStringContainsString(Project::LOGO_CONVERSION, $portfolioProject['logo']);
        $this->assertFileExists($image->getPath(Project::IMAGE_CONVERSION));
        $this->assertFileExists($image->getPath(Project::THUMBNAIL_CONVERSION));
        $this->assertFileExists($logo->getPath(Project::LOGO_CONVERSION));
    }

    public function test_project_media_collections_only_keep_one_file(): void
    {
        Storage::fake('public');

        $project = Project::factory()->create(['image' => null]);

        $project
            ->addMedia(UploadedFile::fake()->image('first.jpg'))
            ->toMediaCollection(Project::IMAGE_COLLECTION);
        $project
            ->addMedia(UploadedFile::fake()->image('replacement.jpg'))
            ->toMediaCollection(Project::IMAGE_COLLECTION);

        $project->refresh();

        $this->assertCount(1, $project->getMedia(Project::IMAGE_COLLECTION));
        $this->assertSame(
            'replacement.jpg',
            $project->getFirstMedia(Project::IMAGE_COLLECTION)?->file_name,
        );
    }

    public function test_legacy_media_backfill_is_idempotent(): void
    {
        Storage::fake('public');

        $project = Project::factory()->create([
            'image' => 'images/projects/atlas/digi-pedia-ai-learning.webp',
            'logo' => 'images/brands/projects/digi-pedia.webp',
        ]);

        $this->artisan('projects:backfill-media')->assertSuccessful();
        $this->artisan('projects:backfill-media')->assertSuccessful();

        $project->refresh();

        $this->assertCount(1, $project->getMedia(Project::IMAGE_COLLECTION));
        $this->assertCount(1, $project->getMedia(Project::LOGO_COLLECTION));
    }
}
