<?php

namespace Tests\Feature;

use App\Enums\AtharContributionStatus;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPlacement;
use App\Enums\AtharPublicationStatus;
use App\Enums\AtharRelationship;
use App\Filament\Resources\AtharInvitations\AtharInvitationResource;
use App\Filament\Resources\AtharInvitations\Pages\ListAtharInvitations;
use App\Models\AtharContribution;
use App\Models\AtharInvitation;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AtharAdminLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_athar_admin_states_are_translated_for_supported_locales(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach (AtharInvitationStatus::cases() as $state) {
                $this->assertTranslated("admin.athar.invitation_statuses.{$state->value}", $state->label());
            }

            foreach (AtharInvitationDeliveryMode::cases() as $state) {
                $this->assertTranslated("admin.athar.delivery_modes.{$state->value}", $state->label());
            }

            foreach (AtharContributionStatus::cases() as $state) {
                $this->assertTranslated("admin.athar.contribution_statuses.{$state->value}", $state->label());
            }

            foreach (AtharPublicationStatus::cases() as $state) {
                $this->assertTranslated("admin.athar.publication_statuses.{$state->value}", $state->label());
            }

            foreach (AtharRelationship::cases() as $state) {
                $this->assertTranslated("admin.athar.relationships.{$state->value}", $state->label());
            }

            foreach (AtharPlacement::cases() as $state) {
                $this->assertTranslated("admin.athar.placements.{$state->value}", $state->adminLabel());
            }

            foreach (AtharIdentityDisplay::cases() as $state) {
                $this->assertTranslated("admin.athar.identity_display.{$state->value}", $state->label());
            }
        }
    }

    public function test_athar_resource_labels_are_localized(): void
    {
        app()->setLocale('ar');

        $this->assertSame('دعوة أثر', AtharInvitationResource::getModelLabel());
        $this->assertSame('دعوات أثر', AtharInvitationResource::getPluralModelLabel());
        $this->assertSame('أثر', AtharInvitationResource::getNavigationLabel());
    }

    public function test_arabic_athar_record_page_hides_private_credentials_and_source_from_view_only_users(): void
    {
        app()->setLocale('ar');
        $this->seed(PermissionSeeder::class);

        $role = Role::create(['name' => 'athar reviewer', 'guard_name' => 'web']);
        $viewAny = Permission::create(['name' => 'view_any athar_invitations', 'guard_name' => 'web']);
        $view = Permission::create(['name' => 'view athar_invitations', 'guard_name' => 'web']);
        $role->syncPermissions([$viewAny, $view]);
        $admin = User::factory()->create(['locale_preference' => 'ar']);
        $admin->assignRole($role);
        $invitation = AtharInvitation::factory()->create(['created_by' => $admin]);

        $this->actingAs($admin)
            ->get('/admin/athar-invitations/'.$invitation->getKey())
            ->assertOk()
            ->assertSee(__('admin.sections.athar_invitation'))
            ->assertSee(__('admin.fields.email_address'))
            ->assertSee(__('admin.fields.delivery_mode'))
            ->assertSee(__('admin.fields.status'))
            ->assertSee(__('admin.athar.invitation_statuses.sent'))
            ->assertSee(__('admin.athar.delivery_modes.email'))
            ->assertSee(__('admin.athar.placements.about'))
            ->assertDontSee(__('admin.fields.share_link'))
            ->assertDontSee('/athar/', false)
            ->assertDontSee(__('admin.sections.athar_private'))
            ->assertDontSee('admin.fields.email');
    }

    public function test_the_share_link_is_only_visible_to_users_who_can_send_an_invitation(): void
    {
        $this->seed(PermissionSeeder::class);
        $role = Role::create(['name' => 'athar sender', 'guard_name' => 'web']);
        $role->syncPermissions([
            Permission::firstOrCreate(['name' => 'view_any athar_invitations', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'view athar_invitations', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'send athar_invitations', 'guard_name' => 'web']),
        ]);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $invitation = AtharInvitation::factory()->create();

        $this->actingAs($admin)
            ->get('/admin/athar-invitations/'.$invitation->getKey())
            ->assertOk()
            ->assertSee(__('admin.fields.share_link'))
            ->assertSee('/athar/', false);
    }

    public function test_the_private_source_requires_its_own_permission(): void
    {
        $this->seed(PermissionSeeder::class);
        $role = Role::create(['name' => 'athar private reviewer', 'guard_name' => 'web']);
        $role->syncPermissions([
            Permission::firstOrCreate(['name' => 'view_any athar_invitations', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'view athar_invitations', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'view_private athar_contributions', 'guard_name' => 'web']),
        ]);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $invitation = AtharInvitation::factory()->create();
        AtharContribution::factory()->for($invitation, 'invitation')->submitted()->create([
            'sealed_payload' => ['freeform' => 'Private source visible only to this role.'],
        ]);

        $this->actingAs($admin)
            ->get('/admin/athar-invitations/'.$invitation->getKey())
            ->assertOk()
            ->assertSee(__('admin.sections.athar_private'))
            ->assertSee('Private source visible only to this role.', false);
    }

    public function test_admin_keeps_publication_copy_and_identity_contributor_led(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $invitation = AtharInvitation::factory()->create([
            'created_by' => $admin->getKey(),
            'preferred_locale' => 'ar',
            'personal_reason' => 'سياق أعدّه إبراهيم للمراجعة.',
        ]);
        $this->bootAdminPanel();

        Livewire::actingAs($admin)
            ->test(ListAtharInvitations::class)
            ->assertTableActionDoesNotExist('prepare_publication')
            ->assertTableActionDoesNotExist('delete');

        $this->actingAs($admin)
            ->get('/admin/athar-invitations/create')
            ->assertOk()
            ->assertDontSee(__('admin.fields.relationship'))
            ->assertDontSee(__('admin.fields.personal_reason'))
            ->assertDontSee(__('admin.fields.identity_display'));
    }

    private function bootAdminPanel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }

    private function assertTranslated(string $key, string $value): void
    {
        $this->assertNotSame($key, $value);
        $this->assertNotSame(str_replace('.', '_', $key), $value);
    }
}
