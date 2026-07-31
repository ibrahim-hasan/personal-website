<?php

namespace Tests\Feature;

use App\Actions\Services\ServicePublicationValidator;
use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDeliveryEntity;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectEvidenceLevel;
use App\Enums\ProjectPermissionStatus;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Services\Projects\ProjectCaseStudyPublicationValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatedContentPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_relation_validation_is_strict_without_recursing_through_a_project_back_to_the_service(): void
    {
        $service = Service::factory()->create(['key' => 'relation-safe-service']);
        $project = $this->eligibleProject('relation-safe-project');
        $draftArticle = Article::factory()->create([
            'key' => 'relation-draft-article',
            'is_published' => false,
        ]);

        $service->projects()->attach($project, ['sort_order' => 0]);
        $service->articles()->attach($draftArticle, ['sort_order' => 0]);

        $violations = app(ServicePublicationValidator::class)->violations($service->fresh());

        $this->assertArrayHasKey('relation.article.relation-draft-article', $violations);
        $this->assertArrayNotHasKey('relation.project.relation-safe-project', $violations);

        $draftArticle->update(['is_published' => true]);

        $this->assertTrue(app(ServicePublicationValidator::class)->isPublishable($service->fresh()));
    }

    public function test_project_relation_validation_requires_direct_articles_to_be_publicly_available(): void
    {
        $project = $this->eligibleProject('project-with-direct-article');
        $draftArticle = Article::factory()->create([
            'key' => 'project-draft-article',
            'is_published' => false,
        ]);
        $project->articles()->attach($draftArticle, ['sort_order' => 0]);

        $validator = app(ProjectCaseStudyPublicationValidator::class);

        $this->assertTrue(
            $validator->validate($project->fresh())->hasViolation('relation.article.project-draft-article.not_public'),
        );

        $draftArticle->update(['is_published' => true]);

        $this->assertTrue($validator->validate($project->fresh())->isEligible());
    }

    public function test_service_and_project_details_render_public_related_articles_in_selected_order(): void
    {
        $service = Service::factory()->create(['key' => 'service-related-output']);
        $project = $this->eligibleProject('project-related-output');
        $firstArticle = Article::factory()->create(['key' => 'first-related-output']);
        $secondArticle = Article::factory()->create(['key' => 'second-related-output']);

        $service->projects()->attach($project, ['sort_order' => 0]);
        $service->articles()->sync([
            $secondArticle->getKey() => ['sort_order' => 0],
            $firstArticle->getKey() => ['sort_order' => 1],
        ]);
        $project->articles()->sync([
            $secondArticle->getKey() => ['sort_order' => 0],
            $firstArticle->getKey() => ['sort_order' => 1],
        ]);

        $articleUrls = [
            localized_route('writing.show', ['article' => $secondArticle], locale: 'ar'),
            localized_route('writing.show', ['article' => $firstArticle], locale: 'ar'),
        ];

        $this->get(localized_route('services.show', ['service' => $service], locale: 'ar'))
            ->assertOk()
            ->assertSee(__('site.services.related_projects'), false)
            ->assertSee(__('site.services.related_articles'), false)
            ->assertSeeInOrder($articleUrls, false);

        $this->get(localized_route('work.show', ['project' => $project], locale: 'ar'))
            ->assertOk()
            ->assertSee(__('site.work.related_articles'), false)
            ->assertSeeInOrder($articleUrls, false);
    }

    public function test_article_relationships_preserve_selected_order_and_keep_publication_eligibility_explicit(): void
    {
        $article = Article::factory()->create([
            'key' => 'article-related-output',
            'slug' => ['ar' => 'مقال-علاقات-عامة', 'en' => 'public-related-article'],
        ]);
        $visibleService = Service::factory()->create([
            'key' => 'visible-related-service',
            'name' => ['ar' => 'خدمة ظاهرة', 'en' => 'Visible service'],
        ]);
        $hiddenService = Service::factory()->draft()->create([
            'key' => 'hidden-related-service',
            'name' => ['ar' => 'خدمة مخفية', 'en' => 'Hidden service'],
        ]);
        $visibleProject = $this->eligibleProject('visible-related-project', [
            'title' => ['ar' => 'مشروع ظاهر', 'en' => 'Visible project'],
        ]);
        $hiddenProject = Project::factory()->create([
            'key' => 'hidden-related-project',
            'title' => ['ar' => 'مشروع مخفي', 'en' => 'Hidden project'],
            'is_detailed_case_study' => false,
        ]);

        $article->services()->sync([
            $visibleService->getKey() => ['sort_order' => 0],
            $hiddenService->getKey() => ['sort_order' => 1],
        ]);
        $article->projects()->sync([
            $visibleProject->getKey() => ['sort_order' => 0],
            $hiddenProject->getKey() => ['sort_order' => 1],
        ]);

        $article = $article->fresh();

        $this->assertSame(
            [$visibleService->key, $hiddenService->key],
            $article->services()->pluck('services.key')->all(),
        );
        $this->assertSame(
            [$visibleProject->key, $hiddenProject->key],
            $article->projects()->pluck('projects.key')->all(),
        );
        $this->assertTrue(app(ServicePublicationValidator::class)->isPublishable($visibleService));
        $this->assertFalse(app(ServicePublicationValidator::class)->isPublishable($hiddenService));
        $this->assertTrue(app(ProjectCaseStudyPublicationValidator::class)->validate($visibleProject)->isEligible());
        $this->assertFalse(app(ProjectCaseStudyPublicationValidator::class)->validate($hiddenProject)->isEligible());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function eligibleProject(string $key, array $overrides = []): Project
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
            'key' => $key,
            'slug' => ['ar' => 'مشروع-'.$key, 'en' => $key],
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
        ], $overrides));
    }
}
