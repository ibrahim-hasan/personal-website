<?php

namespace Tests\Feature\Guide;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminGuideFileDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedGuidePermissions();
    }

    public function test_admin_with_view_permission_can_download_private_guide_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('guides/private-guide.pdf', 'private-pdf-content');

        $guide = Guide::factory()->draft()->create([
            'is_active' => false,
        ]);

        $guide->addMediaFromDisk('guides/private-guide.pdf', 'local')
            ->usingFileName('private-guide.pdf')
            ->toMediaCollection('guide_file');

        $admin = $this->makeUserWithPermissions(['view guides']);

        $this->actingAs($admin)
            ->get(route('filament.admin.guides.guide-file', ['guide' => $guide]))
            ->assertOk()
            ->assertDownload('private-guide.pdf');
    }

    public function test_guest_cannot_download_admin_guide_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('guides/private-guide.pdf', 'private-pdf-content');

        $guide = Guide::factory()->create();
        $guide->addMediaFromDisk('guides/private-guide.pdf', 'local')
            ->usingFileName('private-guide.pdf')
            ->toMediaCollection('guide_file');

        $this->get(route('filament.admin.guides.guide-file', ['guide' => $guide]))
            ->assertForbidden();
    }

    public function test_admin_without_view_permission_gets_forbidden(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('guides/private-guide.pdf', 'private-pdf-content');

        $guide = Guide::factory()->create();
        $guide->addMediaFromDisk('guides/private-guide.pdf', 'local')
            ->usingFileName('private-guide.pdf')
            ->toMediaCollection('guide_file');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('filament.admin.guides.guide-file', ['guide' => $guide]))
            ->assertForbidden();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function makeUserWithPermissions(array $permissions): User
    {
        $role = Role::findOrCreate('test-role-admin-guide-file', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function seedGuidePermissions(): void
    {
        foreach (['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'] as $action) {
            Permission::findOrCreate("{$action} guides", 'web');
        }
    }
}
