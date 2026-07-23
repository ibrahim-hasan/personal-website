<?php

namespace Tests\Feature;

use App\Actions\Athar\EditAtharPublicationVersion;
use App\Actions\Athar\ExpireAtharInvitations;
use App\Actions\Athar\HideAtharPublication;
use App\Actions\Athar\RevokeAtharInvitation;
use App\Actions\Athar\UnhideAtharPublication;
use App\Enums\AtharContributionStatus;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPlacement;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharContribution;
use App\Models\AtharInvitation;
use App\Models\AtharPublicationVersion;
use App\Models\User;
use App\Policies\AtharInvitationPolicy;
use App\Support\AtharPublicProof;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

        $count = app(ExpireAtharInvitations::class)->handle();

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('athar_invitations', ['id' => $stillValid->getKey(), 'status' => AtharInvitationStatus::Sent->value]);
        $this->assertDatabaseHas('athar_invitations', ['id' => $revoked->getKey(), 'status' => AtharInvitationStatus::Sent->value]);
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
        $version->consentEvents()->create([
            'contribution_id' => $contribution->getKey(),
            'event_type' => 'approved',
            'snapshot_hash' => $version->snapshot_hash,
            'approved_locales' => ['en'],
            'placement' => AtharPlacement::About,
            'identity_display' => 'anonymous',
            'privacy_notice_version' => '2026-07-22',
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

    public function test_edit_updates_the_published_text_and_keeps_the_snapshot_in_sync(): void
    {
        $invitation = AtharInvitation::factory()->create([
            'placement' => AtharPlacement::About,
            'recipient_name' => 'Layla Hassan',
            'identity_display' => AtharIdentityDisplay::Anonymous,
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
                'identity_display' => AtharIdentityDisplay::Anonymous,
                'approved_locales' => ['en'],
                'public_payload' => ['en' => ['text' => 'Original published text.', 'context' => '']],
            ]);

        app(EditAtharPublicationVersion::class)->handle($version, [
            'text' => 'Edited published text.',
        ]);

        $version->refresh();
        $this->assertSame('Edited published text.', $version->public_payload['en']['text']);
        $this->assertSame(
            hash('sha256', json_encode($version->public_payload, JSON_UNESCAPED_UNICODE)),
            $version->snapshot_hash,
            'snapshot_hash must match the edited payload',
        );

        $proof = AtharPublicProof::forPlacement(AtharPlacement::About, 'en');
        $this->assertSame('Edited published text.', $proof[0]['text']);
        $this->assertSame('', $proof[0]['name'], 'anonymous invitation hides the name');

        // The writer name is controlled live by the invitation's identity_display.
        $invitation->forceFill(['identity_display' => AtharIdentityDisplay::FullName])->save();
        $proof = AtharPublicProof::forPlacement(AtharPlacement::About, 'en');
        $this->assertSame('Layla Hassan', $proof[0]['name'], 'the writer name should appear once the invitation allows it');
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
}
