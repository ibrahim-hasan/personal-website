<?php

namespace Tests\Feature\Validation;

use App\Enums\IntellectualLibraryType;
use App\Models\IntellectualLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntellectualLibraryMediaRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_intellectual_library_type_enum_has_expected_cases(): void
    {
        $cases = IntellectualLibraryType::cases();

        $this->assertContainsOnlyInstancesOf(IntellectualLibraryType::class, $cases);
        $this->assertGreaterThanOrEqual(4, count($cases));
    }

    public function test_library_model_has_translatable_fields(): void
    {
        $library = new IntellectualLibrary;

        $this->assertTrue(in_array('name', $library->getTranslatableAttributes()));
        $this->assertTrue(in_array('slug', $library->getTranslatableAttributes()));
    }

    public function test_library_model_has_author_relationship(): void
    {
        $library = new IntellectualLibrary;

        $this->assertTrue(method_exists($library, 'author'));
        $this->assertTrue(method_exists($library, 'registerMediaCollections'));
    }

    public function test_library_type_enum_labels_are_translatable(): void
    {
        $types = IntellectualLibraryType::cases();

        foreach ($types as $type) {
            $this->assertNotEmpty($type->label());
        }
    }

    public function test_library_model_has_tag_support(): void
    {
        $library = new IntellectualLibrary;

        $this->assertTrue(method_exists($library, 'tags'));
    }
}
