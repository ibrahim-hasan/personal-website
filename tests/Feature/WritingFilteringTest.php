<?php

namespace Tests\Feature;

use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WritingFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ArticleSeeder::class);
    }

    public function test_topic_filters_are_server_rendered_and_use_the_unfiltered_canonical_url(): void
    {
        $articles = app(ArticleCatalog::class)->localized('en', includeBody: false);
        $matchingArticle = collect($articles)->first(
            fn (array $article): bool => in_array('data', $article['topic_keys'], true),
        );
        $otherArticle = collect($articles)->first(
            fn (array $article): bool => ! in_array('data', $article['topic_keys'], true),
        );

        $this->assertIsArray($matchingArticle);
        $this->assertIsArray($otherArticle);

        $this->get('/en/writing?topic=data')
            ->assertOk()
            ->assertSee($matchingArticle['title'], false)
            ->assertDontSee($otherArticle['title'], false)
            ->assertSee('href="'.localized_route('writing', locale: 'en').'"', false)
            ->assertSee('noindex, follow, noarchive', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('data-overflow-rail', false);
    }

    public function test_unknown_topic_is_not_exposed_and_an_empty_topic_returns_to_the_canonical_index(): void
    {
        $this->get('/writing?topic=not-a-public-topic')->assertNotFound();

        $this->get('/en/writing?topic=')
            ->assertRedirect('/en/writing');
    }

    public function test_writing_filters_do_not_depend_on_alpine_visibility_rules(): void
    {
        $response = $this->get('/writing?topic=data')->assertOk();

        $this->assertStringContainsString('data-overflow-rail', $response->getContent());
        $this->assertStringNotContainsString('x-show="matches(', $response->getContent());
        $this->assertStringNotContainsString('articleLibrary', $response->getContent());
    }
}
