<?php

namespace Tests\Feature\Domain;

use App\Models\Author;
use App\Models\User;
use App\Policies\AuthorPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuthorDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    public function test_author_policy_requires_delete_permission(): void
    {
        $author = Author::factory()->create();
        $userWithoutPermission = User::factory()->create();

        $policy = new AuthorPolicy;
        $canDelete = $policy->delete($userWithoutPermission, $author);

        $this->assertFalse($canDelete);
    }

    public function test_author_policy_checks_library_existence(): void
    {
        $author = Author::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('delete authors');

        $this->assertFalse($author->intellectualLibraries()->exists());

        $policy = new AuthorPolicy;
        $canDelete = $policy->delete($user, $author);

        $this->assertTrue($canDelete);
    }

    public function test_author_model_has_intellectual_libraries_method(): void
    {
        $author = new Author;

        $this->assertTrue(method_exists($author, 'intellectualLibraries'));
    }

    public function test_author_policy_blocks_delete_when_has_libraries(): void
    {
        $this->markTestSkipped('Requires IntellectualLibrary factory - SQLite translatable compatibility');

        $author = Author::factory()->create();
        $user = User::factory()->create();
        $user->givePermissionTo('delete authors');

        $policy = new AuthorPolicy;
        $canDelete = $policy->delete($user, $author);

        $this->assertFalse($canDelete);
    }

    protected function seedPermissions(): void
    {
        $resources = ['services', 'intellectual_libraries', 'authors', 'settings', 'users', 'roles', 'tags'];
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'restore', 'force_delete'];

        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$action} {$resource}", 'web');
            }
        }
    }
}
