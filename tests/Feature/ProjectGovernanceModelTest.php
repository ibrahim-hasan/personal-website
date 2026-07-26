<?php

namespace Tests\Feature;

use App\Enums\ProjectEvidenceState;
use App\Enums\ProjectPermissionStatus;
use App\Models\ContactInquiry;
use App\Models\Project;
use App\Models\ProjectEvidence;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectGovernanceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_governance_defaults_are_conservative(): void
    {
        $project = Project::factory()->create();

        $this->assertSame(ProjectPermissionStatus::Unreviewed, $project->permission_status);
        $this->assertFalse($project->is_detailed_case_study);
        $this->assertSame('unreviewed', $project->image_permission_status?->value);
        $this->assertSame('unreviewed', $project->logo_permission_status?->value);
    }

    public function test_project_private_permission_references_are_encrypted_and_never_serialize(): void
    {
        $project = Project::factory()->create([
            'permission_reference' => 'restricted project permission',
            'image_permission_reference' => 'restricted image permission',
            'logo_permission_reference' => 'restricted logo permission',
        ]);

        $rawPermissionReference = DB::table('projects')
            ->where('id', $project->getKey())
            ->value('permission_reference');

        $this->assertSame('restricted project permission', $project->permission_reference);
        $this->assertNotSame('restricted project permission', $rawPermissionReference);
        $this->assertArrayNotHasKey('permission_reference', $project->toArray());
        $this->assertArrayNotHasKey('image_permission_reference', $project->toArray());
        $this->assertArrayNotHasKey('logo_permission_reference', $project->toArray());
    }

    public function test_project_evidence_private_references_are_encrypted_and_public_scope_is_strict(): void
    {
        $project = Project::factory()->create();
        $approved = ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'sort_order' => 2,
            'state' => ProjectEvidenceState::Approved,
            'is_public' => true,
            'source_reference' => 'restricted-source-reference',
            'permission_reference' => 'restricted-permission-reference',
        ]);
        ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'sort_order' => 1,
            'state' => ProjectEvidenceState::Draft,
            'is_public' => true,
        ]);
        ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'sort_order' => 3,
            'state' => ProjectEvidenceState::Approved,
            'is_public' => false,
        ]);

        $rawReference = DB::table('project_evidence')
            ->where('id', $approved->getKey())
            ->value('source_reference');

        $this->assertSame('restricted-source-reference', $approved->source_reference);
        $this->assertNotSame('restricted-source-reference', $rawReference);
        $this->assertSame([$approved->getKey()], ProjectEvidence::query()->publicApproved()->pluck('id')->all());
        $this->assertArrayNotHasKey('source_reference', $approved->toArray());
        $this->assertArrayNotHasKey('permission_reference', $approved->toArray());
    }

    public function test_service_and_project_relations_keep_the_explicit_pivot_order(): void
    {
        $project = Project::factory()->create();
        $firstService = Service::factory()->create(['key' => 'first-service']);
        $secondService = Service::factory()->create(['key' => 'second-service']);

        $project->services()->attach([
            $secondService->getKey() => ['sort_order' => 20],
            $firstService->getKey() => ['sort_order' => 10],
        ]);

        $this->assertSame(
            ['first-service', 'second-service'],
            $project->services()->pluck('key')->all(),
        );
        $this->assertSame([$project->getKey()], $firstService->projects()->pluck('projects.id')->all());
    }

    public function test_service_public_arrays_never_fill_a_missing_locale_from_another_language(): void
    {
        $service = Service::factory()->create([
            'deliverables' => [
                ['ar' => 'مخرج عربي معتمد'],
            ],
            'fit_signals' => [
                'ar' => ['إشارة مناسبة بالعربية'],
            ],
        ]);

        $english = $service->toPublicArray('en');

        $this->assertSame([], $english['deliverables']);
        $this->assertSame([], $english['fit_signals']);
    }

    public function test_contact_submission_hash_is_hidden_while_new_qualification_fields_are_persisted(): void
    {
        $inquiry = ContactInquiry::factory()->create([
            'public_reference' => 'IH-2K7M9CP3W4DX',
            'submission_hash' => hash('sha256', 'session-token'),
            'role' => 'Operations lead',
            'timing' => 'Within six weeks',
        ]);

        $this->assertSame('Operations lead', $inquiry->role);
        $this->assertSame('Within six weeks', $inquiry->timing);
        $this->assertSame('IH-2K7M9CP3W4DX', $inquiry->public_reference);
        $this->assertArrayNotHasKey('submission_hash', $inquiry->toArray());
    }
}
