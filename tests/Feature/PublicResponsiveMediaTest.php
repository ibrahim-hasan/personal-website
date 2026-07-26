<?php

namespace Tests\Feature;

use App\Enums\ProjectAssetPermissionStatus;
use App\Enums\ProjectDisclosureLevel;
use App\Enums\ProjectPermissionStatus;
use App\Models\Article;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Tests\TestCase;

class PublicResponsiveMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_hero_uses_responsive_conversion_data_with_intrinsic_dimensions(): void
    {
        Storage::fake('public');

        $article = Article::factory()->create([
            'key' => 'responsive-article',
            'slug' => ['ar' => 'مقال-متجاوب', 'en' => 'responsive-article'],
            'image' => null,
        ]);

        $media = $article
            ->addMedia(UploadedFile::fake()->image('article.jpg', 1800, 1200))
            ->toMediaCollection(Article::IMAGE_COLLECTION);

        $article->refresh();
        $image = $article->responsiveImage();

        $this->assertTrue($media->fresh()->hasResponsiveImages(Article::IMAGE_CONVERSION));
        $this->assertStringContainsString(Article::IMAGE_CONVERSION, $image['src']);
        $this->assertNotSame('', $image['srcset']);
        $this->assertSame(Article::HERO_WIDTH, $image['width']);
        $this->assertSame(Article::HERO_HEIGHT, $image['height']);

        $this->get(localized_route('writing.show', ['article' => $article], locale: 'en'))
            ->assertOk()
            ->assertSee('srcset=', false)
            ->assertSee('sizes="(min-width: 86rem) 86rem, calc(100vw - 2rem)"', false)
            ->assertSee('width="1600"', false)
            ->assertSee('height="900"', false)
            ->assertSee('loading="eager"', false)
            ->assertSee('fetchpriority="high"', false);
    }

    public function test_project_media_uses_responsive_conversions_without_bypassing_permission_or_anonymization_gates(): void
    {
        Storage::fake('public');

        $project = Project::factory()->create([
            'image' => null,
            'logo' => null,
            'image_permission_status' => ProjectAssetPermissionStatus::Approved,
            'image_permission_reference' => 'approved image use',
            'logo_permission_status' => ProjectAssetPermissionStatus::Approved,
            'logo_permission_reference' => 'approved logo use',
        ]);

        $project
            ->addMedia(UploadedFile::fake()->image('project.jpg', 1680, 1080))
            ->toMediaCollection(Project::IMAGE_COLLECTION);
        $project
            ->addMedia(UploadedFile::fake()->image('logo.png', 800, 400))
            ->toMediaCollection(Project::LOGO_COLLECTION);

        $project->refresh();
        $portfolioProject = $project->toPortfolioArray('en');

        $this->assertStringContainsString(Project::THUMBNAIL_CONVERSION, $portfolioProject['image_media']['src']);
        $this->assertNotSame('', $portfolioProject['image_media']['srcset']);
        $this->assertSame(Project::CARD_WIDTH, $portfolioProject['image_media']['width']);
        $this->assertSame(Project::CARD_HEIGHT, $portfolioProject['image_media']['height']);
        $this->assertStringContainsString(Project::LOGO_CONVERSION, $portfolioProject['logo_media']['src']);
        $this->assertNotSame('', $portfolioProject['logo_media']['srcset']);
        $this->assertSame(Project::LOGO_WIDTH, $portfolioProject['logo_media']['width']);
        $this->assertSame(Project::LOGO_HEIGHT, $portfolioProject['logo_media']['height']);

        $project->forceFill([
            'permission_status' => ProjectPermissionStatus::ApprovedAnonymized,
            'disclosure_level' => ProjectDisclosureLevel::Anonymized,
        ])->save();

        $hiddenProject = $project->fresh()->toPortfolioArray('en');

        $this->assertSame('', $hiddenProject['image']);
        $this->assertSame('', $hiddenProject['image_media']['src']);
        $this->assertSame('', $hiddenProject['image_media']['srcset']);
        $this->assertSame('', $hiddenProject['logo']);
        $this->assertSame('', $hiddenProject['logo_media']['src']);
        $this->assertSame('', $hiddenProject['logo_media']['srcset']);
    }

    public function test_jpeg_conversion_optimizer_retains_the_configured_metadata_stripping_option(): void
    {
        $optimizers = config('media-library.image_optimizers');

        $this->assertArrayHasKey(Jpegoptim::class, $optimizers);
        $this->assertContains('--strip-all', $optimizers[Jpegoptim::class]);
    }
}
