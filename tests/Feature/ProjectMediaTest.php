<?php

namespace Tests\Feature;

use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectPermissionStatus;
use App\Models\Project;
use Database\Seeders\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Tests\TestCase;

class ProjectMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_uses_legacy_paths_until_media_is_uploaded(): void
    {
        $project = Project::factory()->create([
            'image' => 'images/projects/atlas/legacy.webp',
            'logo' => 'images/brands/projects/legacy.webp',
            'image_permission_status' => ProjectAssetPermissionStatus::Approved,
            'image_permission_reference' => 'approved image use',
            'logo_permission_status' => ProjectAssetPermissionStatus::Approved,
            'logo_permission_reference' => 'approved logo use',
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
            'image_permission_status' => ProjectAssetPermissionStatus::Approved,
            'image_permission_reference' => 'approved image use',
            'logo_permission_status' => ProjectAssetPermissionStatus::Approved,
            'logo_permission_reference' => 'approved logo use',
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

    public function test_newly_uploaded_project_media_is_immediately_approved_for_public_display(): void
    {
        Storage::fake('public');

        $project = Project::factory()->create([
            'image' => null,
            'logo' => null,
        ]);

        $project
            ->addMedia(UploadedFile::fake()->image('project.jpg', 1680, 1080))
            ->toMediaCollection(Project::IMAGE_COLLECTION);
        $project
            ->addMedia(UploadedFile::fake()->image('logo.png', 800, 400))
            ->toMediaCollection(Project::LOGO_COLLECTION);

        $project->refresh();
        $portfolioProject = $project->toPortfolioArray('en');

        $this->assertSame(ProjectAssetPermissionStatus::Approved, $project->image_permission_status);
        $this->assertNotEmpty($project->image_permission_reference);
        $this->assertSame(ProjectAssetPermissionStatus::Approved, $project->logo_permission_status);
        $this->assertNotEmpty($project->logo_permission_reference);
        $this->assertTrue($project->mayRenderImage());
        $this->assertTrue($project->mayRenderLogo());
        $this->assertStringContainsString(Project::IMAGE_CONVERSION, $portfolioProject['image']);
        $this->assertStringContainsString(Project::LOGO_CONVERSION, $portfolioProject['logo']);
    }

    public function test_uploaded_media_stays_withheld_for_anonymized_or_restricted_projects(): void
    {
        Storage::fake('public');

        $anonymizedProject = Project::factory()->create([
            'image' => null,
            'disclosure_level' => ProjectDisclosureLevel::Anonymized,
        ]);
        $restrictedProject = Project::factory()->create([
            'image' => 'images/projects/atlas/restricted.webp',
            'permission_status' => ProjectPermissionStatus::InternalOnly,
            'image_permission_status' => ProjectAssetPermissionStatus::Approved,
            'image_permission_reference' => 'Restricted project media must stay private.',
        ]);

        $anonymizedProject
            ->addMedia(UploadedFile::fake()->image('anonymized.jpg', 1680, 1080))
            ->toMediaCollection(Project::IMAGE_COLLECTION);
        $anonymizedProject->refresh();
        $restrictedProject->refresh();

        $this->assertSame(ProjectAssetPermissionStatus::Unreviewed, $anonymizedProject->image_permission_status);
        $this->assertSame(ProjectAssetPermissionStatus::Approved, $restrictedProject->image_permission_status);
        $this->assertFalse($anonymizedProject->mayRenderImage());
        $this->assertFalse($restrictedProject->mayRenderImage());
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
        $this->assertSame(ProjectAssetPermissionStatus::Approved, $project->image_permission_status);
        $this->assertTrue($project->mayRenderImage());
    }

    public function test_media_is_not_exposed_without_an_approved_permission_and_private_reference(): void
    {
        $project = Project::factory()->create([
            'image' => 'images/projects/atlas/legacy.webp',
            'logo' => 'images/brands/projects/legacy.webp',
        ]);

        $portfolioProject = $project->toPortfolioArray('en');

        $this->assertSame('', $portfolioProject['image']);
        $this->assertSame('', $portfolioProject['alt']);
        $this->assertSame('', $portfolioProject['logo']);
        $this->assertSame('', $portfolioProject['logo_alt']);
    }

    public function test_curated_legacy_media_repair_approves_only_the_existing_public_portfolio_assets(): void
    {
        $curatedProject = Project::factory()->create([
            'key' => 'digi-pedia',
            'image' => 'images/projects/atlas/digi-pedia-ai-learning.webp',
            'logo' => 'images/brands/projects/digi-pedia.webp',
        ]);
        $unapprovedProject = Project::factory()->create([
            'key' => 'unapproved-project',
            'image' => 'images/projects/atlas/unapproved.webp',
            'logo' => 'images/brands/projects/unapproved.webp',
        ]);
        $migration = require database_path('migrations/2026_07_31_210443_approve_curated_legacy_project_media.php');

        $migration->up();

        $curatedProject->refresh();
        $unapprovedProject->refresh();

        $this->assertSame(ProjectAssetPermissionStatus::Approved, $curatedProject->image_permission_status);
        $this->assertSame('Owner-approved existing public portfolio image.', $curatedProject->image_permission_reference);
        $this->assertSame(ProjectAssetPermissionStatus::Approved, $curatedProject->logo_permission_status);
        $this->assertSame('Owner-approved existing public portfolio logo.', $curatedProject->logo_permission_reference);
        $this->assertSame(ProjectAssetPermissionStatus::Unreviewed, $unapprovedProject->image_permission_status);
        $this->assertSame(ProjectAssetPermissionStatus::Unreviewed, $unapprovedProject->logo_permission_status);

        $migration->up();

        $curatedProject->refresh();

        $this->assertSame('Owner-approved existing public portfolio image.', $curatedProject->image_permission_reference);
        $this->assertSame('Owner-approved existing public portfolio logo.', $curatedProject->logo_permission_reference);
    }

    public function test_automatic_media_approval_repair_publishes_only_eligible_unreviewed_assets(): void
    {
        Storage::fake('public');
        Event::fake([MediaHasBeenAddedEvent::class]);

        $managedMediaProject = Project::factory()->create([
            'image' => null,
            'logo' => null,
        ]);
        $managedMediaProject
            ->addMedia(UploadedFile::fake()->image('managed-project.jpg', 1680, 1080))
            ->toMediaCollection(Project::IMAGE_COLLECTION);
        $managedMediaProject
            ->addMedia(UploadedFile::fake()->image('managed-logo.png', 800, 400))
            ->toMediaCollection(Project::LOGO_COLLECTION);

        $legacyPathProject = Project::factory()->create([
            'image' => 'images/projects/atlas/newly-public.webp',
            'logo' => null,
        ]);
        $anonymizedProject = Project::factory()->create([
            'image' => 'images/projects/atlas/anonymized.webp',
            'disclosure_level' => ProjectDisclosureLevel::Anonymized,
        ]);
        $restrictedProject = Project::factory()->create([
            'image' => 'images/projects/atlas/restricted.webp',
            'permission_status' => ProjectPermissionStatus::InternalOnly,
        ]);
        $revokedAssetProject = Project::factory()->create([
            'image' => 'images/projects/atlas/revoked.webp',
            'image_permission_status' => ProjectAssetPermissionStatus::Revoked,
            'image_permission_reference' => 'Revoked by the project owner.',
        ]);
        $assetlessProject = Project::factory()->create([
            'image' => null,
            'logo' => null,
        ]);
        $migration = require database_path('migrations/2026_08_28_130918_auto_approve_existing_project_media.php');

        $migration->up();

        $managedMediaProject->refresh();
        $legacyPathProject->refresh();
        $anonymizedProject->refresh();
        $restrictedProject->refresh();
        $revokedAssetProject->refresh();
        $assetlessProject->refresh();

        $this->assertSame(ProjectAssetPermissionStatus::Approved, $managedMediaProject->image_permission_status);
        $this->assertSame(ProjectAssetPermissionStatus::Approved, $managedMediaProject->logo_permission_status);
        $this->assertTrue($managedMediaProject->mayRenderImage());
        $this->assertTrue($managedMediaProject->mayRenderLogo());
        $this->assertSame(ProjectAssetPermissionStatus::Approved, $legacyPathProject->image_permission_status);
        $this->assertTrue($legacyPathProject->mayRenderImage());
        $this->assertSame(ProjectAssetPermissionStatus::Unreviewed, $anonymizedProject->image_permission_status);
        $this->assertSame(ProjectAssetPermissionStatus::Unreviewed, $restrictedProject->image_permission_status);
        $this->assertSame(ProjectAssetPermissionStatus::Revoked, $revokedAssetProject->image_permission_status);
        $this->assertSame(ProjectAssetPermissionStatus::Unreviewed, $assetlessProject->image_permission_status);

        $imageReference = $managedMediaProject->image_permission_reference;
        $logoReference = $managedMediaProject->logo_permission_reference;

        $migration->up();

        $managedMediaProject->refresh();

        $this->assertSame($imageReference, $managedMediaProject->image_permission_reference);
        $this->assertSame($logoReference, $managedMediaProject->logo_permission_reference);
    }

    public function test_project_seeder_approves_the_curated_legacy_media_for_fresh_installations(): void
    {
        $this->seed(ProjectSeeder::class);

        $project = Project::query()->where('key', 'digi-pedia')->firstOrFail();

        $this->assertTrue($project->mayRenderImage());
        $this->assertTrue($project->mayRenderLogo());
        $this->assertSame('images/projects/atlas/digi-pedia-ai-learning.webp', $project->toPortfolioArray('en')['image']);
        $this->assertSame('images/brands/projects/digi-pedia.webp', $project->toPortfolioArray('en')['logo']);
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
