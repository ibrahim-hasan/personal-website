<?php

namespace Tests\Feature\Guide;

use App\Models\Guide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GuideResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedGuidePermissions();
    }

    public function test_user_with_permission_can_view_any_guides(): void
    {
        $user = $this->makeUserWithPermissions(['view_any guides']);

        $this->assertTrue($user->can('viewAny', Guide::class));
    }

    public function test_home_page_loads_posted_guides_only(): void
    {
        $visible = Guide::factory()->create([
            'title' => ['ar' => 'دليل ظاهر', 'en' => 'Visible Guide'],
            'description' => [
                'ar' => 'وصف الدليل الظاهر',
                'en' => 'Visible guide description',
            ],
            'sort_order' => 1,
        ]);

        $hidden = Guide::factory()->draft()->create([
            'title' => ['ar' => 'دليل مخفي', 'en' => 'Hidden Guide'],
            'sort_order' => 2,
        ]);

        $postedGuides = Guide::query()->posted()->ordered()->get();

        $this->assertCount(1, $postedGuides);
        $this->assertTrue($postedGuides->contains('id', $visible->id));
        $this->assertFalse($postedGuides->contains('id', $hidden->id));

        $this->get(route('home'))->assertOk();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function makeUserWithPermissions(array $permissions): User
    {
        $role = Role::findOrCreate('test-role-guides', 'web');
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
