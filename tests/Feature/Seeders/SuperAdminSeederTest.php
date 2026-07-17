<?php

namespace Tests\Feature\Seeders;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_active_verified_super_admin_from_configuration(): void
    {
        config()->set('admin.name', 'Production Admin');
        config()->set('admin.email', 'admin@example.com');
        config()->set('admin.password', 'a-secure-production-password');

        $this->seed(PermissionSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame('Production Admin', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue($user->hasRole('super_admin'));
        $this->assertTrue(Hash::check('a-secure-production-password', $user->password));
    }

    public function test_it_is_idempotent_for_the_configured_admin_email(): void
    {
        config()->set('admin.name', 'Production Admin');
        config()->set('admin.email', 'admin@example.com');
        config()->set('admin.password', 'a-secure-production-password');

        $this->seed(PermissionSeeder::class);
        $this->seed([SuperAdminSeeder::class, SuperAdminSeeder::class]);

        $this->assertSame(1, User::query()->where('email', 'admin@example.com')->count());
        $this->assertSame(1, User::query()->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))->count());
    }

    public function test_it_rejects_missing_production_credentials(): void
    {
        config()->set('admin.name', null);
        config()->set('admin.email', null);
        config()->set('admin.password', null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ADMIN_NAME');

        $this->seed(SuperAdminSeeder::class);
    }
}
