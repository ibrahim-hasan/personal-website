<?php

namespace Tests\Feature;

use App\Actions\Athar\DeleteAtharInvitation;
use App\Actions\Athar\DeleteAtharPrivateMessage;
use App\Actions\Athar\ExpireAtharInvitations;
use App\Actions\Athar\HideAtharPublication;
use App\Actions\Athar\RevokeAtharInvitation;
use App\Actions\Athar\UnhideAtharPublication;
use App\Enums\AtharConsentEventType;
use App\Enums\AtharContributionStatus;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPlacement;
use App\Enums\AtharPublicationStatus;
use App\Filament\Resources\AtharInvitations\Pages\ListAtharInvitations;
use App\Models\AtharAccessChallenge;
use App\Models\AtharContribution;
use App\Models\AtharInvitation;
use App\Models\AtharPublicationConsentEvent;
use App\Models\AtharPublicationVersion;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AtharAccessCodeNotification;
use App\Notifications\AtharApprovalNotification;
use App\Notifications\AtharInvitationNotification;
use App\Policies\AtharInvitationPolicy;
use App\Policies\AtharPublicationVersionPolicy;
use App\Support\AtharPublicationSnapshot;
use App\Support\AtharPublicProof;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AtharAdminActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_revoke_marks_an_invitation_revoked_and_blocks_token_access(): void
    {
        $invitation = AtharInvitation::factory()->create(['status' => AtharInvitationStatus::Sent]);

        app(RevokeAtharInvitation::class)->handle($invitation);

        $invitation->refresh();
        $this->assertSame(AtharInvitationStatus::Revoked, $invitation->status);
        $this->assertNotNull($invitation->revoked_at);
        $this->assertFalse($invitation->isAccessible());
    }

    public function test_revoke_is_idempotent_for_already_terminal_invitations(): void
    {
        $invitation = AtharInvitation::factory()->create(['status' => AtharInvitationStatus::Completed]);

        $this->expectException(HttpException::class);
        app(RevokeAtharInvitation::class)->handle($invitation);
    }

    public function test_revoke_does_not_remove_a_contributors_control_link_after_they_started(): void
    {
        $invitation = AtharInvitation::factory()->create(['status' => AtharInvitationStatus::Verified]);
        AtharContribution::factory()->for($invitation, 'invitation')->create();

        $this->expectException(HttpException::class);
        app(RevokeAtharInvitation::class)->handle($invitation);
    }

    public function test_the_expire_command_transitions_past_due_invitations(): void
    {
        AtharInvitation::factory()->create([
            'status' => AtharInvitationStatus::Sent,
            'expires_at' => Carbon::now()->subDay(),
        ]);
        AtharInvitation::factory()->create([
            'status' => AtharInvitationStatus::Verified,
            'expires_at' => Carbon::now()->subHour(),
        ]);
        $stillValid = AtharInvitation::factory()->create([
            'status' => AtharInvitationStatus::Sent,
            'expires_at' => Carbon::now()->addDays(7),
        ]);
        $revoked = AtharInvitation::factory()->create([
            'status' => AtharInvitationStatus::Sent,
            'expires_at' => Carbon::now()->subDay(),
            'revoked_at' => Carbon::now(),
        ]);
        $engaged = AtharInvitation::factory()->create([
            'status' => AtharInvitationStatus::Verified,
            'expires_at' => Carbon::now()->subDay(),
        ]);
        AtharContribution::factory()->for($engaged, 'invitation')->create();

        $count = app(ExpireAtharInvitations::class)->handle();

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('athar_invitations', ['id' => $stillValid->getKey(), 'status' => AtharInvitationStatus::Sent->value]);
        $this->assertDatabaseHas('athar_invitations', ['id' => $revoked->getKey(), 'status' => AtharInvitationStatus::Sent->value]);
        $this->assertDatabaseHas('athar_invitations', ['id' => $engaged->getKey(), 'status' => AtharInvitationStatus::Verified->value]);
        $this->assertTrue($engaged->fresh()->isAccessible());
    }

    public function test_hide_removes_a_publication_from_the_public_site_and_unhide_restores_it(): void
    {
        $invitation = AtharInvitation::factory()->create(['placement' => AtharPlacement::About]);
        $contribution = AtharContribution::factory()
            ->for($invitation, 'invitation')
            ->submitted()
            ->create(['status' => AtharContributionStatus::Published]);
        $version = AtharPublicationVersion::factory()
            ->for($contribution, 'contribution')
            ->create([
                'status' => AtharPublicationStatus::Published,
                'placement' => AtharPlacement::About,
                'approved_locales' => ['en'],
                'public_payload' => ['en' => ['text' => 'A live endorsement to hide.', 'context' => '']],
            ]);
        $version->forceFill([
            'snapshot_hash' => AtharPublicationSnapshot::hash($version->public_payload),
        ])->save();
        $version->consentEvents()->create([
            'contribution_id' => $contribution->getKey(),
            'event_type' => 'approved',
            'snapshot_hash' => $version->snapshot_hash,
            'approved_locales' => ['en'],
            'placement' => AtharPlacement::About,
            'identity_display' => 'anonymous',
            'privacy_notice_version' => config('legal.privacy_version'),
            'verification_method' => 'link',
            'occurred_at' => now(),
        ]);

        $this->assertNotEmpty(AtharPublicProof::forPlacement(AtharPlacement::About, 'en'));

        app(HideAtharPublication::class)->handle($version);

        $this->assertSame(AtharPublicationStatus::Hidden, $version->fresh()->status);
        $this->assertSame([], AtharPublicProof::forPlacement(AtharPlacement::About, 'en'));

        app(UnhideAtharPublication::class)->handle($version->fresh());

        $this->assertSame(AtharPublicationStatus::Published, $version->fresh()->status);
        $this->assertNotEmpty(AtharPublicProof::forPlacement(AtharPlacement::About, 'en'));
    }

    public function test_admin_deletion_removes_private_message_data_without_removing_published_proof(): void
    {
        $invitation = AtharInvitation::factory()->create(['placement' => AtharPlacement::About]);
        $contribution = AtharContribution::factory()
            ->for($invitation, 'invitation')
            ->submitted()
            ->create(['draft_payload' => ['freeform' => 'A saved private draft.']]);
        $version = AtharPublicationVersion::factory()
            ->for($contribution, 'contribution')
            ->create([
                'status' => AtharPublicationStatus::Published,
                'placement' => AtharPlacement::About,
                'approved_locales' => ['en'],
            ]);

        $deleted = app(DeleteAtharPrivateMessage::class)->handle($contribution);

        $this->assertSame(AtharContributionStatus::DeletionRequested, $deleted->status);
        $this->assertNull($deleted->sealed_payload);
        $this->assertNull($deleted->draft_payload);
        $this->assertNull($deleted->source_hash);
        $this->assertNull($deleted->deleted_at);
        $this->assertModelExists($version);
    }

    public function test_retention_admin_can_permanently_delete_an_invitation_and_all_related_data(): void
    {
        $token = str_repeat('x', 64);
        $invitation = AtharInvitation::factory()->create([
            'placement' => AtharPlacement::About,
            'token_hash' => hash('sha256', $token),
        ]);
        $challenge = AtharAccessChallenge::factory()->for($invitation, 'invitation')->create();
        $contribution = AtharContribution::factory()
            ->for($invitation, 'invitation')
            ->submitted()
            ->create(['status' => AtharContributionStatus::Published]);
        $payload = ['en' => ['text' => 'A published proof to remove.', 'context' => '']];
        $version = AtharPublicationVersion::factory()
            ->for($contribution, 'contribution')
            ->create([
                'status' => AtharPublicationStatus::Published,
                'placement' => AtharPlacement::About,
                'approved_locales' => ['en'],
                'public_payload' => $payload,
                'snapshot_hash' => AtharPublicationSnapshot::hash($payload),
            ]);
        $consentEvent = AtharPublicationConsentEvent::factory()->create([
            'contribution_id' => $contribution->getKey(),
            'publication_version_id' => $version->getKey(),
            'event_type' => AtharConsentEventType::Approved,
            'snapshot_hash' => $version->snapshot_hash,
            'approved_locales' => ['en'],
            'placement' => AtharPlacement::About,
            'identity_display' => $version->identity_display,
        ]);
        $queuedNotifications = [
            new AtharInvitationNotification('https://ibrahimhasan.test/athar/invitation', 'en', $invitation->getKey()),
            new AtharApprovalNotification('https://ibrahimhasan.test/athar/approval', 'en', $invitation->getKey()),
            new AtharAccessCodeNotification('123456', 'en', $invitation->getKey()),
        ];

        $this->assertNotEmpty(AtharPublicProof::forPlacement(AtharPlacement::About, 'en'));

        app(DeleteAtharInvitation::class)->handle($invitation);

        $this->assertModelMissing($invitation);
        $this->assertModelMissing($challenge);
        $this->assertModelMissing($contribution);
        $this->assertModelMissing($version);
        $this->assertModelMissing($consentEvent);
        $this->assertSame([], AtharPublicProof::forPlacement(AtharPlacement::About, 'en'));
        $this->get(route('athar.show', ['token' => $token]))
            ->assertOk()
            ->assertSee(__('athar.unavailable.title'));

        foreach ($queuedNotifications as $notification) {
            $this->assertFalse($notification->shouldSend(new \stdClass, 'mail'));
        }
    }

    public function test_public_attribution_is_frozen_on_the_published_version(): void
    {
        $invitation = AtharInvitation::factory()->create([
            'placement' => AtharPlacement::About,
            'recipient_name' => 'Layla Hassan',
            'identity_display' => AtharIdentityDisplay::FullName,
        ]);
        $contribution = AtharContribution::factory()
            ->for($invitation, 'invitation')
            ->submitted()
            ->create(['status' => AtharContributionStatus::Published]);
        $version = AtharPublicationVersion::factory()
            ->for($contribution, 'contribution')
            ->create([
                'status' => AtharPublicationStatus::Published,
                'placement' => AtharPlacement::About,
                'identity_display' => AtharIdentityDisplay::FullName,
                'display_name' => 'Layla Hassan',
                'approved_locales' => ['en'],
                'public_payload' => ['en' => ['text' => 'Original published text.', 'context' => '', 'identity_display' => 'full_name', 'display_name' => 'Layla Hassan']],
            ]);
        $version->forceFill([
            'snapshot_hash' => AtharPublicationSnapshot::hash($version->public_payload),
        ])->save();
        $version->consentEvents()->create([
            'contribution_id' => $contribution->getKey(),
            'event_type' => 'approved',
            'snapshot_hash' => $version->snapshot_hash,
            'approved_locales' => ['en'],
            'placement' => AtharPlacement::About,
            'identity_display' => AtharIdentityDisplay::FullName,
            'privacy_notice_version' => config('legal.privacy_version'),
            'verification_method' => 'link',
            'occurred_at' => now(),
        ]);

        $proof = AtharPublicProof::forPlacement(AtharPlacement::About, 'en');
        $this->assertSame('Original published text.', $proof[0]['text']);
        $this->assertSame('Layla Hassan', $proof[0]['name']);

        $invitation->forceFill(['recipient_name' => 'Changed after consent', 'identity_display' => AtharIdentityDisplay::Anonymous])->save();
        $proof = AtharPublicProof::forPlacement(AtharPlacement::About, 'en');
        $this->assertSame('Layla Hassan', $proof[0]['name']);
    }

    public function test_admins_cannot_edit_a_contributor_approved_publication_version(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $reviewer = User::factory()->create();
        $reviewer->givePermissionTo(Permission::firstOrCreate(['name' => 'review athar_publications', 'guard_name' => 'web']));
        $version = AtharPublicationVersion::factory()->create();

        $this->assertFalse(app(AtharPublicationVersionPolicy::class)->update($reviewer, $version));
        $this->assertFileDoesNotExist(app_path('Actions/Athar/EditAtharPublicationVersion.php'));
    }

    public function test_update_policy_requires_the_dedicated_update_permission_not_review(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $creator = User::factory()->create();
        $creator->givePermissionTo(Permission::firstOrCreate(['name' => 'create athar_invitations', 'guard_name' => 'web']));

        $reviewer = User::factory()->create();
        $reviewer->givePermissionTo(Permission::firstOrCreate(['name' => 'review athar_publications', 'guard_name' => 'web']));

        $editor = User::factory()->create();
        $editor->givePermissionTo(Permission::firstOrCreate(['name' => 'update athar_invitations', 'guard_name' => 'web']));

        $invitation = AtharInvitation::factory()->create(['created_by' => $creator->getKey()]);
        $policy = app(AtharInvitationPolicy::class);

        $this->assertFalse($policy->update($reviewer, $invitation), 'reviewers must not edit invitations');
        $this->assertTrue($policy->update($editor, $invitation), 'editors must edit invitations');
    }

    public function test_revoke_policy_requires_the_revoke_permission(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $admin = User::factory()->create();
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'revoke athar_invitations', 'guard_name' => 'web']));
        $invitation = AtharInvitation::factory()->create();

        $policy = app(AtharInvitationPolicy::class);
        $this->assertTrue($policy->revoke($admin, $invitation));
    }

    public function test_private_message_deletion_policy_requires_retention_permission(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $admin = User::factory()->create();
        $invitation = AtharInvitation::factory()->create();
        AtharContribution::factory()->for($invitation, 'invitation')->create();
        $policy = app(AtharInvitationPolicy::class);

        $this->assertFalse($policy->deletePrivateMessage($admin, $invitation));

        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage athar_retention', 'guard_name' => 'web']));

        $this->assertTrue($policy->deletePrivateMessage($admin, $invitation));
    }

    public function test_permanent_invitation_deletion_policy_requires_retention_permission(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $admin = User::factory()->create();
        $invitation = AtharInvitation::factory()->create();
        $policy = app(AtharInvitationPolicy::class);

        $this->assertFalse($policy->purge($admin, $invitation));

        $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'manage athar_retention', 'guard_name' => 'web']));

        $this->assertTrue($policy->purge($admin, $invitation));
    }

    public function test_retention_admin_can_permanently_delete_an_invitation_from_the_admin_table(): void
    {
        $this->seed(PermissionSeeder::class);

        $role = Role::create(['name' => 'athar retention admin', 'guard_name' => 'web']);
        $role->syncPermissions([
            Permission::firstOrCreate(['name' => 'view_any athar_invitations', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'view athar_invitations', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'manage athar_retention', 'guard_name' => 'web']),
        ]);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $invitation = AtharInvitation::factory()->create();
        $this->bootAdminPanel();

        Livewire::actingAs($admin)
            ->test(ListAtharInvitations::class)
            ->assertTableActionVisible('delete_permanently', $invitation)
            ->callTableAction('delete_permanently', $invitation);

        $this->assertModelMissing($invitation);
    }

    private function bootAdminPanel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }
}
