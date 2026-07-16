<?php

namespace Tests\Feature\Localization;

use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LocalizedSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_slugs_are_generated_per_locale_and_keep_native_arabic_script(): void
    {
        $article = Article::factory()->create([
            'slug' => [],
            'title' => [
                'ar' => 'عنوان عربي واضح',
                'en' => 'A Clear English Title',
            ],
        ]);

        $this->assertSame('عنوان-عربي-واضح', $article->getTranslation('slug', 'ar'));
        $this->assertSame('a-clear-english-title', $article->getTranslation('slug', 'en'));
        $this->assertSame('عنوان-عربي-واضح', $article->slug_ar);
        $this->assertSame('a-clear-english-title', $article->slug_en);
    }

    public function test_arabic_slugs_preserve_latin_acronyms_in_mixed_script_titles(): void
    {
        $article = Article::factory()->create([
            'slug' => [],
            'title' => [
                'ar' => 'دليل AI لاتخاذ القرار',
                'en' => 'An AI Decision Guide',
            ],
        ]);

        $this->assertSame('دليل-AI-لاتخاذ-القرار', $article->getTranslation('slug', 'ar'));
        $this->assertSame('دليل-AI-لاتخاذ-القرار', $article->slug_ar);
    }

    public function test_generated_slugs_are_unique_in_each_locale_including_soft_deleted_records(): void
    {
        $firstArticle = Article::factory()->create([
            'slug' => [],
            'title' => [
                'ar' => 'عنوان متكرر',
                'en' => 'Repeated title',
            ],
        ]);
        $firstArticle->delete();

        $secondArticle = Article::factory()->create([
            'slug' => [],
            'title' => [
                'ar' => 'عنوان متكرر',
                'en' => 'Repeated title',
            ],
        ]);

        $this->assertSame('عنوان-متكرر-1', $secondArticle->getTranslation('slug', 'ar'));
        $this->assertSame('repeated-title-1', $secondArticle->getTranslation('slug', 'en'));
    }

    public function test_existing_localized_slugs_are_not_overwritten_when_titles_change(): void
    {
        $article = Article::factory()->create([
            'slug' => [
                'ar' => 'مسار-تحريري',
                'en' => 'editorial-path',
            ],
        ]);

        $article->update([
            'title' => [
                'ar' => 'عنوان جديد',
                'en' => 'A new title',
            ],
        ]);

        $this->assertSame('مسار-تحريري', $article->getTranslation('slug', 'ar'));
        $this->assertSame('editorial-path', $article->getTranslation('slug', 'en'));
        $this->assertSame('مسار-تحريري', $article->slug_ar);
        $this->assertSame('editorial-path', $article->slug_en);
    }

    public function test_shadow_columns_enforce_database_uniqueness_for_manual_slugs(): void
    {
        Article::factory()->create([
            'slug' => [
                'ar' => 'مسار-فريد',
                'en' => 'unique-path',
            ],
        ]);

        $this->expectException(QueryException::class);

        Article::factory()->create([
            'slug' => [
                'ar' => 'مسار-آخر',
                'en' => 'unique-path',
            ],
        ]);
    }

    public function test_projects_and_services_keep_stable_keys_separate_from_localized_slugs(): void
    {
        $project = Project::factory()->create([
            'key' => 'stable-project',
            'slug' => [
                'ar' => 'مشروع-مترجم',
                'en' => 'translated-project',
            ],
        ]);
        $service = Service::factory()->create([
            'key' => 'stable-service',
            'slug' => [
                'ar' => 'خدمة-مترجمة',
                'en' => 'translated-service',
            ],
        ]);

        $this->assertSame('stable-project', $project->toPortfolioArray('ar')['key']);
        $this->assertSame('مشروع-مترجم', $project->toPortfolioArray('ar')['id']);
        $this->assertSame('translated-project', $project->getLocalizedRouteKey('en'));
        $this->assertSame('stable-service', $service->toPublicArray('en')['key']);
        $this->assertSame('translated-service', $service->toPublicArray('en')['id']);
        $this->assertSame('خدمة-مترجمة', $service->getLocalizedRouteKey('ar'));
    }

    public function test_localized_slug_migrations_can_roll_back_and_reapply_cleanly(): void
    {
        $promotionMigration = require database_path('migrations/2026_07_16_110714_promote_localized_slugs_on_public_content_tables.php');
        $expansionMigration = require database_path('migrations/2026_07_16_110713_add_localized_slug_columns_to_public_content_tables.php');

        $promotionMigration->down();
        $expansionMigration->down();

        $this->assertTrue(Schema::hasColumn('articles', 'slugs'));
        $this->assertFalse(Schema::hasColumn('articles', 'slug'));
        $this->assertTrue(Schema::hasColumn('projects', 'slug'));
        $this->assertFalse(Schema::hasColumn('projects', 'key'));
        $this->assertTrue(Schema::hasColumn('services', 'slug'));
        $this->assertFalse(Schema::hasColumn('services', 'key'));

        $expansionMigration->up();
        $promotionMigration->up();

        $this->assertTrue(Schema::hasColumn('articles', 'slug'));
        $this->assertFalse(Schema::hasColumn('articles', 'slugs'));
        $this->assertTrue(Schema::hasColumn('projects', 'key'));
        $this->assertTrue(Schema::hasColumn('services', 'key'));
    }
}
