<?php

namespace Tests\Feature\Filament;

use App\Enums\ProjectAssetPermissionStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectMediaWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $this->bootAdminPanel();
    }

    public function test_project_media_uploaded_through_the_admin_is_immediately_public(): void
    {
        $key = 'admin-public-media';

        Livewire::actingAs($this->administrator())
            ->test(CreateProject::class)
            ->fillForm($this->projectFormData($key))
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('key', $key)->firstOrFail();
        $portfolioProject = $project->toPortfolioArray('en');

        $this->assertTrue($project->hasMedia(Project::IMAGE_COLLECTION));
        $this->assertTrue($project->hasMedia(Project::LOGO_COLLECTION));
        $this->assertSame(ProjectAssetPermissionStatus::Approved, $project->image_permission_status);
        $this->assertNotEmpty($project->image_permission_reference);
        $this->assertSame(ProjectAssetPermissionStatus::Approved, $project->logo_permission_status);
        $this->assertNotEmpty($project->logo_permission_reference);
        $this->assertTrue($project->mayRenderImage());
        $this->assertTrue($project->mayRenderLogo());
        $this->assertStringContainsString(Project::IMAGE_CONVERSION, $portfolioProject['image']);
        $this->assertStringContainsString(Project::LOGO_CONVERSION, $portfolioProject['logo']);
    }

    /** @return array<string, mixed> */
    private function projectFormData(string $key): array
    {
        return [
            'key' => $key,
            'lens' => 'product',
            'sort_order' => 1,
            'featured' => false,
            'is_active' => true,
            'title' => [
                'ar' => 'مشروع وسائط عام',
                'en' => 'Public media project',
            ],
            'slug' => [
                'ar' => 'مشروع-وسائط-عام',
                'en' => $key,
            ],
            'sector' => [
                'ar' => 'المنتجات الرقمية',
                'en' => 'Digital products',
            ],
            'summary' => [
                'ar' => 'ملخص مشروع يختبر نشر الوسائط العامة من لوحة التحكم.',
                'en' => 'A project summary that tests public media publishing from the admin panel.',
            ],
            'challenge' => [
                'ar' => 'تأكيد نشر الوسائط فور رفعها من لوحة التحكم.',
                'en' => 'Ensure media is published as soon as it is uploaded through the admin panel.',
            ],
            'response' => [
                'ar' => 'ربط رفع الوسائط بمسار النشر العام للمشروع.',
                'en' => 'Connect the media upload to the project public publishing path.',
            ],
            'outcome' => [
                'ar' => 'ظهور الوسائط في بطاقة المشروع على الموقع العام.',
                'en' => 'Media appears in the project card on the public site.',
            ],
            'image_alt' => [
                'ar' => 'صورة توضيحية لمشروع وسائط عام',
                'en' => 'An illustration for a public media project',
            ],
            'logo_alt' => [
                'ar' => 'شعار المشروع',
                'en' => 'Project logo',
            ],
            'tags' => [[
                'ar' => 'وسائط عامة',
                'en' => 'Public media',
            ]],
            Project::IMAGE_COLLECTION => [UploadedFile::fake()->image('project.jpg', 1680, 1080)],
            Project::LOGO_COLLECTION => [UploadedFile::fake()->image('logo.png', 800, 400)],
        ];
    }

    private function administrator(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        return $admin;
    }

    private function bootAdminPanel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }
}
