<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\SeoAuthorityEditorialContentSeeder;
use Database\Seeders\SeoServiceRelationshipSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ServiceHubRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            ServiceSeeder::class,
            ArticleSeeder::class,
            ProjectSeeder::class,
        ]);
    }

    public function test_the_services_hub_renders_all_service_content_in_the_initial_html(): void
    {
        $this->get('/services')
            ->assertOk()
            ->assertSee('data-service-hub', false)
            ->assertSee('data-service-hub-section', false)
            ->assertSee('id="service-transformation"', false)
            ->assertSee('id="service-ai-adoption"', false)
            ->assertSee('id="service-data-governance"', false)
            ->assertSee('id="service-systems"', false)
            ->assertSee('استراتيجية التحول الرقمي', false)
            ->assertSee('هندسة تبنّي الذكاء الاصطناعي', false)
            ->assertSee('حوكمة البيانات واستراتيجيتها', false)
            ->assertSee('هندسة الأنظمة والأتمتة', false)
            ->assertDontSee('x-data="serviceTabs(', false)
            ->assertDontSee('x-text="current()', false)
            ->assertDontSee('role="tabpanel"', false);

        $this->get('/en/services')
            ->assertOk()
            ->assertSee('id="service-transformation"', false)
            ->assertSee('Digital Transformation Strategy', false)
            ->assertSee('AI Adoption Engineering', false)
            ->assertSee('Data Governance &amp; Strategy', false)
            ->assertSee('Systems &amp; Automation Architecture', false)
            ->assertSee('?service=transformation#consultation', false)
            ->assertSee('?service=ai-adoption#consultation', false)
            ->assertSee('?service=data-governance#consultation', false)
            ->assertSee('?service=systems#consultation', false)
            ->assertSee('data-analytics-ui-location="service_section"', false);
    }

    public function test_known_legacy_service_urls_permanently_redirect_to_their_locale_hub_anchor_and_preserve_the_query(): void
    {
        $aliases = [
            'ar' => [
                'استراتيجية-التحول-الرقمي' => 'transformation',
                'استراتيجية-وتطبيق-الذكاء-الاصطناعي' => 'ai-adoption',
                'هندسة-تبني-الذكاء-الاصطناعي' => 'ai-adoption',
                'استراتيجية-البيانات-وحوكمتها' => 'data-governance',
                'حوكمة-البيانات-واستراتيجيتها' => 'data-governance',
                'الأنظمة-والأتمتة' => 'systems',
                'هندسة-الأنظمة-والأتمتة' => 'systems',
            ],
            'en' => [
                'digital-transformation-strategy' => 'transformation',
                'ai-strategy-and-implementation' => 'ai-adoption',
                'ai-adoption-engineering' => 'ai-adoption',
                'data-strategy-and-governance' => 'data-governance',
                'data-governance-strategy' => 'data-governance',
                'systems-and-automation' => 'systems',
                'systems-automation-architecture' => 'systems',
            ],
        ];

        foreach ($aliases as $locale => $localeAliases) {
            foreach ($localeAliases as $alias => $key) {
                $this->get(localized_route('services.legacy', ['legacyService' => $alias], locale: $locale).'?campaign=organic')
                    ->assertStatus(301)
                    ->assertRedirect(localized_route('services', locale: $locale).'?campaign=organic#service-'.$key);
            }
        }
    }

    public function test_ordered_public_relationships_render_as_crawlable_initial_html_links(): void
    {
        $service = Service::query()->where('key', 'ai-adoption')->firstOrFail();
        $articles = Article::query()
            ->whereIn('key', ['first-ai-use-case', 'ai-value'])
            ->get()
            ->keyBy('key');
        $projects = Project::query()
            ->whereIn('key', ['digi-pedia', 'maazim'])
            ->get()
            ->keyBy('key');

        $service->articles()->sync([
            $articles->get('first-ai-use-case')->getKey() => ['sort_order' => 0],
            $articles->get('ai-value')->getKey() => ['sort_order' => 1],
        ]);
        $service->projects()->sync([
            $projects->get('digi-pedia')->getKey() => ['sort_order' => 0],
            $projects->get('maazim')->getKey() => ['sort_order' => 1],
        ]);

        $response = $this->get('/en/services');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Choose Your First AI Use Case',
                'From AI Experiment to Measurable Business Value',
            ])
            ->assertSee(localized_route('work', locale: 'en').'#project-digi-pedia', false)
            ->assertSee(localized_route('work', locale: 'en').'#project-maazim', false)
            ->assertSee('Digi Pedia', false)
            ->assertSee('Maazim', false);
    }

    public function test_draft_articles_and_inactive_projects_are_filtered_from_service_relationships(): void
    {
        $service = Service::query()->where('key', 'systems')->firstOrFail();
        $article = Article::query()->where('key', 'automation-assistant-agent')->firstOrFail();
        $project = Project::query()->where('key', 'wafaa')->firstOrFail();

        $article->update(['is_published' => false]);
        $project->update(['is_active' => false]);
        $service->articles()->attach($article, ['sort_order' => 0]);
        $service->projects()->attach($project, ['sort_order' => 0]);

        $this->get('/en/services')
            ->assertOk()
            ->assertDontSee($article->getTranslation('title', 'en'))
            ->assertDontSee(localized_route('work', locale: 'en').'#project-wafaa', false);
    }

    public function test_the_public_pillar_leads_its_service_while_scheduled_drafts_remain_hidden(): void
    {
        $this->travelTo(Carbon::parse('2026-08-29 12:00:00'));

        $this->seed([
            SeoAuthorityEditorialContentSeeder::class,
            SeoServiceRelationshipSeeder::class,
        ]);
        $pillar = Article::query()
            ->where('key', 'ai-adoption-roadmap-saudi-companies-2026')
            ->firstOrFail();
        $firstUseCase = Article::query()->where('key', 'first-ai-use-case')->firstOrFail();
        $scheduledDraft = Article::query()
            ->where('key', 'ai-use-case-register-governance-template')
            ->firstOrFail();

        $this->get('/en/services')
            ->assertOk()
            ->assertSeeInOrder([
                $pillar->getTranslation('title', 'en', false),
                $firstUseCase->getTranslation('title', 'en', false),
            ])
            ->assertDontSee($scheduledDraft->getTranslation('title', 'en', false));
    }

    public function test_unknown_or_cross_locale_legacy_service_urls_are_not_resolved(): void
    {
        $this->get('/services/not-a-known-service')->assertNotFound();
        $this->get(localized_route('services.legacy', [
            'legacyService' => 'استراتيجية-التحول-الرقمي',
        ], locale: 'en'))->assertNotFound();
        $this->get('/services/digital-transformation-strategy')->assertNotFound();
    }

    public function test_known_legacy_aliases_stay_redirectable_after_a_service_is_unpublished_or_deleted(): void
    {
        Service::query()->where('key', 'transformation')->update([
            'is_draft' => true,
            'is_active' => false,
        ]);

        $this->get(localized_route('services.legacy', [
            'legacyService' => 'استراتيجية-التحول-الرقمي',
        ]))
            ->assertStatus(301)
            ->assertRedirect(localized_route('services').'#service-transformation');

        Service::query()->where('key', 'transformation')->firstOrFail()->delete();

        $this->get(localized_route('services.legacy', [
            'legacyService' => 'استراتيجية-التحول-الرقمي',
        ]))
            ->assertStatus(301)
            ->assertRedirect(localized_route('services').'#service-transformation');
    }
}
