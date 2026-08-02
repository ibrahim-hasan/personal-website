<?php

namespace Tests\Feature;

use App\Models\Service;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceHubRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ServiceSeeder::class);
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
            ->assertSee('Systems &amp; Automation Architecture', false);
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
