<?php

namespace Tests\Feature\Domain;

use App\Models\Service;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LegacyContentRetirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_schema_contains_only_current_personal_site_content_tables(): void
    {
        foreach ([
            'articles',
            'comments',
            'contact_inquiries',
            'media',
            'projects',
            'services',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Expected current table [{$table}] to exist.");
        }

        foreach ([
            'authors',
            'guide_downloaders',
            'guides',
            'intellectual_libraries',
            'newsletters',
            'taggables',
            'tags',
        ] as $table) {
            $this->assertFalse(Schema::hasTable($table), "Expected retired table [{$table}] to be absent.");
        }
    }

    public function test_permission_seeder_creates_only_current_admin_resource_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $this->assertSame(56, Permission::query()->count());
        $this->assertTrue(Permission::query()->where('name', 'update articles')->exists());
        $this->assertTrue(Permission::query()->where('name', 'view_any contact_inquiries')->exists());

        foreach ([
            'authors',
            'guide_downloaders',
            'guides',
            'intellectual_libraries',
            'newsletters',
            'tags',
        ] as $resource) {
            $this->assertFalse(
                Permission::query()->where('name', 'like', "% {$resource}")->exists(),
                "Expected no permissions for retired resource [{$resource}].",
            );
        }
    }

    public function test_legacy_retirement_cannot_be_silently_rolled_back(): void
    {
        $migration = require database_path('migrations/2026_07_15_224556_retire_legacy_content_tables.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Restore a database backup');

        $migration->down();
    }

    public function test_retiring_legacy_media_removes_its_public_files(): void
    {
        Storage::fake('public');
        $media = Media::query()->create([
            'model_type' => 'App\\Models\\Guide',
            'model_id' => 123,
            'collection_name' => 'legacy',
            'name' => 'legacy-guide',
            'file_name' => 'legacy-guide.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 12,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);
        $path = "{$media->getKey()}/legacy-guide.pdf";
        Storage::disk('public')->put($path, 'legacy guide');

        $migration = require database_path('migrations/2026_07_15_224556_retire_legacy_content_tables.php');
        $migration->up();

        $this->assertDatabaseMissing('media', ['id' => $media->getKey()]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_final_transition_removes_only_marked_legacy_services_and_enforces_the_slug_invariant(): void
    {
        $current = Service::factory()->create();
        $legacy = Service::factory()->create(['slug' => 'legacy-service-123']);
        $legacy->delete();

        $migration = require database_path('migrations/2026_07_16_001323_finalize_personal_site_transition.php');
        $migration->up();

        $this->assertModelExists($current);
        $this->assertDatabaseMissing('services', ['id' => $legacy->getKey()]);

        $slugColumn = collect(Schema::getColumns('services'))->firstWhere('name', 'slug');
        $this->assertFalse($slugColumn['nullable']);
    }

    public function test_final_transition_blocks_a_partial_batch_rollback(): void
    {
        $migration = require database_path('migrations/2026_07_16_001323_finalize_personal_site_transition.php');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('personal-site transition is irreversible');

        $migration->down();
    }
}
