<?php

namespace Tests\Unit\Support;

use App\Support\PortfolioAtlas;
use Tests\TestCase;

class PortfolioAtlasTest extends TestCase
{
    public function test_project_ids_are_unique_and_the_selection_is_stable(): void
    {
        $projectIds = array_column(PortfolioAtlas::projects(), 'id');

        $this->assertSame([
            'digi-pedia',
            'wafaa',
            'rannan',
            'maazim',
            'rafid-360',
            'taifk',
            'bosalty',
            '2060-investments',
        ], $projectIds);
        $this->assertCount(count($projectIds), array_unique($projectIds));
    }

    public function test_projects_only_use_declared_decision_lenses(): void
    {
        $allowedLenses = ['ai-adoption', 'transformation', 'product', 'operations'];

        $this->assertSame($allowedLenses, array_column(PortfolioAtlas::lenses(), 'id'));

        foreach (PortfolioAtlas::projects() as $project) {
            $this->assertContains($project['lens'], $allowedLenses);
        }
    }

    public function test_public_content_resolves_required_fields_in_arabic_and_english(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);

            $this->assertCount(3, PortfolioAtlas::companies());
            $this->assertCount(4, PortfolioAtlas::experience());
            $this->assertCount(8, PortfolioAtlas::projects());
            $this->assertCount(4, PortfolioAtlas::lenses());

