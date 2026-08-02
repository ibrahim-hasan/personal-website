<?php

namespace Tests\Feature;

use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDeliveryEntity;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectEvidenceKind;
use App\Enums\ProjectEvidenceLevel;
use App\Enums\ProjectEvidenceState;
use App\Enums\ProjectPermissionStatus;
use App\Models\Project;
use App\Models\ProjectEvidence;
use App\Models\Service;
use App\Models\User;
use App\Services\Projects\ProjectCaseStudyPublicationValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectCaseStudyPublicationValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_named_case_study_with_approved_assets_and_evidence_is_eligible(): void
    {
        $project = $this->eligibleProject([
            'evidence_level' => ProjectEvidenceLevel::VerifiedQuantitative,
        ]);
        $reviewer = User::factory()->create();
        $evidence = ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'kind' => ProjectEvidenceKind::Exact,
            'state' => ProjectEvidenceState::Approved,
            'is_public' => true,
            'baseline_value' => 12.5,
            'result_value' => 32.5,
            'unit' => '%',
            'baseline_period' => ['ar' => 'قبل التطبيق', 'en' => 'Before implementation'],
            'result_period' => ['ar' => 'بعد التطبيق', 'en' => 'After implementation'],
            'method' => ['ar' => 'مراجعة السجلات', 'en' => 'Record review'],
            'scope' => ['ar' => 'مسار واحد', 'en' => 'One workflow'],
            'source_owner' => 'Project owner',
            'source_reference' => 'Evidence source',
            'permission_reference' => 'Permission record',
            'verified_by' => $reviewer->getKey(),
            'approved_by' => $reviewer->getKey(),
            'verified_at' => now()->subDay(),
            'approved_at' => now(),
        ]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertTrue($eligibility->isEligible(), implode(', ', $eligibility->violations()));
        $this->assertSame([$evidence->getKey()], $eligibility->publicEvidence()->pluck('id')->all());
        $this->assertTrue($eligibility->mayRenderImage());
        $this->assertTrue($eligibility->mayRenderLogo());
    }

    public function test_detailed_active_reviewed_state_and_complete_bilingual_content_are_required(): void
    {
        $project = $this->eligibleProject([
            'is_detailed_case_study' => false,
            'is_active' => false,
            'case_study_reviewed_at' => null,
            'summary' => ['ar' => 'ملخص عربي مكتمل'],
            'case_study_sections' => ['ar' => []],
        ]);
        $project->delete();

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertFalse($eligibility->isEligible());
        $this->assertTrue($eligibility->hasViolation('project.not_detailed'));
        $this->assertTrue($eligibility->hasViolation('project.inactive'));
        $this->assertTrue($eligibility->hasViolation('project.deleted'));
        $this->assertTrue($eligibility->hasViolation('project.review_missing'));
        $this->assertTrue($eligibility->hasViolation('translation.en.summary.missing'));
        $this->assertTrue($eligibility->hasViolation('sections.ar.executive_summary.missing'));
        $this->assertTrue($eligibility->hasViolation('sections.en.missing'));
    }

    public function test_anonymized_permission_requires_anonymized_disclosure_and_a_private_permission_reference(): void
    {
        $project = $this->eligibleProject([
            'permission_status' => ProjectPermissionStatus::ApprovedAnonymized,
            'disclosure_level' => ProjectDisclosureLevel::Named,
            'permission_reference' => null,
        ]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertFalse($eligibility->isEligible());
        $this->assertTrue($eligibility->hasViolation('project.disclosure_incompatible'));
        $this->assertTrue($eligibility->hasViolation('project.permission_reference_missing'));

        $project->forceFill([
            'disclosure_level' => ProjectDisclosureLevel::Anonymized,
            'permission_reference' => 'Anonymized approval reference',
        ])->save();

        $this->assertTrue(app(ProjectCaseStudyPublicationValidator::class)->validate($project)->isEligible());
    }

    public function test_revoked_assets_are_suppressed_without_withdrawing_an_otherwise_publishable_case_study(): void
    {
        $project = $this->eligibleProject([
            'image_permission_status' => ProjectAssetPermissionStatus::Revoked,
        ]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertTrue($eligibility->isEligible());
        $this->assertFalse($eligibility->mayRenderImage());
        $this->assertTrue($eligibility->mayRenderLogo());
    }

    public function test_approved_assets_require_private_permission_references_before_they_can_render(): void
    {
        $project = $this->eligibleProject([
            'logo_permission_reference' => null,
        ]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertFalse($eligibility->isEligible());
        $this->assertTrue($eligibility->hasViolation('logo.permission_reference_missing'));
        $this->assertFalse($eligibility->mayRenderLogo());
    }

    public function test_only_approved_public_evidence_is_exposed_and_public_drafts_block_publication(): void
    {
        $project = $this->eligibleProject();
        $approved = ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'state' => ProjectEvidenceState::Approved,
            'is_public' => true,
            'kind' => ProjectEvidenceKind::Qualitative,
        ]);
        $draft = ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'state' => ProjectEvidenceState::Draft,
            'is_public' => true,
        ]);
        ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'state' => ProjectEvidenceState::Approved,
            'is_public' => false,
        ]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertFalse($eligibility->isEligible());
        $this->assertSame([$approved->getKey()], $eligibility->publicEvidence()->pluck('id')->all());
        $this->assertTrue($eligibility->hasViolation('evidence.'.$draft->getKey().'.public_not_approved'));
    }

    public function test_evidence_level_alignment_rejects_quantitative_evidence_for_qualitative_cases(): void
    {
        $project = $this->eligibleProject([
            'evidence_level' => ProjectEvidenceLevel::Qualitative,
        ]);
        $evidence = ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'kind' => ProjectEvidenceKind::Exact,
            'state' => ProjectEvidenceState::Approved,
            'is_public' => true,
        ]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertFalse($eligibility->isEligible());
        $this->assertTrue($eligibility->hasViolation('evidence.level.qualitative_requires_qualitative_only'));
        $this->assertTrue($eligibility->hasViolation('evidence.'.$evidence->getKey().'.unit_missing'));
    }

    public function test_qualitative_evidence_cannot_present_numeric_claims_as_unmeasured_proof(): void
    {
        $project = $this->eligibleProject();
        $evidence = ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'kind' => ProjectEvidenceKind::Qualitative,
            'state' => ProjectEvidenceState::Approved,
            'is_public' => true,
            'result_text' => ['ar' => 'تحسن ٥ مسارات عمل.', 'en' => 'Five workflows improved.'],
        ]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertFalse($eligibility->isEligible());
        $this->assertTrue($eligibility->hasViolation('evidence.'.$evidence->getKey().'.qualitative_contains_number'));
    }

    public function test_documented_cases_require_at_least_one_approved_public_evidence_record(): void
    {
        $project = $this->eligibleProject([
            'evidence_level' => ProjectEvidenceLevel::Documented,
        ]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertFalse($eligibility->isEligible());
        $this->assertTrue($eligibility->hasViolation('evidence.level.documented_requires_public_evidence'));
    }

    public function test_documented_cases_accept_approved_public_evidence_with_a_private_source_and_permission_record(): void
    {
        $project = $this->eligibleProject([
            'evidence_level' => ProjectEvidenceLevel::Documented,
        ]);
        ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'kind' => ProjectEvidenceKind::Qualitative,
            'state' => ProjectEvidenceState::Approved,
            'is_public' => true,
            'source_owner' => 'Project owner',
            'source_reference' => 'Documented source',
            'permission_reference' => 'Permission record',
        ]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertTrue($eligibility->isEligible(), implode(', ', $eligibility->violations()));
    }

    public function test_verified_quantitative_cases_require_complete_approved_quantitative_evidence(): void
    {
        $project = $this->eligibleProject([
            'evidence_level' => ProjectEvidenceLevel::VerifiedQuantitative,
        ]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertFalse($eligibility->isEligible());
        $this->assertTrue($eligibility->hasViolation('evidence.level.verified_quantitative_requires_quantitative_evidence'));
    }

    public function test_selected_services_must_be_publicly_publishable_before_a_case_study_can_publish(): void
    {
        $project = $this->eligibleProject();
        $incompleteService = Service::factory()->create([
            'key' => 'incomplete-related-service',
            'summary' => ['ar' => '', 'en' => 'An incomplete public service summary.'],
        ]);
        $project->services()->attach($incompleteService, ['sort_order' => 10]);

        $eligibility = app(ProjectCaseStudyPublicationValidator::class)->validate($project);

        $this->assertFalse($eligibility->isEligible());
        $this->assertTrue($eligibility->hasViolation('relation.service.incomplete-related-service.not_public'));
    }

    /** @param array<string, mixed> $attributes */
    private function eligibleProject(array $attributes = []): Project
    {
        $sections = [
            'ar' => [
                'executive_summary' => 'ملخص تنفيذي يوضح نطاق العمل.',
                'context' => 'السياق التشغيلي للمشروع.',
                'constraints' => ['تعدد أصحاب القرار'],
                'changes' => [['area' => 'workflow', 'body' => 'أعيد تنظيم مسار العمل.']],
                'solution' => 'حل عملي مناسب للسياق.',
                'implementation' => 'نُفذ الحل على مراحل واضحة.',
                'adoption' => 'تلقى الفريق دعماً للتبني.',
                'lessons' => ['وضوح المسؤولية يسرع القرار.'],
            ],
            'en' => [
                'executive_summary' => 'An executive summary of the work.',
                'context' => 'The operational context for the project.',
                'constraints' => ['Several decision makers'],
                'changes' => [['area' => 'workflow', 'body' => 'The workflow was reorganized.']],
                'solution' => 'A practical solution for the context.',
                'implementation' => 'The solution was delivered in clear phases.',
                'adoption' => 'The team received adoption support.',
                'lessons' => ['Clear ownership speeds up decisions.'],
            ],
        ];

        return Project::factory()->create(array_merge([
            'is_detailed_case_study' => true,
            'is_active' => true,
            'delivery_entity' => ProjectDeliveryEntity::Direct,
            'delivery_period' => ['ar' => '2025', 'en' => '2025'],
            'ibrahim_role' => ['ar' => 'دور قيادي في التنفيذ', 'en' => 'A delivery leadership role'],
            'confidentiality_note' => ['ar' => 'نُشرت التفاصيل بموافقة واضحة.', 'en' => 'The details are published with clear permission.'],
            'case_study_sections' => $sections,
            'case_study_reviewed_at' => now(),
            'permission_status' => ProjectPermissionStatus::ApprovedNamed,
            'permission_reference' => 'Named approval reference',
            'disclosure_level' => ProjectDisclosureLevel::Named,
            'evidence_level' => ProjectEvidenceLevel::Qualitative,
            'image_permission_status' => ProjectAssetPermissionStatus::Approved,
            'image_permission_reference' => 'Image permission record',
            'logo' => 'images/brands/projects/example.webp',
            'logo_permission_status' => ProjectAssetPermissionStatus::Approved,
            'logo_permission_reference' => 'Logo permission record',
        ], $attributes));
    }
}
