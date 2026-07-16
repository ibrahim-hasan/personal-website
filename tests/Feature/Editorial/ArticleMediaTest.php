<?php

namespace Tests\Feature\Editorial;

use App\Models\Article;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_prefers_media_image_and_generates_article_conversions(): void
    {
        Storage::fake('public');

        $article = Article::factory()->create([
            'key' => 'media-article',
            'image' => null,
        ]);

        $media = $article
            ->addMedia(UploadedFile::fake()->image('article.jpg', 1800, 1200))
            ->toMediaCollection(Article::IMAGE_COLLECTION);

        $catalogArticle = app(ArticleCatalog::class)->findByKey('media-article');

        $this->assertNotNull($catalogArticle);
        $this->assertStringContainsString('/storage/', $catalogArticle->image);
        $this->assertStringContainsString(Article::IMAGE_CONVERSION, $catalogArticle->image);
        $this->assertFileExists($media->getPath(Article::IMAGE_CONVERSION));
        $this->assertFileExists($media->getPath(Article::THUMBNAIL_CONVERSION));
    }

    public function test_article_media_collection_replaces_the_previous_image(): void
    {
        Storage::fake('public');

        $article = Article::factory()->create(['image' => null]);

        $article->addMedia(UploadedFile::fake()->image('first.jpg'))->toMediaCollection(Article::IMAGE_COLLECTION);
        $article->addMedia(UploadedFile::fake()->image('replacement.jpg'))->toMediaCollection(Article::IMAGE_COLLECTION);

        $article->refresh();

        $this->assertCount(1, $article->getMedia(Article::IMAGE_COLLECTION));
        $this->assertSame('replacement.jpg', $article->getFirstMedia(Article::IMAGE_COLLECTION)?->file_name);
    }

    public function test_legacy_article_media_backfill_is_idempotent(): void
    {
        Storage::fake('public');

        $article = Article::factory()->create([
            'image' => 'images/ibrahim/ibrahim-speaking-hero.webp',
        ]);

        $this->artisan('articles:backfill-media')->assertSuccessful();
        $this->artisan('articles:backfill-media')->assertSuccessful();

        $this->assertCount(1, $article->fresh()->getMedia(Article::IMAGE_COLLECTION));
    }
}
