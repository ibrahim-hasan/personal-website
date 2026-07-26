<?php

namespace Tests\Feature;

use App\Actions\Projects\TransitionProjectEvidence;
use App\Enums\ProjectEvidenceKind;
use App\Enums\ProjectEvidenceState;
use App\Models\Project;
use App\Models\ProjectEvidence;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectEvidenceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    public function test_dedicated_project_publication_and_evidence_approval_permissions_are_seeded_and_authorized(): void
    {
        $project = Project::factory()->create();
        $evidence = ProjectEvidence::factory()->create();
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $admin = $this->admin();

        $this->assertFalse($editor->can('publish', $project));
        $this->assertFalse($editor->can('approve', $evidence));
        $this->assertFalse($editor->can('setPublicVisibility', $evidence));

        $this->assertTrue($admin->can('publish', $project));
        $this->assertTrue($admin->can('verify', $evidence));
        $this->assertTrue($admin->can('approve', $evidence));
        $this->assertTrue($admin->can('revoke', $evidence));
        $this->assertTrue($admin->can('setPublicVisibility', $evidence));
    }

    public function test_editor_can_return_rejected_evidence_to_draft_but_cannot_review_it(): void
    {
        $reviewer = $this->admin();
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $evidence = $this->quantitativeEvidence();
        $workflow = app(TransitionProjectEvidence::class);

        $rejected = $workflow->reject($reviewer, $evidence);

        $this->assertSame(ProjectEvidenceState::Rejected, $rejected->state);
        $this->assertFalse($rejected->is_public);

        $draft = $workflow->draft($editor, $rejected);

        $this->assertSame(ProjectEvidenceState::Draft, $draft->state);
        $this->assertFalse($draft->is_public);

        try {
            $workflow->verify($editor, $draft);
            $this->fail('An editor without evidence approval permission must not verify evidence.');
        } catch (AuthorizationException) {
            $this->assertSame(ProjectEvidenceState::Draft, $draft->fresh()->state);
        }
    }

    public function test_authorized_reviewer_can_verify_approve_and_make_valid_evidence_public_without_exposing_private_references(): void
    {
        $reviewer = $this->admin();
        $evidence = $this->quantitativeEvidence();
        $workflow = app(TransitionProjectEvidence::class);

        $verified = $workflow->verify($reviewer, $evidence);

        $this->assertSame(ProjectEvidenceState::Verified, $verified->state);
        $this->assertSame($reviewer->getKey(), $verified->verified_by);
        $this->assertNotNull($verified->verified_at);
        $this->assertFalse($verified->is_public);

        $approved = $workflow->approve($reviewer, $verified);

        $this->assertSame(ProjectEvidenceState::Approved, $approved->state);
        $this->assertSame($reviewer->getKey(), $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
        $this->assertFalse($approved->is_public);

        $public = $workflow->setPublicVisibility($reviewer, $approved, true);

        $this->assertTrue($public->is_public);
        $this->assertSame(1, ProjectEvidence::query()->publicApproved()->count());
        $this->assertArrayNotHasKey('source_owner', $public->toArray());
        $this->assertArrayNotHasKey('source_reference', $public->toArray());
        $this->assertArrayNotHasKey('permission_reference', $public->toArray());
    }

    public function test_approval_requires_verified_evidence(): void
    {
        $reviewer = $this->admin();
        $evidence = $this->quantitativeEvidence();

        try {
            app(TransitionProjectEvidence::class)->approve($reviewer, $evidence);
            $this->fail('Draft evidence must be verified before approval.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('state', $exception->errors());
            $this->assertSame(ProjectEvidenceState::Draft, $evidence->fresh()->state);
            $this->assertFalse($evidence->fresh()->is_public);
        }
    }

    public function test_verification_rejects_missing_private_references_without_disclosing_existing_private_data(): void
    {
        $reviewer = $this->admin();
        $privatePermissionReference = 'permission-reference-that-must-remain-private';
        $evidence = $this->quantitativeEvidence([
            'source_reference' => null,
            'permission_reference' => $privatePermissionReference,
        ]);

        try {
            app(TransitionProjectEvidence::class)->verify($reviewer, $evidence);
            $this->fail('Quantitative evidence without a source reference must not be verified.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('source_reference', $exception->errors());
            $this->assertStringNotContainsString(
                $privatePermissionReference,
                implode(' ', array_merge(...array_values($exception->errors()))),
            );
            $this->assertSame(ProjectEvidenceState::Draft, $evidence->fresh()->state);
        }
    }

    public function test_super_admin_authorization_does_not_bypass_evidence_validation(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $evidence = $this->quantitativeEvidence(['unit' => null]);

        try {
            app(TransitionProjectEvidence::class)->verify($superAdmin, $evidence);
            $this->fail('A super administrator must not verify evidence with incomplete measurement data.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('unit', $exception->errors());
            $this->assertSame(ProjectEvidenceState::Draft, $evidence->fresh()->state);
        }
    }

    public function test_public_visibility_revalidates_approved_evidence_before_exposure(): void
    {
        $reviewer = $this->admin();
        $workflow = app(TransitionProjectEvidence::class);
        $approved = $workflow->approve($reviewer, $workflow->verify($reviewer, $this->quantitativeEvidence()));
        $approved->forceFill(['source_reference' => null])->save();

        try {
            $workflow->setPublicVisibility($reviewer, $approved, true);
            $this->fail('An approved record with a missing private source reference must not become public.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('source_reference', $exception->errors());
            $this->assertFalse($approved->fresh()->is_public);
        }
    }

    public function test_revocation_immediately_hides_public_evidence_and_cannot_be_reversed_by_visibility_toggle(): void
    {
        $reviewer = $this->admin();
        $workflow = app(TransitionProjectEvidence::class);
        $approved = $workflow->approve($reviewer, $workflow->verify($reviewer, $this->quantitativeEvidence()));
        $public = $workflow->setPublicVisibility($reviewer, $approved, true);

        $revoked = $workflow->revoke($reviewer, $public);

        $this->assertSame(ProjectEvidenceState::Revoked, $revoked->state);
        $this->assertFalse($revoked->is_public);
        $this->assertNotNull($revoked->revoked_at);
        $this->assertSame(0, ProjectEvidence::query()->publicApproved()->count());

        try {
            $workflow->setPublicVisibility($reviewer, $revoked, true);
            $this->fail('Revoked evidence must not become public through a visibility toggle.');
        } catch (ValidationException) {
            $this->assertFalse($revoked->fresh()->is_public);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function quantitativeEvidence(array $attributes = []): ProjectEvidence
    {
        return ProjectEvidence::factory()->create(array_merge([
            'kind' => ProjectEvidenceKind::Exact,
            'label' => [
                'ar' => 'تحسن زمن إتمام الطلبات',
                'en' => 'Faster request completion',
            ],
            'result_text' => [
                'ar' => 'انخفض الوقت اللازم لإتمام الطلبات في المسار المتفق عليه.',
                'en' => 'Completion time fell in the agreed workflow.',
            ],
            'baseline_value' => 14,
            'result_value' => 9,
            'unit' => 'days',
            'direction' => 'decrease',
            'baseline_period' => [
                'ar' => 'قبل التطبيق',
                'en' => 'Before implementation',
            ],
            'result_period' => [
                'ar' => 'بعد التطبيق',
                'en' => 'After implementation',
            ],
            'method' => [
                'ar' => 'مراجعة سجلات التشغيل',
                'en' => 'Operational record review',
            ],
            'scope' => [
                'ar' => 'المسار المتفق عليه',
                'en' => 'The agreed workflow',
            ],
            'source_owner' => 'Authorized source owner',
            'source_reference' => 'Private source reference',
            'permission_reference' => 'Private permission reference',
            'state' => ProjectEvidenceState::Draft,
            'is_public' => false,
        ], $attributes));
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }
}
