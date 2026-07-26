<?php

namespace Tests\Feature\Filament;

use App\Actions\Projects\SetProjectCaseStudyPublication;
use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDeliveryEntity;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectEvidenceLevel;
use App\Enums\ProjectEvidenceState;
use App\Enums\ProjectPermissionStatus;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\RelationManagers\ProjectEvidenceRelationManager;
use App\Models\Article;
use App\Models\Project;
use App\Models\ProjectEvidence;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectAdministrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }

    public function test_project_form_registers_the_evidence_manager_and_the_bilingual_case_study_workflow_fields(): void
    {
        $admin = $this->admin();
        $project = Project::factory()->create();

        $this->assertContains(ProjectEvidenceRelationManager::class, ProjectResource::getRelations());

        Livewire::actingAs($admin)
            ->test(EditProject::class, ['record' => $project->getKey()])
            ->assertFormFieldExists('delivery_entity')
            ->assertFormFieldExists('permission_reference')
            ->assertFormFieldExists('case_study_sections.ar.executive_summary')
            ->assertFormFieldExists('case_study_sections.en.executive_summary')
            ->assertFormFieldExists('services')
            ->assertFormFieldExists('articles')
            ->assertFormFieldExists('image_permission_status')
            ->assertFormFieldExists('logo_permission_status');
    }

    public function test_authorized_admin_can_publish_and_unpublish_a_valid_detailed_case_study_without_hiding_its_work_card(): void
    {
        $admin = $this->admin();
        $project = $this->eligibleProject(['is_detailed_case_study' => false]);

        Livewire::actingAs($admin)
            ->test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionVisible('publish_case_study')
            ->callAction('publish_case_study')
            ->assertHasNoActionErrors();

        $project->refresh();

        $this->assertTrue($project->is_detailed_case_study);
        $this->assertTrue($project->is_active);

        Livewire::actingAs($admin)
            ->test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionVisible('unpublish_case_study')
            ->callAction('unpublish_case_study')
            ->assertHasNoActionErrors();

        $project->refresh();

        $this->assertFalse($project->is_detailed_case_study);
        $this->assertTrue($project->is_active);
    }

    public function test_related_services_save_in_the_operator_selected_order(): void
    {
        $admin = $this->admin();
        $project = Project::factory()->create();
        $firstService = Service::factory()->create(['key' => 'first-related-service']);
        $secondService = Service::factory()->create(['key' => 'second-related-service']);

        Livewire::actingAs($admin)
            ->test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'services' => [$secondService->getKey(), $firstService->getKey()],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            [$secondService->key, $firstService->key],
            $project->fresh()->services()->pluck('key')->all(),
        );
    }

    public function test_related_articles_save_in_the_operator_selected_order(): void
    {
        $admin = $this->admin();
        $project = Project::factory()->create();
        $firstArticle = Article::factory()->create(['key' => 'first-related-project-article']);
        $secondArticle = Article::factory()->create(['key' => 'second-related-project-article']);

        Livewire::actingAs($admin)
            ->test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm([
                'articles' => [$secondArticle->getKey(), $firstArticle->getKey()],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            [$secondArticle->key, $firstArticle->key],
            $project->fresh()->articles()->pluck('key')->all(),
        );
    }

    public function test_editor_cannot_see_case_study_publication_actions_or_evidence_review_actions(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $project = Project::factory()->create();
        $evidence = ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'state' => ProjectEvidenceState::Draft,
        ]);

        Livewire::actingAs($editor)
            ->test(EditProject::class, ['record' => $project->getKey()])
            ->assertActionHidden('publish_case_study')
            ->assertActionHidden('unpublish_case_study');

        Livewire::actingAs($editor)
            ->test(ProjectEvidenceRelationManager::class, [
                'ownerRecord' => $project,
                'pageClass' => EditProject::class,
            ])
            ->assertTableActionHidden('verify', $evidence)
            ->assertTableActionHidden('approve', $evidence)
            ->assertTableActionHidden('make_public', $evidence);
    }

    public function test_authorized_admin_can_verify_evidence_from_the_relation_manager(): void
    {
        $admin = $this->admin();
        $project = Project::factory()->create();
        $evidence = ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'state' => ProjectEvidenceState::Draft,
        ]);

        Livewire::actingAs($admin)
            ->test(ProjectEvidenceRelationManager::class, [
                'ownerRecord' => $project,
                'pageClass' => EditProject::class,
            ])
            ->assertTableActionVisible('verify', $evidence)
            ->callTableAction('verify', $evidence);

        $evidence->refresh();

        $this->assertSame(ProjectEvidenceState::Verified, $evidence->state);
        $this->assertSame($admin->getKey(), $evidence->verified_by);
        $this->assertFalse($evidence->is_public);
    }

    public function test_reviewed_evidence_cannot_be_edited_or_deleted_outside_the_governed_workflow(): void
    {
        $admin = $this->admin();
        $project = Project::factory()->create();
        $evidence = ProjectEvidence::factory()->create([
            'project_id' => $project->getKey(),
            'state' => ProjectEvidenceState::Approved,
            'is_public' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(ProjectEvidenceRelationManager::class, [
                'ownerRecord' => $project,
                'pageClass' => EditProject::class,
            ])
            ->assertTableActionHidden('edit', $evidence)
            ->assertTableActionHidden('delete', $evidence)
            ->assertTableActionVisible('revoke', $evidence);
    }

    public function test_super_admin_cannot_bypass_case_study_publication_validation(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $project = Project::factory()->create(['is_detailed_case_study' => false]);

        try {
            app(SetProjectCaseStudyPublication::class)->publish($superAdmin, $project);
            $this->fail('Incomplete case-study content must block publication for every role.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('case_study', $exception->errors());
            $this->assertSame(__('project_admin.publication.review_missing'), $exception->errors()['case_study'][0]);
        }

        $this->assertFalse($project->fresh()->is_detailed_case_study);
    }

    /** @param array<string, mixed> $overrides */
    private function eligibleProject(array $overrides = []): Project
    {
        $suffix = (string) fake()->unique()->numberBetween(1000, 999999);

        return Project::factory()->create(array_merge([
            'key' => 'admin-case-study-'.$suffix,
            'slug' => ['ar' => 'دراسة-إدارية-'.$suffix, 'en' => 'admin-case-study-'.$suffix],
            'title' => ['ar' => 'مشروع تجريبي موثق', 'en' => 'A documented example project'],
            'is_detailed_case_study' => true,
            'is_active' => true,
            'delivery_entity' => ProjectDeliveryEntity::Direct,
            'delivery_period' => ['ar' => '2025', 'en' => '2025'],
            'ibrahim_role' => ['ar' => 'قيادة التنفيذ', 'en' => 'Delivery leadership'],
            'confidentiality_note' => ['ar' => 'نُشرت التفاصيل بموافقة واضحة.', 'en' => 'The details are published with clear permission.'],
            'case_study_sections' => [
                'ar' => [
                    'executive_summary' => 'ملخص تنفيذي يوضح نطاق العمل.',
                    'context' => 'السياق التشغيلي للمشروع.',
                    'constraints' => ['تعدد أصحاب القرار'],
                    'changes' => [['area' => 'workflow', 'body' => 'أعيد تنظيم مسار العمل.']],
                    'solution' => 'حل عملي يناسب السياق.',
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
            ],
            'case_study_reviewed_at' => now(),
            'permission_status' => ProjectPermissionStatus::ApprovedNamed,
            'permission_reference' => 'Private named approval reference',
            'disclosure_level' => ProjectDisclosureLevel::Named,
            'evidence_level' => ProjectEvidenceLevel::Qualitative,
            'image' => 'images/projects/admin-example.webp',
            'image_permission_status' => ProjectAssetPermissionStatus::Approved,
            'image_permission_reference' => 'Private image approval reference',
            'logo' => 'images/brands/projects/admin-example.webp',
            'logo_permission_status' => ProjectAssetPermissionStatus::Approved,
            'logo_permission_reference' => 'Private logo approval reference',
        ], $overrides));
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }
}
