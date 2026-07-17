<?php

namespace Tests\Feature\Filament;

use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalContentAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authorized_admin_can_manage_current_services_and_projects(): void
    {
        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            ServiceSeeder::class,
            ProjectSeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $service = Service::query()->firstOrFail();
        $project = Project::query()->firstOrFail();

        $this->assertNotEmpty($service->key);
        $this->assertNotEmpty($service->getTranslation('slug', 'ar'));
        $this->assertNotEmpty($service->getTranslation('slug', 'en'));
        $this->assertNotEmpty($project->key);
        $this->assertNotEmpty($project->getTranslation('slug', 'ar'));
        $this->assertNotEmpty($project->getTranslation('slug', 'en'));

        $this->actingAs($admin)
            ->get('/admin/services')
            ->assertOk()
            ->assertSee($service->getTranslation('name', 'ar'));

        $this->actingAs($admin)
            ->get('/admin/projects')
            ->assertOk()
            ->assertSee($project->getTranslation('title', 'ar'))
            ->assertSee('/admin/projects/'.$project->getKey().'/edit', false)
            ->assertDontSee('/admin/projects/'.$project->getTranslation('slug', 'ar').'/edit', false);

        $this->actingAs($admin)
            ->get('/admin/projects/'.$project->getKey())
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/projects/'.$project->getKey().'/edit')
            ->assertOk()
            ->assertSee(__('admin.fields.key'))
            ->assertSee(__('admin.fields.slug'))
            ->assertSee(__('admin.fields.project_image'))
            ->assertSee(__('admin.hints.project_image_upload'))
            ->assertDontSee(__('admin.hints.public_asset_path'));

        $this->actingAs($admin)
            ->get('/admin/services/'.$service->getKey().'/edit')
            ->assertOk()
            ->assertSee(__('admin.fields.key'))
            ->assertSee(__('admin.fields.slug'));
    }
}
