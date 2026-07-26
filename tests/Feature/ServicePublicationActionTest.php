<?php

namespace Tests\Feature;

use App\Actions\Services\SetServicePublication;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ServicePublicationActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_publish_requires_the_dedicated_service_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('update services');
        $service = Service::factory()->draft()->inactive()->create();

        $this->assertFalse($user->can('publish', $service));

        try {
            app(SetServicePublication::class)->publish($user, $service);
            $this->fail('Publishing without the dedicated permission must be forbidden.');
        } catch (AuthorizationException) {
            $this->assertTrue($service->fresh()->is_draft);
            $this->assertFalse($service->fresh()->is_active);
        }
    }

    public function test_admins_receive_the_durable_service_publication_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $service = Service::factory()->draft()->inactive()->create();

        $this->assertTrue($admin->can('publish', $service));

        $published = app(SetServicePublication::class)->publish($admin, $service);

        $this->assertFalse($published->is_draft);
        $this->assertTrue($published->is_active);
    }

    public function test_super_admin_authorization_never_bypasses_service_content_validation(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $service = Service::factory()->draft()->inactive()->create([
            'fit_signals' => [
                'ar' => ['إشارة واحدة فقط'],
                'en' => ['One signal only'],
            ],
        ]);

        $this->assertTrue($superAdmin->can('publish', $service));

        try {
            app(SetServicePublication::class)->publish($superAdmin, $service);
            $this->fail('Incomplete Service content must block publication for every role.');
        } catch (ValidationException) {
            $service->refresh();

            $this->assertTrue($service->is_draft);
            $this->assertFalse($service->is_active);
        }
    }

    public function test_unpublish_requires_the_same_permission_and_removes_the_service_from_public_state(): void
    {
        $service = Service::factory()->create();
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertFalse($editor->can('publish', $service));

        try {
            app(SetServicePublication::class)->unpublish($editor, $service);
            $this->fail('Unpublishing without the dedicated permission must be forbidden.');
        } catch (AuthorizationException) {
            $this->assertTrue($service->fresh()->is_active);
            $this->assertFalse($service->fresh()->is_draft);
        }

        $unpublished = app(SetServicePublication::class)->unpublish($admin, $service);

        $this->assertTrue($unpublished->is_draft);
        $this->assertFalse($unpublished->is_active);
    }
}
