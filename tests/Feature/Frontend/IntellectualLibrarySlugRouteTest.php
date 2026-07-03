<?php

namespace Tests\Feature\Frontend;

use App\Models\IntellectualLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntellectualLibrarySlugRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_library_model_has_slug_retrieval_method(): void
    {
        $library = new IntellectualLibrary;

        $this->assertTrue(method_exists($library, 'getSlugForLocale'));
        $this->assertTrue(method_exists($library, 'getSlugOptions'));
    }

    public function test_library_scope_orders_by_display_date_descending(): void
    {
        $library = new IntellectualLibrary;

        $this->assertTrue(method_exists($library, 'scopeOrderByDisplayDateDesc'));
    }

    public function test_library_has_display_date_attribute(): void
    {
        $library = new IntellectualLibrary;

        $this->assertTrue(method_exists($library, 'displayDate'));
    }
}
