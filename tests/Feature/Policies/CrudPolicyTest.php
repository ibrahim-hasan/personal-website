<?php

namespace Tests\Feature\Policies;

use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CrudPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_user_with_permission_can_view_any_services(): void
    {
        $user = $this->makeUserWithPermissions(['view_any services']);

        $this->assertTrue($user->can('viewAny', Service::class));
    }

    public function test_user_without_permission_cannot_view_any_services(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->can('viewAny', Service::class));
    }

    public function test_user_with_permission_can_update_article(): void
    {
        $article = Article::factory()->create();

        $user = $this->makeUserWithPermissions(['update articles']);

        $this->assertTrue($user->can('update', $article));
    }

    public function test_user_without_permission_cannot_delete_project(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create();

        $this->assertFalse($user->can('delete', $project));
    }

    public function test_user_with_permission_can_create_projects(): void
    {
        $user = $this->makeUserWithPermissions(['create projects']);

        $this->assertTrue($user->can('create', Project::class));
    }

    public function test_user_with_permission_can_manage_setting(): void
    {
        $setting = Setting::query()->create([
            'group' => 'test',
            'key' => 'sample',
            'value' => '1',
        ]);
        $user = $this->makeUserWithPermissions(['update settings']);

        $this->assertTrue($user->can('update', $setting));
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function makeUserWithPermissions(array $permissions): User
    {
        $role = Role::findOrCreate('test-role', 'web');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function seedPermissions(): void
    {
        $resources = ['services', 'projects', 'articles', 'comments', 'contact_inquiries', 'settings', 'users', 'roles'];
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$action} {$resource}", 'web');
            }
        }
    }
}
