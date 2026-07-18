<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleReadingProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LivewireUpdateEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_livewire_updates_use_a_stable_protected_endpoint(): void
    {
        $updateRoute = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route): bool => $route->getName() === 'livewire.update');

        $this->assertNotNull($updateRoute);
        $this->assertSame('livewire/update', $updateRoute->uri());
        $this->assertSame(['POST'], $updateRoute->methods());
        $this->post('/livewire/update')->assertNotFound();
    }

    public function test_livewire_renders_the_stable_update_uri(): void
    {
        $this->assertSame('/livewire/update', app('livewire')->getUpdateUri());
    }

    public function test_an_authenticated_article_progress_update_reaches_the_livewire_endpoint(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create(['locale_preference' => 'ar']);
        $csrfToken = 'livewire-endpoint-test-token';

        $page = $this->actingAs($reader)
            ->withSession(['_token' => $csrfToken])
            ->get('/en/writing/'.$article->getTranslation('slug', 'en'));

        $page->assertOk();

        preg_match('/wire:snapshot="([^"]+)"/', $page->getContent(), $matches);

        $this->assertArrayHasKey(1, $matches);

        $response = $this->actingAs($reader)
            ->withSession(['_token' => $csrfToken])
            ->withHeaders([
                'X-CSRF-TOKEN' => $csrfToken,
                'X-Livewire' => 'true',
            ])
            ->postJson('/livewire/update', [
                '_token' => $csrfToken,
                'components' => [[
                    'snapshot' => html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5),
                    'updates' => [],
                    'calls' => [[
                        'method' => 'updateProgress',
                        'params' => [25],
                        'metadata' => [],
                    ]],
                ]],
            ]);

        $response->assertOk();
        $this->assertDatabaseHas(ArticleReadingProgress::class, [
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
            'progress_percent' => 25,
        ]);
    }
}
