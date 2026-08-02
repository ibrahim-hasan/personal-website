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

    public function test_projects_keep_localized_slugs_while_services_use_stable_hub_anchors(): void
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
        ]);

        $this->assertSame('stable-project', $project->toPortfolioArray('ar')['key']);
        $this->assertSame('مشروع-مترجم', $project->toPortfolioArray('ar')['id']);
        $this->assertSame('translated-project', $project->getLocalizedRouteKey('en'));
        $this->assertSame('stable-service', $service->toPublicArray('en')['key']);
        $this->assertSame('service-stable-service', $service->toPublicArray('en')['id']);
    }

    public function test_service_hub_retires_detail_slug_columns_while_projects_keep_localized_slugs(): void
    {
        $this->assertTrue(Schema::hasColumn('projects', 'slug'));
        $this->assertTrue(Schema::hasColumn('projects', 'slug_ar'));
        $this->assertTrue(Schema::hasColumn('projects', 'slug_en'));
        $this->assertTrue(Schema::hasColumn('services', 'key'));
        $this->assertFalse(Schema::hasColumn('services', 'slug'));
        $this->assertFalse(Schema::hasColumn('services', 'slug_ar'));
        $this->assertFalse(Schema::hasColumn('services', 'slug_en'));
        $this->assertFalse(Schema::hasColumn('services', 'seo_title'));
        $this->assertFalse(Schema::hasColumn('services', 'seo_description'));
    }
}
