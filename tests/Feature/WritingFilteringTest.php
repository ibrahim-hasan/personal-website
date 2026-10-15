<?php

namespace Tests\Feature;

use App\Models\Article;
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
            fn (array $article): bool => in_array('data-knowledge-systems', $article['topic_keys'], true),
        );
        $otherArticle = collect($articles)->first(
            fn (array $article): bool => ! in_array('data-knowledge-systems', $article['topic_keys'], true),
        );

        $this->assertIsArray($matchingArticle);
        $this->assertIsArray($otherArticle);

        $this->get('/en/writing?topic=data-knowledge-systems')
            ->assertOk()
            ->assertSee($matchingArticle['title'], false)
            ->assertDontSee($otherArticle['title'], false)
            ->assertSee('href="'.localized_route('writing', locale: 'en').'"', false)
            ->assertSee('noindex, follow, noarchive', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('class="filter-bar-shell" data-overflow-rail', false)
            ->assertSee('class="filter-bar"', false)
            ->assertSee('data-overflow-rail', false);
    }

    public function test_unknown_topic_is_not_exposed_and_an_empty_topic_returns_to_the_canonical_index(): void
    {
        $this->get('/writing?topic=not-a-public-topic')->assertNotFound();

        $this->get('/en/writing?topic=')
            ->assertRedirect('/en/writing');

        $this->get('/writing?topic[]=leadership')
            ->assertRedirect('/writing');
    }

    public function test_legacy_topics_permanently_redirect_to_available_localized_clusters_and_preserve_other_query_parameters(): void
    {
        $aliases = [
            'leadership' => 'product-strategy-measurement',
            'ai_strategy' => 'ai-adoption-governance',
            'transformation' => 'digital-transformation-operations',
        ];

        foreach (['ar', 'en'] as $locale) {
            foreach ($aliases as $legacyTopic => $currentTopic) {
                $query = [
                    'topic' => $legacyTopic,
                    'utm_source' => 'google',
                    'utm_campaign' => 'Legacy filters & discovery',
                    'return_to' => 'https://example.com/#external',
                    'filters' => ['device' => 'mobile'],
                ];
                $response = $this->get(
                    localized_route('writing', locale: $locale)
                    .'?'.http_build_query($query, encoding_type: PHP_QUERY_RFC3986),
                )->assertMovedPermanently();
                $target = (string) $response->headers->get('Location');

                $this->assertStringStartsWith(localized_route('writing', locale: $locale).'?', $target);
                parse_str((string) parse_url($target, PHP_URL_QUERY), $targetQuery);
                $this->assertSame(array_replace($query, ['topic' => $currentTopic]), $targetQuery);

                $this->get($target)
                    ->assertOk()
                    ->assertSee('rel="canonical" href="'.localized_route('writing', locale: $locale).'"', false)
                    ->assertSee('noindex, follow, noarchive', false);
            }
        }
    }

    public function test_legacy_topics_do_not_redirect_when_their_cluster_has_no_public_articles(): void
    {
        Article::query()->update(['is_published' => false]);
        Article::factory()->create(['topic_keys' => ['data']]);

        foreach (['ar', 'en'] as $locale) {
            $this->get(localized_route('writing', ['topic' => 'leadership'], locale: $locale))
                ->assertNotFound();
        }
    }

    public function test_writing_filters_do_not_depend_on_alpine_visibility_rules(): void
    {
        $response = $this->get('/writing?topic=data-knowledge-systems')->assertOk();

        $this->assertStringContainsString('data-overflow-rail', $response->getContent());
        $this->assertStringNotContainsString('x-show="matches(', $response->getContent());
        $this->assertStringNotContainsString('articleLibrary', $response->getContent());
    }

    public function test_the_reader_facing_topic_index_is_consolidated_into_four_clusters(): void
    {
        $response = $this->get('/en/writing')->assertOk();

        foreach ([
            'AI adoption &amp; governance',
            'Data &amp; knowledge systems',
            'Digital transformation &amp; operations',
            'Product strategy &amp; measurement',
        ] as $cluster) {
            $response->assertSee($cluster, false);
        }

        $this->assertSame(4, substr_count($response->getContent(), '?topic='));
    }
}
