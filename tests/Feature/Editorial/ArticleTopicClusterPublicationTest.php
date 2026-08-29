<?php

namespace Tests\Feature\Editorial;

use App\Actions\Editorial\ArticlePublicationValidator;
use App\Models\Article;
use App\Support\Editorial\ArticleCatalog;
use App\Support\Editorial\ArticleTopicClusters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleTopicClusterPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publication_rejects_unrecognized_topics_and_maps_supported_legacy_topics(): void
    {
        Storage::fake('public');
        $article = Article::factory()->create([
            'is_published' => false,
            'topic_keys' => ['unassigned-topic'],
        ]);
        $article
            ->addMedia(UploadedFile::fake()->image('topic-cluster-hero.jpg', 1600, 900))
            ->toMediaCollection(Article::IMAGE_COLLECTION);
        $validator = app(ArticlePublicationValidator::class);

        $this->assertContains('article.topics_unrecognized', $validator->violations($article, false));
        $this->assertContains('article.topics_unrecognized', $validator->publishReadinessViolations($article));
        $this->assertSame(
            [ArticleTopicClusters::PRODUCT_STRATEGY_MEASUREMENT],
            ArticleTopicClusters::forTopicKeys(['strategy']),
        );

        $article->update(['topic_keys' => ['strategy']]);

        $this->assertNotContains('article.topics_unrecognized', $validator->violations($article, false));
        $this->assertNotContains('article.topics_unrecognized', $validator->publishReadinessViolations($article));
    }

    public function test_an_existing_public_article_with_a_custom_topic_remains_in_the_public_catalog(): void
    {
        $article = Article::factory()->create([
            'key' => 'legacy-public-custom-topic',
            'topic_keys' => ['permissioned-industry-topic'],
            'is_published' => true,
            'published_at' => today()->subDay(),
        ]);
        $validator = app(ArticlePublicationValidator::class);

        $this->assertTrue($validator->isPubliclyEligible($article));
        $this->assertNotNull(app(ArticleCatalog::class)->findByKey($article->key));
        $this->assertContains('article.topics_unrecognized', $validator->violations($article, false));
        $this->assertContains('article.topics_unrecognized', $validator->publishReadinessViolations($article));
    }
}
