<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\ArticleNarrationStatus;
use App\Jobs\PrepareArticleNarration;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Services\OpenAI\OpenAiNarrationEditor;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueMissingArticleNarrationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ArticleSeeder::class);
        Queue::fake();
    }

    public function test_it_queues_only_articles_without_a_current_narration_script(): void
    {
        $catalog = app(ArticleCatalog::class);
        $scripts = app(ArticleNarrationScript::class);
        $current = $catalog->findByKey('ai-value');
        $preparing = $catalog->findByKey('ai-not-answer');

        $this->assertNotNull($current);
        $this->assertNotNull($preparing);

        $currentScript = $scripts->build($current, 'ar');
        ArticleNarration::factory()->create([
            'article_key' => $current->key,
            'locale' => 'ar',
            'status' => ArticleNarrationStatus::Draft,
            'source_hash' => hash('sha256', $currentScript),
            'script' => $currentScript,
            'prompt_version' => OpenAiNarrationEditor::promptVersion(),
        ]);

        ArticleNarration::factory()->preparing()->create([
            'article_key' => $preparing->key,
            'locale' => 'ar',
            'source_hash' => $scripts->fingerprint($preparing, 'ar'),
            'preparation_started_at' => now(),
        ]);

        $this->artisan('articles:queue-missing-narrations', ['--locale' => 'ar'])
            ->expectsOutputToContain('Queued 7 narration preparations')
            ->assertExitCode(0);

        Queue::assertPushed(PrepareArticleNarration::class, 7);
        Queue::assertNotPushed(
            PrepareArticleNarration::class,
            fn (PrepareArticleNarration $job): bool => in_array($job->articleKey, [$current->key, $preparing->key], true),
        );
    }

    public function test_it_requeues_a_legacy_narration_policy_even_when_the_article_text_is_current(): void
    {
        $catalog = app(ArticleCatalog::class);
        $scripts = app(ArticleNarrationScript::class);
        $article = $catalog->findByKey('ai-value');

        $this->assertNotNull($article);

        $source = $scripts->build($article, 'ar');
        ArticleNarration::factory()->create([
            'article_key' => $article->key,
            'locale' => 'ar',
            'status' => ArticleNarrationStatus::Approved,
            'source_hash' => hash('sha256', $source),
            'script' => $source,
            'prompt_version' => 'arabic-editorial-v5',
            'approved_at' => now(),
        ]);

        $this->artisan('articles:queue-missing-narrations', ['--locale' => 'ar'])
            ->expectsOutputToContain('Queued 9 narration preparations')
            ->assertExitCode(0);

        Queue::assertPushed(
            PrepareArticleNarration::class,
            fn (PrepareArticleNarration $job): bool => $job->articleKey === $article->key && $job->locale === 'ar',
        );
    }

    public function test_it_rejects_unsupported_locales(): void
    {
        $this->artisan('articles:queue-missing-narrations', ['--locale' => 'tr'])
            ->expectsOutputToContain('Unsupported locale(s): tr')
            ->assertExitCode(1);

        Queue::assertNothingPushed();
    }
}
