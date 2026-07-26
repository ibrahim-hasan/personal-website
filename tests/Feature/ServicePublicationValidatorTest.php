<?php

namespace Tests\Feature;

use App\Actions\Services\ServicePublicationValidator;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePublicationValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_complete_active_service_is_publishable_in_both_locales(): void
    {
        $service = Service::factory()->create();

        $this->assertTrue(app(ServicePublicationValidator::class)->isPublishable($service));
    }

    public function test_missing_localized_semantic_content_blocks_publication_without_falling_back_to_the_other_language(): void
    {
        $service = Service::factory()->create([
            'engagement_note' => [
                'ar' => 'نبدأ بجلسة لفهم التحدّي وتحديد الخطوة العملية التالية.',
            ],
            'deliverables' => [
                ['ar' => 'مخرج عربي معتمد'],
            ],
        ]);

        $violations = app(ServicePublicationValidator::class)->violations($service);

        $this->assertArrayHasKey('engagement_note.en', $violations);
        $this->assertArrayHasKey('deliverables.en', $violations);
    }

    public function test_service_publication_requires_the_fit_signal_and_deliverable_limits(): void
    {
        $service = Service::factory()->create([
            'fit_signals' => [
                'ar' => ['إشارة واحدة فقط'],
                'en' => ['Only one signal'],
            ],
            'deliverables' => [
                ['ar' => 'أ', 'en' => 'A'],
                ['ar' => 'ب', 'en' => 'B'],
                ['ar' => 'ج', 'en' => 'C'],
                ['ar' => 'د', 'en' => 'D'],
                ['ar' => 'هـ', 'en' => 'E'],
                ['ar' => 'و', 'en' => 'F'],
            ],
        ]);

        $violations = app(ServicePublicationValidator::class)->violations($service);

        $this->assertArrayHasKey('fit_signals.ar', $violations);
        $this->assertArrayHasKey('fit_signals.en', $violations);
        $this->assertArrayHasKey('deliverables.ar', $violations);
        $this->assertArrayHasKey('deliverables.en', $violations);
    }

    public function test_banned_sales_phrase_blocks_publication_but_other_uses_of_its_root_are_not_globally_banned(): void
    {
        $blocked = Service::factory()->create([
            'engagement_note' => [
                'ar' => 'نبدأ بجلسة تشخيصية قبل تحديد الخطوة التالية.',
                'en' => 'We begin by understanding the challenge and identifying the next practical step.',
            ],
        ]);
        $allowed = Service::factory()->create([
            'engagement_note' => [
                'ar' => 'نراجع التشخيص التشغيلي الموجود قبل تحديد الخطوة التالية.',
                'en' => 'We review the existing operational diagnosis before defining the next step.',
            ],
        ]);

        $validator = app(ServicePublicationValidator::class);

        $this->assertArrayHasKey('engagement_note.ar', $validator->violations($blocked));
        $this->assertArrayNotHasKey('engagement_note.ar', $validator->violations($allowed));
    }

    public function test_draft_services_can_be_content_complete_without_being_publicly_publishable(): void
    {
        $service = Service::factory()->draft()->create();
        $validator = app(ServicePublicationValidator::class);

        $this->assertTrue($validator->hasCompleteContent($service));
        $this->assertFalse($validator->isPublishable($service));
        $this->assertArrayHasKey('status', $validator->violations($service));
    }
}
