<?php

namespace Tests\Feature\ArticleAudio;

use App\Contracts\ArticleAudio\NarrationEditor;
use App\Data\ArticleAudio\NarrationDraft;
use App\Enums\ArticleNarrationStatus;
use App\Jobs\PrepareArticleNarration;
use App\Models\ArticleNarration;
use App\Services\ArticleAudio\ArticleNarrationScript;
use App\Support\Ai\NarrationExecutionBudget;
use App\Support\Editorial\ArticleCatalog;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PrepareArticleNarrationJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_timeout_always_covers_every_provider_attempt_and_retry_delay(): void
    {
        config()->set('services.openai.timeout', 180);

        $job = new PrepareArticleNarration('ai-value', 'ar');

        $this->assertSame(NarrationExecutionBudget::jobTimeout(), $job->timeout);
        $this->assertGreaterThanOrEqual(NarrationExecutionBudget::minimumJobTimeout(), $job->timeout);
        $this->assertLessThan(650, $job->timeout);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ArticleSeeder::class);
    }

    public function test_job_prepares_a_reviewable_draft_without_approving_it(): void
    {
        $article = app(ArticleCatalog::class)->findByKey('ai-value');
        $this->assertNotNull($article);
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');
        $prepared = $source;

        app()->instance(NarrationEditor::class, new class($prepared) implements NarrationEditor
        {
            public function __construct(private readonly string $script) {}

            public function prepare(string $source, string $locale): NarrationDraft
            {
                return new NarrationDraft(
                    script: $this->script,
                    notes: ['Added contextual Arabic diacritics.'],
                    pronunciationNotes: ['Clarified one acronym.'],
                    model: 'test-editor-model',
                    promptVersion: 'test-v1',
                );
            }
        });

        $job = new PrepareArticleNarration('ai-value', 'ar');
        app()->call([$job, 'handle']);

        $narration = ArticleNarration::query()->firstOrFail();

        $this->assertSame(ArticleNarrationStatus::Draft, $narration->status);
        $this->assertSame(hash('sha256', $source), $narration->source_hash);
        $this->assertSame($prepared, $narration->script);
        $this->assertSame('test-editor-model', $narration->preparation_model);
        $this->assertSame(['Added contextual Arabic diacritics.'], $narration->preparation_notes);
        $this->assertNull($narration->approved_at);
        $this->assertNotNull($narration->prepared_at);
    }

    public function test_failed_repreparation_keeps_the_last_approved_script_and_redacts_the_key(): void
    {
        config()->set('ai.providers.openai.key', 'private-openai-key');
        $article = app(ArticleCatalog::class)->findByKey('ai-value');
        $this->assertNotNull($article);
        $source = app(ArticleNarrationScript::class)->build($article, 'ar');
        $narration = ArticleNarration::factory()->approved()->create([
            'article_key' => 'ai-value',
            'locale' => 'ar',
            'source_hash' => hash('sha256', $source),
            'script' => $source,
        ]);

        $job = new PrepareArticleNarration('ai-value', 'ar');
        $job->failed(new RuntimeException('Provider rejected private-openai-key.'));

        $narration->refresh();

        $this->assertSame(ArticleNarrationStatus::Failed, $narration->status);
        $this->assertSame($source, $narration->script);
        $this->assertNotNull($narration->approved_at);
        $this->assertStringContainsString('[redacted]', $narration->last_error);
        $this->assertStringNotContainsString('private-openai-key', $narration->last_error);
    }
}
