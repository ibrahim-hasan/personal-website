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

    public function test_arabic_athar_record_page_renders_localized_labels_and_values(): void
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
            ->assertSee(__('admin.fields.share_link'))
            ->assertSee(__('admin.fields.status'))
            ->assertSee(__('admin.athar.invitation_statuses.sent'))
            ->assertSee(__('admin.athar.delivery_modes.email'))
            ->assertSee('/athar/', false)
            ->assertSee(__('admin.athar.placements.about'))
            ->assertSee(__('admin.fields.contribution_status'))
            ->assertDontSee('admin.fields.email');
    }

    public function test_admin_no_longer_has_a_prepare_publication_action(): void
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
            ->assertTableActionDoesNotExist('prepare_publication');
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
