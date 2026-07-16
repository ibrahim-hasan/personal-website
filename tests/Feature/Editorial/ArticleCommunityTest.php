<?php

namespace Tests\Feature\Editorial;

use App\Enums\CommentReportStatus;
use App\Enums\CommentStatus;
use App\Livewire\Website\ArticleCommunity;
use App\Models\Article;
use App\Models\ArticleAppreciation;
use App\Models\ArticleBookmark;
use App\Models\ArticleReadingProgress;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ArticleCommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_verified_reader_can_appreciate_once_and_toggle_their_bookmark(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create();

        $component = Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key]);

        $component->call('toggleAppreciation')->assertHasNoErrors();
        $component->call('toggleAppreciation')->assertHasNoErrors();
        $component->call('toggleBookmark')->assertHasNoErrors();
        $component->call('updateProgress', 60)->assertHasNoErrors();

        $this->assertDatabaseCount(ArticleAppreciation::class, 0);
        $this->assertDatabaseHas(ArticleBookmark::class, [
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);

        $this->actingAs($reader)
            ->get('/reader/library')
            ->assertOk()
            ->assertSee($article->getTranslation('title', 'ar'), false)
            ->assertSee('60%', false);
    }

    public function test_new_comments_and_one_level_replies_enter_moderation(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create();
        $parent = Comment::factory()->approved()->create(['article_id' => $article->getKey()]);

        $component = Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key])
            ->set('commentBody', 'A concrete observation that adds to the article.')
            ->call('postComment')
            ->assertStatus(200)
            ->assertHasNoErrors();

        $component
            ->call('startReply', $parent->getKey())
            ->set('replyBody', 'A constructive reply to this observation.')
            ->call('postReply')
            ->assertHasNoErrors();

        $this->assertDatabaseHas(Comment::class, [
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
            'parent_id' => null,
            'status' => CommentStatus::Pending->value,
        ]);
        $this->assertDatabaseHas(Comment::class, [
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
            'parent_id' => $parent->getKey(),
            'status' => CommentStatus::Pending->value,
        ]);
    }

    public function test_community_validation_uses_localized_human_field_names(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create();

        app()->setLocale('ar');

        Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key])
            ->call('postComment')
            ->assertHasErrors(['commentBody' => 'required'])
            ->tap(function ($component): void {
                $this->assertSame('حقل نص المشاركة مطلوب.', $component->errors()->first('commentBody'));
                $this->assertStringNotContainsString('comment body', $component->errors()->first('commentBody'));
            });

        app()->setLocale('en');

        Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key])
            ->call('postComment')
            ->assertHasErrors(['commentBody' => 'required'])
            ->tap(function ($component): void {
                $this->assertSame('The contribution field is required.', $component->errors()->first('commentBody'));
            });
    }

    public function test_a_reader_can_privately_report_an_approved_contribution_only_once(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create();
        $comment = Comment::factory()->approved()->create(['article_id' => $article->getKey()]);

        $component = Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key])
            ->call('openReport', $comment->getKey())
            ->set('reportReason', 'misinformation')
            ->set('reportDetails', 'This claim may need a source.')
            ->call('submitReport')
            ->assertHasNoErrors();

        $component
            ->call('openReport', $comment->getKey())
            ->set('reportReason', 'other')
            ->call('submitReport')
            ->assertHasNoErrors();

        $this->assertDatabaseCount(CommentReport::class, 1);
        $this->assertDatabaseHas(CommentReport::class, [
            'comment_id' => $comment->getKey(),
            'reporter_user_id' => $reader->getKey(),
            'reason' => 'other',
        ]);
    }

    public function test_unverified_readers_cannot_mutate_community_state(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->unverified()->create();

        Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key])
            ->call('toggleAppreciation')
            ->assertHasErrors('auth');

        $this->assertDatabaseCount(ArticleAppreciation::class, 0);
    }

    public function test_reading_progress_is_monotonic_and_completion_is_never_reversed(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create();
        $component = Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key]);

        $component->call('updateProgress', 82)->assertHasNoErrors();
        $component->call('updateProgress', 24)->assertHasNoErrors();

        $progress = ArticleReadingProgress::query()->firstOrFail();
        $this->assertSame(82, $progress->progress_percent);
        $this->assertNull($progress->completed_at);

        $component->call('updateProgress', 97)->assertHasNoErrors();
        $completedAt = $progress->fresh()->completed_at;
        $this->assertNotNull($completedAt);

        $component->call('updateProgress', 10)->assertHasNoErrors();
        $progress->refresh();

        $this->assertSame(97, $progress->progress_percent);
        $this->assertTrue($completedAt->equalTo($progress->completed_at));
    }

    public function test_first_progress_write_is_an_atomic_upsert_on_the_unique_reader_article_key(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create();
        $queries = [];

        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        ArticleReadingProgress::record($reader, $article->getKey(), 96);

        $this->assertTrue(
            collect($queries)->contains(fn (string $query): bool => str_contains($query, 'article_reading_progress')
                && str_contains($query, 'on conflict')),
            'The first progress write must use the unique-key upsert path.',
        );
        $this->assertDatabaseCount(ArticleReadingProgress::class, 1);

        $progress = ArticleReadingProgress::query()->firstOrFail();

        $this->assertSame(96, $progress->progress_percent);
        $this->assertNotNull($progress->completed_at);
    }

    public function test_inactive_readers_cannot_interact_or_record_progress(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create(['is_active' => false]);

        Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key])
            ->call('toggleAppreciation')
            ->assertHasErrors('auth');

        Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key])
            ->call('updateProgress', 70)
            ->assertHasErrors('auth');

        $this->assertDatabaseCount(ArticleAppreciation::class, 0);
        $this->assertDatabaseCount(ArticleReadingProgress::class, 0);
    }

    #[DataProvider('nonDeletedReplyStatuses')]
    public function test_a_reader_cannot_delete_a_root_contribution_with_any_non_deleted_reply(CommentStatus $replyStatus): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create();
        $root = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        $reply = Comment::factory()->create([
            'article_id' => $article->getKey(),
            'parent_id' => $root->getKey(),
            'status' => $replyStatus,
        ]);

        Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key])
            ->call('deleteComment', $root->getKey())
            ->assertForbidden();

        $this->assertModelExists($root);
        $this->assertModelExists($reply);
    }

    /** @return array<string, array{CommentStatus}> */
    public static function nonDeletedReplyStatuses(): array
    {
        return [
            'pending reply' => [CommentStatus::Pending],
            'approved reply' => [CommentStatus::Approved],
            'rejected reply' => [CommentStatus::Rejected],
        ];
    }

    public function test_existing_appreciations_and_bookmarks_toggle_without_unique_constraint_failures(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create();
        ArticleAppreciation::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        ArticleBookmark::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);

        $component = Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key]);

        $component->call('toggleAppreciation')->assertHasNoErrors();
        $component->call('toggleBookmark')->assertHasNoErrors();
        $component->call('toggleAppreciation')->assertHasNoErrors();
        $component->call('toggleBookmark')->assertHasNoErrors();

        $this->assertDatabaseCount(ArticleAppreciation::class, 1);
        $this->assertDatabaseCount(ArticleBookmark::class, 1);
    }

    public function test_deleting_an_unreplied_contribution_closes_its_pending_reports(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->create();
        $comment = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        $report = CommentReport::factory()->create(['comment_id' => $comment->getKey()]);

        Livewire::actingAs($reader)
            ->test(ArticleCommunity::class, ['articleKey' => $article->key])
            ->call('deleteComment', $comment->getKey())
            ->assertHasNoErrors();

        $this->assertSoftDeleted($comment);
        $this->assertSame(CommentReportStatus::Resolved, $report->fresh()->status);
        $this->assertNotNull($report->fresh()->reviewed_at);
    }
}
