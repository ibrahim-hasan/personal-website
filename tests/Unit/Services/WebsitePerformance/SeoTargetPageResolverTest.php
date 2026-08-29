<?php

namespace Tests\Unit\Services\WebsitePerformance;

use App\Models\Article;
use App\Services\WebsitePerformance\SeoTargetPageResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTargetPageResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exposes_the_six_non_brand_query_groups_defined_by_the_seo_brief(): void
    {
        $this->assertSame([
            'saudi_ai_digital_transformation_advisory',
            'ai_governance',
            'first_ai_use_case',
            'ai_adoption_roadmap',
            'data_knowledge_systems',
            'digital_transformation_workflow_automation',
        ], SeoTargetPageResolver::queryGroupKeys());
    }

    public function test_it_resolves_target_pages_from_stable_article_keys_and_current_localized_slugs(): void
    {
        Article::factory()->create([
            'key' => 'ai-governance',
            'slug' => [
                'ar' => 'الحوكمة-الحالية',
                'en' => 'current-governance-slug',
            ],
        ]);

        $resolver = app(SeoTargetPageResolver::class);

        $this->assertSame(
            'ai_governance',
            $resolver->pageKey('https://ibrahimhasan.net/en/writing/current-governance-slug'),
        );
        $this->assertSame(
            'ai_governance',
            $resolver->pageKey('https://ibrahimhasan.net/writing/'.rawurlencode('الحوكمة-الحالية')),
        );
        $this->assertNull(
            $resolver->pageKey('https://ibrahimhasan.net/en/writing/a-practical-operating-model-for-ai-governance'),
        );
    }
}