            $this->assertResolvedItems(
                PortfolioAtlas::companies(),
                ['id', 'role', 'name', 'relationship', 'tagline', 'summary'],
                'focus',
            );
            $this->assertResolvedItems(
                PortfolioAtlas::experience(),
                ['id', 'step', 'title', 'summary'],
            );
            $this->assertResolvedItems(
                PortfolioAtlas::projects(),
                ['id', 'title', 'sector', 'summary', 'challenge', 'response', 'outcome', 'lens', 'image', 'alt', 'logo', 'logo_alt'],
                'tags',
            );
            $this->assertResolvedItems(
                PortfolioAtlas::lenses(),
                ['id', 'label', 'description', 'question'],
            );
        }
    }

    public function test_unsupported_locale_uses_the_configured_fallback(): void
    {
        config()->set('app.fallback_locale', 'en');

        app()->setLocale('en');
        $englishProjects = PortfolioAtlas::projects();
        $englishCompanies = PortfolioAtlas::companies();

        app()->setLocale('fr');

        $this->assertSame($englishProjects, PortfolioAtlas::projects());
        $this->assertSame($englishCompanies, PortfolioAtlas::companies());
    }

    public function test_featured_projects_support_lens_filtering_and_limits(): void
    {
        $this->assertCount(4, PortfolioAtlas::featuredProjects());

        $operationsProjects = PortfolioAtlas::featuredProjects('operations', 2);

        $this->assertCount(2, $operationsProjects);
        $this->assertSame(['operations'], array_values(array_unique(array_column($operationsProjects, 'lens'))));
        $this->assertSame([], PortfolioAtlas::featuredProjects('unknown'));
        $this->assertSame([], PortfolioAtlas::featuredProjects(limit: 0));
    }

    public function test_homepage_projects_fill_a_balanced_six_case_grid(): void
    {
        $this->assertSame(
            ['digi-pedia', 'wafaa', 'rannan', 'maazim', 'rafid-360', 'taifk'],
            array_column(PortfolioAtlas::homepageProjects(), 'id'),
        );
        $this->assertSame([], PortfolioAtlas::homepageProjects(0));
    }

    public function test_company_chapters_describe_concrete_operating_outcomes(): void
    {
        app()->setLocale('en');

        $companies = collect(PortfolioAtlas::companies())->keyBy('id');

        $this->assertSame('Turning the goal into a product roadmap', $companies['code-moments']['focus'][0]);
        $this->assertSame('An operating model built to support growth', $companies['from-scratch']['focus'][0]);
        $this->assertSame('Assessing the viability of AI use cases', $companies['independent-strategic-practice']['focus'][0]);
    }

    public function test_from_scratch_precedes_code_moments_in_the_company_chapters(): void
    {
        $this->assertSame(
            ['from-scratch', 'code-moments', 'independent-strategic-practice'],
            array_column(PortfolioAtlas::companies(), 'id'),
        );
    }

    public function test_company_actions_link_external_sites_or_the_consultation_route(): void
    {
        app()->setLocale('en');

        $companies = collect(PortfolioAtlas::companies())->keyBy('id');

        $this->assertSame('https://fromscratch-solutions.com', $companies['from-scratch']['action']['url']);
        $this->assertTrue($companies['from-scratch']['action']['external']);
        $this->assertSame('https://codemoments.com', $companies['code-moments']['action']['url']);
        $this->assertTrue($companies['code-moments']['action']['external']);
        $this->assertNull($companies['independent-strategic-practice']['action']['url']);
        $this->assertFalse($companies['independent-strategic-practice']['action']['external']);
    }

    public function test_public_content_does_not_expose_banned_technical_terms(): void
    {
        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);

            $publicContent = json_encode([
                PortfolioAtlas::companies(),
                PortfolioAtlas::experience(),
                PortfolioAtlas::projects(),
                PortfolioAtlas::lenses(),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:Laravel|GitHub|API|framework|database)\b/i',
                $publicContent,
            );
        }
    }

    public function test_every_project_has_a_non_empty_atlas_image_path(): void
    {
        foreach (PortfolioAtlas::projects() as $project) {
            $this->assertNotSame('', trim($project['image']));
            $this->assertMatchesRegularExpression(
                '#^images/projects/atlas/[a-z0-9-]+\.webp$#',
                $project['image'],
            );
            $this->assertMatchesRegularExpression(
                '#^images/brands/projects/[a-z0-9-]+\.webp$#',
                $project['logo'],
            );
            $this->assertFileExists(public_path($project['image']));
            $this->assertFileExists(public_path($project['logo']));
        }
    }

    public function test_company_chapters_use_current_names_roles_and_authentic_marks(): void
    {
        app()->setLocale('en');

        $companies = collect(PortfolioAtlas::companies())->keyBy('id');

        $this->assertSame('Founder & Chief Executive Officer', $companies['code-moments']['relationship']);
        $this->assertSame('From Scratch', $companies['from-scratch']['name']);
        $this->assertSame('Co-founder & Chief Executive Officer', $companies['from-scratch']['relationship']);
        $this->assertSame('Technical Expertise with Clients', $companies['independent-strategic-practice']['name']);
        foreach (['code-moments', 'from-scratch'] as $companyId) {
            foreach (['logo_on_light', 'logo_on_dark'] as $logoVariant) {
                $logoPath = $companies[$companyId][$logoVariant];

                $this->assertIsString($logoPath);
                $this->assertMatchesRegularExpression(
                    '#^images/brands/companies/[a-z0-9-]+-on-(light|dark)\.svg$#',
                    $logoPath,
                );
                $this->assertFileExists(public_path($logoPath));
            }
        }

        $this->assertSame('', $companies['independent-strategic-practice']['logo_on_light']);
        $this->assertSame('', $companies['independent-strategic-practice']['logo_on_dark']);
        $this->assertArrayNotHasKey('from-scratch-solutions', $companies->all());
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<string>  $requiredStringFields
     */
    private function assertResolvedItems(array $items, array $requiredStringFields, ?string $listField = null): void
    {
        foreach ($items as $item) {
            foreach ($requiredStringFields as $field) {
                $this->assertArrayHasKey($field, $item);
                $this->assertIsString($item[$field]);
                $this->assertNotSame('', trim($item[$field]));
            }

            if ($listField === null) {
                continue;
            }

            $this->assertArrayHasKey($listField, $item);
            $this->assertIsArray($item[$listField]);
            $this->assertNotEmpty($item[$listField]);

            foreach ($item[$listField] as $value) {
                $this->assertIsString($value);
                $this->assertNotSame('', trim($value));
            }
        }
    }
}
