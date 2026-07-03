<?php

namespace Tests\Feature\Policies;

use App\Models\Author;
use App\Models\Guide;
use App\Models\IntellectualLibrary;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Tags\Tag;
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

    public function test_user_with_permission_can_update_intellectual_library(): void
    {
        $author = Author::query()->create([
            'name' => ['ar' => 'مؤلف', 'en' => 'Author'],
            'is_active' => true,
            'is_draft' => false,
        ]);
        $library = IntellectualLibrary::query()->create([
            'name' => ['ar' => 'اختبار', 'en' => 'Test'],
            'slug' => ['ar' => 'اختبار', 'en' => 'test'],
            'type' => 'article',
            'author_id' => $author->id,
            'seo_title' => ['ar' => 'عنوان', 'en' => 'Title'],
        ]);

        $user = $this->makeUserWithPermissions(['update intellectual_libraries']);

        $this->assertTrue($user->can('update', $library));
    }

    public function test_user_without_permission_cannot_delete_author(): void
    {
        $author = Author::query()->create([
            'name' => ['ar' => 'مؤلف', 'en' => 'Author'],
            'is_active' => true,
            'is_draft' => false,
        ]);
        $user = User::factory()->create();

        $this->assertFalse($user->can('delete', $author));
    }

    public function test_user_with_permission_can_create_guides(): void
    {
        $user = $this->makeUserWithPermissions(['create guides']);

        $this->assertTrue($user->can('create', Guide::class));
    }

    public function test_user_with_permission_can_manage_setting_and_tag(): void
    {
        $setting = Setting::query()->create([
            'group' => 'test',
            'key' => 'sample',
            'value' => '1',
        ]);
        $tag = Tag::findOrCreate('sample-tag', 'tags');
        $user = $this->makeUserWithPermissions(['update settings', 'update tags']);

        $this->assertTrue($user->can('update', $setting));
        $this->assertTrue($user->can('update', $tag));
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
        $resources = ['services', 'intellectual_libraries', 'authors', 'guides', 'settings', 'users', 'roles', 'tags'];
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$action} {$resource}", 'web');
            }
        }
    }
}
