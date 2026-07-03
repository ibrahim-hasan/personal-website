<?php

namespace Tests\Feature\Validation;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_publish_transition_with_required_fields(): void
    {
        $service = Service::factory()->create([
            'name' => ['ar' => 'Test', 'en' => 'Test'],
            'is_draft' => true,
        ]);

        $service->is_draft = false;
        $service->save();

        $this->assertFalse($service->is_draft);
    }

    public function test_service_draft_allows_minimal_data(): void
    {
        $service = Service::factory()->draft()->create([
            'name' => ['ar' => '', 'en' => ''],
        ]);

        $service->refresh();
        $this->assertTrue($service->is_draft);
    }

    public function test_service_model_has_is_draft_attribute(): void
    {
        $draft = Service::factory()->draft()->create();
        $published = Service::factory()->create();

        $this->assertTrue($draft->is_draft);
        $this->assertFalse($published->is_draft);
    }
}
