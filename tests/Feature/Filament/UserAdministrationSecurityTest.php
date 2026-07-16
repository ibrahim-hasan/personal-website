<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Profile;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Tests\TestCase;

class UserAdministrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_a_non_super_admin_cannot_manage_super_admin_users_or_the_protected_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $protectedRole = Role::query()->where('name', 'super_admin')->firstOrFail();

        $this->assertFalse($admin->can('update', $superAdmin));
        $this->assertFalse($admin->can('delete', $superAdmin));
        $this->assertFalse($admin->can('update', $protectedRole));
        $this->assertFalse($admin->can('delete', $protectedRole));

        $this->actingAs($admin)
            ->get('/admin/users/'.$superAdmin->getKey().'/edit')
            ->assertForbidden();
    }

    public function test_a_non_super_admin_can_assign_an_allowed_role_but_not_super_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $editorRole = Role::query()->where('name', 'editor')->firstOrFail();
        $superAdminRole = Role::query()->where('name', 'super_admin')->firstOrFail();

        $this->bootAdminPanel();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm($this->userFormData('forbidden@example.test', [$superAdminRole->getKey()]))
            ->call('create')
            ->assertHasFormErrors(['roles.0']);

        $this->assertDatabaseMissing(User::class, ['email' => 'forbidden@example.test']);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm($this->userFormData('editor@example.test', [$editorRole->getKey()]))
            ->call('create')
            ->assertHasNoFormErrors();

        $createdUser = User::query()->where('email', 'editor@example.test')->firstOrFail();
        $this->assertTrue($createdUser->hasRole('editor'));
        $this->assertFalse($createdUser->hasRole('super_admin'));
    }

    public function test_the_super_admin_role_cannot_be_updated_or_deleted_by_anyone(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $protectedRole = Role::query()->where('name', 'super_admin')->firstOrFail();

        $this->assertFalse($superAdmin->can('update', $protectedRole));
        $this->assertFalse($superAdmin->can('delete', $protectedRole));
        $this->assertFalse($superAdmin->can('restore', $protectedRole));
        $this->assertFalse($superAdmin->can('forceDelete', $protectedRole));

        $this->actingAs($superAdmin)
            ->get('/admin/roles/'.$protectedRole->getKey().'/edit')
            ->assertForbidden();

        $this->bootAdminPanel();

        Livewire::actingAs($superAdmin)
            ->test(ListRoles::class)
            ->assertTableActionHidden('edit', $protectedRole)
            ->assertTableActionHidden('delete', $protectedRole);
    }

    public function test_user_management_enforces_unique_emails(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $existingUser = User::factory()->create();

        $this->bootAdminPanel();

        Livewire::actingAs($superAdmin)
            ->test(CreateUser::class)
            ->fillForm($this->userFormData($existingUser->email, []))
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);

        $this->assertSame(1, User::query()->where('email', $existingUser->email)->count());
    }

    public function test_user_and_profile_forms_use_the_application_default_password_rule(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        Password::defaults(Password::min(30));

        try {
            $this->bootAdminPanel();

            $weakPassword = 'Not-long-enough-123!';

            Livewire::actingAs($superAdmin)
                ->test(CreateUser::class)
                ->fillForm([
                    ...$this->userFormData('weak-password@example.test', []),
                    'password' => $weakPassword,
                    'password_confirmation' => $weakPassword,
                ])
                ->call('create')
                ->assertHasFormErrors(['password']);

            Livewire::actingAs($superAdmin)
                ->test(Profile::class)
                ->fillForm([
                    'name' => $superAdmin->name,
                    'email' => $superAdmin->email,
                    'current_password' => 'password',
                    'password' => $weakPassword,
                    'password_confirmation' => $weakPassword,
                ])
                ->call('save')
                ->assertHasFormErrors(['password']);
        } finally {
            Password::defaults(Password::min(8));
        }
    }

    public function test_profile_page_renders_and_updates_only_to_a_unique_email(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $existingUser = User::factory()->create();

        $this->bootAdminPanel();

        $this->actingAs($superAdmin)
            ->get(Profile::getUrl())
            ->assertOk()
            ->assertSee(__('admin.auth.save_profile'));

        Livewire::actingAs($superAdmin)
            ->test(Profile::class)
            ->fillForm([
                'name' => 'Updated Profile',
                'email' => $existingUser->email,
                'current_password' => 'password',
                'password' => 'A-secure-new-password-123!',
                'password_confirmation' => 'A-secure-new-password-123!',
            ])
            ->call('save')
            ->assertHasFormErrors(['email' => 'unique']);

        $superAdmin->refresh();
        $this->assertNotSame('Updated Profile', $superAdmin->name);

        Livewire::actingAs($superAdmin)
            ->test(Profile::class)
            ->fillForm([
                'name' => 'Updated Profile',
                'email' => 'updated-profile@example.test',
                'current_password' => 'password',
                'password' => 'A-secure-new-password-123!',
                'password_confirmation' => 'A-secure-new-password-123!',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $superAdmin->refresh();
        $this->assertSame('Updated Profile', $superAdmin->name);
        $this->assertSame('updated-profile@example.test', $superAdmin->email);
        $this->assertNull($superAdmin->email_verified_at);
        $this->assertTrue(Hash::check('A-secure-new-password-123!', $superAdmin->password));
    }

    public function test_a_super_admin_cannot_deactivate_delete_or_remove_their_own_role(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->bootAdminPanel();

        Livewire::actingAs($superAdmin)
            ->test(EditUser::class, ['record' => $superAdmin->getKey()])
            ->fillForm([
                'name' => 'Updated Admin Name',
                'email' => $superAdmin->email,
                'is_active' => false,
                'roles' => [],
                'password' => null,
                'password_confirmation' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $superAdmin->refresh();
        $this->assertSame('Updated Admin Name', $superAdmin->name);
        $this->assertTrue($superAdmin->is_active);
        $this->assertTrue($superAdmin->hasRole('super_admin'));
        $this->assertFalse($superAdmin->can('delete', $superAdmin));
        $this->assertFalse(UserResource::canDelete($superAdmin));
    }

    public function test_a_super_admin_can_assign_roles_and_change_another_users_status(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $reader = User::factory()->create();
        $editorRole = Role::query()->where('name', 'editor')->firstOrFail();

        $this->bootAdminPanel();

        Livewire::actingAs($superAdmin)
            ->test(EditUser::class, ['record' => $reader->getKey()])
            ->fillForm([
                'name' => $reader->name,
                'email' => $reader->email,
                'is_active' => false,
                'roles' => [$editorRole->getKey()],
                'password' => null,
                'password_confirmation' => null,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $reader->refresh();
        $this->assertFalse($reader->is_active);
        $this->assertTrue($reader->hasRole('editor'));
    }

    /**
     * @param  array<int, int|string>  $roleIds
     * @return array<string, mixed>
     */
    private function userFormData(string $email, array $roleIds): array
    {
        return [
            'name' => 'Managed User',
            'email' => $email,
            'is_active' => true,
            'roles' => $roleIds,
            'password' => 'A-secure-test-password-123!',
            'password_confirmation' => 'A-secure-test-password-123!',
        ];
    }

    private function bootAdminPanel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }
}
