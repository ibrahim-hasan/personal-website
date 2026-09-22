<?php

namespace Tests\Feature\AgentRafeeq;

use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectPermissionStatus;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Services\AgentRafeeq\PublicContentExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PublicContentExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_only_published_public_fields_with_one_record_per_bilingual_item(): void
    {
        config()->set('app.url', 'https://ibrahimhasan.net');
        app('url')->forceRootUrl('https://ibrahimhasan.net');
        app()->setLocale('en');
        Project::factory()->create([
            'key' => 'public-work',
            'permission_reference' => 'PRIVATE-PERMISSION',
            'image_permission_reference' => 'PRIVATE-IMAGE-PERMISSION',
            'disclosure_level' => ProjectDisclosureLevel::Anonymized,
            'permission_status' => ProjectPermissionStatus::ApprovedAnonymized,
            'case_study_sections' => ['en' => ['context' => 'PRIVATE-CASE-STUDY']],
        ]);
        Project::factory()->create(['key' => 'inactive-work', 'is_active' => false]);
        Service::factory()->create(['key' => 'public-service']);
        Service::factory()->draft()->create(['key' => 'draft-service']);
        Service::factory()->inactive()->create(['key' => 'inactive-service']);
        Article::factory()->create(['key' => 'public-article']);
        Article::factory()->create(['key' => 'draft-article', 'is_published' => false]);
        Article::factory()->create(['key' => 'future-article', 'published_at' => today()->addWeek()]);
        $deleted = Article::factory()->create(['key' => 'deleted-article']);
        $deleted->delete();
        Setting::setValue('private_token', 'PRIVATE-SETTING', 'private');

        $manifest = app(PublicContentExporter::class)->manifest();
        $items = collect($manifest['items'])->keyBy('external_id');

        $this->assertCount(3, $items);
        $this->assertSame('en', app()->getLocale());
        $this->assertSame('https://ibrahimhasan.net', $manifest['source']['url']);
        $project = $items['ibrahim-website:work:public-work'];
        $this->assertSame(['ar', 'en'], array_keys($project['metadata']['localizations']));
        $this->assertNull($project['image_url']);
        $this->assertSame('https://ibrahimhasan.net/en/work#project-public-work', $project['metadata']['localizations']['en']['url']);
        $this->assertSame('service-public-service', $items['ibrahim-website:service:public-service']['metadata']['localizations']['en']['public_anchor']);
        $this->assertNotEmpty($items['ibrahim-website:article:public-article']['metadata']['localizations']['en']['body']);
        $json = json_encode($manifest, JSON_THROW_ON_ERROR);
        foreach (['PRIVATE-', 'draft-article', 'future-article', 'deleted-article', 'draft-service', 'inactive-service', 'inactive-work'] as $private) {
            $this->assertStringNotContainsString($private, $json);
        }
    }

    public function test_a_missing_project_translation_is_not_fabricated_from_the_fallback_language(): void
    {
        Project::factory()->create(['key' => 'arabic-only', 'title' => ['ar' => 'مشروع منشور'], 'summary' => ['ar' => 'ملخص منشور']]);

        $manifest = app(PublicContentExporter::class)->manifest();
        $this->assertSame(['ar'], array_keys($manifest['items'][0]['metadata']['localizations']));
    }

    public function test_empty_exports_fail_closed_instead_of_deleting_everything(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Empty exports cannot withdraw all content');

        app(PublicContentExporter::class)->manifest();
    }
}
