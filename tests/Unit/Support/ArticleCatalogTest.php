<?php

namespace Tests\Unit\Support;

use App\Support\Editorial\ArticleCatalog;
use Tests\TestCase;

class ArticleCatalogTest extends TestCase
{
    public function test_catalog_contains_ten_articles_with_unique_localized_slugs(): void
    {
        $catalog = app(ArticleCatalog::class);
        $articles = $catalog->all();

        $this->assertCount(10, $articles);
        $this->assertCount(10, array_unique(array_map(fn ($article): string => $article->key, $articles)));

        foreach (['ar', 'en'] as $locale) {
            $slugs = array_map(fn ($article): string => $article->slug($locale), $articles);

            $this->assertCount(10, array_unique($slugs));

            foreach ($slugs as $slug) {
                $this->assertNotSame('', trim($slug));
                $this->assertNotNull($catalog->resolve($slug, $locale));
            }
        }
    }

    public function test_every_article_has_complete_localized_editorial_content_and_an_existing_image(): void
    {
        $catalog = app(ArticleCatalog::class);

        foreach (['ar', 'en'] as $locale) {
            foreach ($catalog->localized($locale) as $article) {
                foreach (['title', 'summary', 'seo_title', 'seo_description', 'type', 'body_html', 'image_alt', 'read_time'] as $field) {
                    $this->assertNotSame('', trim($article[$field]), "Missing {$field} for {$article['key']} in {$locale}");
                }

                $this->assertGreaterThanOrEqual(4, count($article['headings']));
                $this->assertLessThanOrEqual(5, count($article['headings']));
                $this->assertNotEmpty($article['topics']);
                $this->assertFileExists(public_path($article['image']));
            }
        }
    }

    public function test_featured_and_related_collections_are_bounded_and_exclude_the_current_article(): void
    {
        $catalog = app(ArticleCatalog::class);
        $current = $catalog->all()[0];

        $this->assertCount(3, $catalog->featured());

        $related = $catalog->related($current);

        $this->assertCount(3, $related);
        $this->assertNotContains($current->key, array_column($related, 'key'));
    }
}
