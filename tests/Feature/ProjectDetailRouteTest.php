<?php

namespace Tests\Feature;

use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDeliveryEntity;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectEvidenceLevel;
use App\Enums\ProjectPermissionStatus;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDetailRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_project_detail_urls_redirect_to_the_matching_localized_work_card(): void
    {
        $project = $this->eligibleProject();

        $this->get(localized_route('work.show', ['project' => $project], locale: 'ar'))
            ->assertRedirect(localized_route('work', locale: 'ar').'#project-'.$project->key);

        $this->get(localized_route('work.show', ['project' => $project], locale: 'en'))
            ->assertRedirect(localized_route('work', locale: 'en').'#project-'.$project->key);
    }

    public function test_inactive_projects_do_not_resolve_through_the_retired_detail_url(): void
    {
        $project = $this->eligibleProject(['is_active' => false]);

        $this->get(localized_route('work.show', ['project' => $project], locale: 'ar'))
            ->assertNotFound();
    }

    public function test_work_overview_keeps_links_only_for_projects_that_pass_the_existing_case_study_gate(): void
    {
        $eligible = $this->eligibleProject();
        $notDetailed = $this->eligibleProject(['is_detailed_case_study' => false]);

        $this->get('/work')
            ->assertOk()
            ->assertSee(localized_route('work', locale: 'ar').'#project-'.$eligible->key, false)
            ->assertDontSee(localized_route('work', locale: 'ar').'#project-'.$notDetailed->key, false);
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
