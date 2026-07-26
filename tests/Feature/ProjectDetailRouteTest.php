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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDetailRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_case_studies_resolve_by_localized_slug_and_keep_private_references_out_of_the_public_page(): void
    {
        $project = $this->eligibleProject();
        $service = Service::factory()->create();
        $project->services()->attach($service, ['sort_order' => 10]);
        ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'kind' => ProjectEvidenceKind::Qualitative,
            'state' => ProjectEvidenceState::Approved,
            'is_public' => true,
            'label' => ['ar' => 'ملاحظة ميدانية', 'en' => 'Field observation'],
            'result_text' => ['ar' => 'أصبح سير العمل أوضح للفريق.', 'en' => 'The workflow became clearer for the team.'],
            'source_owner' => 'PRIVATE SOURCE OWNER',
            'source_reference' => 'PRIVATE SOURCE REFERENCE',
            'permission_reference' => 'PRIVATE EVIDENCE PERMISSION',
        ]);

        $arabicUrl = localized_route('work.show', ['project' => $project], locale: 'ar');
        $englishUrl = localized_route('work.show', ['project' => $project], locale: 'en');

        $this->get($arabicUrl)
            ->assertOk()
            ->assertSee('دراسة حالة', false)
            ->assertSee('ملخص تنفيذي يوضح نطاق العمل.', false)
            ->assertSee('ملاحظة ميدانية', false)
            ->assertSee('hreflang="en" href="'.$englishUrl.'"', false)
            ->assertSee('"@type":"CreativeWork"', false)
            ->assertSee(localized_route('services.show', ['service' => $service], locale: 'ar'), false)
            ->assertDontSee('PRIVATE SOURCE OWNER', false)
            ->assertDontSee('PRIVATE SOURCE REFERENCE', false)
            ->assertDontSee('PRIVATE EVIDENCE PERMISSION', false)
            ->assertDontSee('PRIVATE PROJECT PERMISSION', false)
            ->assertDontSee('site-footer__cta', false);

        $this->get($englishUrl)
            ->assertOk()
            ->assertSee('Case study', false)
            ->assertSee('An executive summary of the work.', false)
            ->assertSee('hreflang="ar" href="'.$arabicUrl.'"', false);
        $this->get('/work/'.$project->getTranslation('slug', 'en'))->assertNotFound();
    }

    public function test_incomplete_inactive_and_non_detailed_projects_cannot_resolve_as_public_case_studies(): void
    {
        $notDetailed = $this->eligibleProject(['is_detailed_case_study' => false]);
        $inactive = $this->eligibleProject(['is_active' => false]);
        $incomplete = $this->eligibleProject(['case_study_sections' => ['ar' => []]]);

        foreach ([$notDetailed, $inactive, $incomplete] as $project) {
            $this->get(localized_route('work.show', ['project' => $project], locale: 'ar'))
                ->assertNotFound();
        }
    }

    public function test_revoked_assets_are_withheld_without_withdrawing_an_otherwise_approved_case_study(): void
    {
        $project = $this->eligibleProject([
            'image' => 'images/projects/private-image.webp',
            'image_alt' => ['ar' => 'وصف خاص للصورة', 'en' => 'Private image description'],
            'image_permission_status' => ProjectAssetPermissionStatus::Revoked,
        ]);

        $this->get(localized_route('work.show', ['project' => $project], locale: 'ar'))
            ->assertOk()
            ->assertDontSee('private-image.webp', false)
            ->assertDontSee('وصف خاص للصورة', false);
    }

    public function test_anonymized_cases_suppress_media_relationships_and_structured_data(): void
    {
        $project = $this->eligibleProject([
            'permission_status' => ProjectPermissionStatus::ApprovedAnonymized,
            'disclosure_level' => ProjectDisclosureLevel::Anonymized,
            'image' => 'images/projects/anonymous-source.webp',
            'logo' => 'images/brands/projects/anonymous-source.webp',
        ]);
        $service = Service::factory()->create();
        $project->services()->attach($service, ['sort_order' => 10]);

        $this->get(localized_route('work.show', ['project' => $project], locale: 'ar'))
            ->assertOk()
            ->assertDontSee('anonymous-source.webp', false)
            ->assertDontSee('"@type":"CreativeWork"', false)
            ->assertDontSee(__('site.work.delivery_entity'), false)
            ->assertDontSee(__('site.work.related_services'), false)
            ->assertDontSee(localized_route('services.show', ['service' => $service], locale: 'ar'), false)
            ->assertDontSee('PRIVATE PROJECT PERMISSION', false);
    }

    public function test_work_overview_links_only_projects_that_pass_the_complete_case_study_gate(): void
    {
        $eligible = $this->eligibleProject();
        $notDetailed = $this->eligibleProject(['is_detailed_case_study' => false]);

        $this->get('/work')
            ->assertOk()
            ->assertSee(localized_route('work.show', ['project' => $eligible], locale: 'ar'), false)
            ->assertDontSee(localized_route('work.show', ['project' => $notDetailed], locale: 'ar'), false);
    }

    /** @param array<string, mixed> $overrides */
    private function eligibleProject(array $overrides = []): Project
    {
        $suffix = (string) fake()->unique()->numberBetween(1000, 999999);
        $sections = [
            'ar' => [
                'executive_summary' => 'ملخص تنفيذي يوضح نطاق العمل.',
                'context' => 'السياق التشغيلي للمشروع.',
                'constraints' => ['تعدد أصحاب القرار'],
                'changes' => [['area' => 'workflow', 'body' => 'أعيد تنظيم مسار العمل.']],
                'solution' => 'حل عملي مناسب للسياق.',
                'implementation' => 'نُفذ الحل على مراحل واضحة.',
                'adoption' => 'تلقى الفريق دعماً للتبنّي.',
                'lessons' => ['وضوح المسؤولية يسرّع القرار.'],
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
            'key' => 'case-study-'.$suffix,
            'slug' => ['ar' => 'دراسة-'.$suffix, 'en' => 'case-study-'.$suffix],
            'title' => ['ar' => 'مشروع تجريبي محكوم', 'en' => 'A governed example project'],
            'is_detailed_case_study' => true,
            'is_active' => true,
            'delivery_entity' => ProjectDeliveryEntity::Direct,
            'delivery_period' => ['ar' => '2025', 'en' => '2025'],
            'ibrahim_role' => ['ar' => 'قيادة التنفيذ', 'en' => 'Delivery leadership'],
            'confidentiality_note' => ['ar' => 'نُشرت التفاصيل بموافقة واضحة.', 'en' => 'The details are published with clear permission.'],
            'case_study_sections' => $sections,
            'case_study_reviewed_at' => now(),
            'permission_status' => ProjectPermissionStatus::ApprovedNamed,
            'permission_reference' => 'PRIVATE PROJECT PERMISSION',
            'disclosure_level' => ProjectDisclosureLevel::Named,
            'evidence_level' => ProjectEvidenceLevel::Qualitative,
            'image' => 'images/projects/approved-example.webp',
            'image_permission_status' => ProjectAssetPermissionStatus::Approved,
            'image_permission_reference' => 'PRIVATE IMAGE PERMISSION',
            'logo' => 'images/brands/projects/approved-example.webp',
            'logo_permission_status' => ProjectAssetPermissionStatus::Approved,
            'logo_permission_reference' => 'PRIVATE LOGO PERMISSION',
        ], $overrides));
    }
}
