<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceDetailRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_complete_service_resolves_only_by_the_active_locale_slug_and_generates_complete_metadata(): void
    {
        $service = Service::factory()->create([
            'slug' => [
                'ar' => 'تحول-رقمي-عملي',
                'en' => 'practical-digital-transformation',
            ],
            'name' => [
                'ar' => 'استراتيجية التحول الرقمي',
                'en' => 'Digital transformation strategy',
            ],
        ]);
        $arabicUrl = localized_route('services.show', ['service' => $service], locale: 'ar');
        $englishUrl = localized_route('services.show', ['service' => $service], locale: 'en');

        $this->get($arabicUrl)
            ->assertOk()
            ->assertSee('استراتيجية التحول الرقمي', false)
            ->assertSee('hreflang="en" href="'.$englishUrl.'"', false)
            ->assertSee('"@type":"Service"', false)
            ->assertDontSee('site-footer__cta', false);
        $this->get($englishUrl)
            ->assertOk()
            ->assertSee('Digital transformation strategy', false)
            ->assertSee('hreflang="ar" href="'.$arabicUrl.'"', false);
        $this->get('/services/practical-digital-transformation')->assertNotFound();
    }

    public function test_draft_incomplete_and_inactive_services_cannot_resolve_on_the_public_detail_route(): void
    {
        $draft = Service::factory()->draft()->create();
        $incomplete = Service::factory()->create([
            'engagement_note' => ['ar' => 'بداية واضحة فقط'],
        ]);
        $inactive = Service::factory()->inactive()->create();

        foreach ([$draft, $incomplete, $inactive] as $service) {
            $this->get(localized_route('services.show', ['service' => $service], locale: 'ar'))
                ->assertNotFound();
        }
    }
}
