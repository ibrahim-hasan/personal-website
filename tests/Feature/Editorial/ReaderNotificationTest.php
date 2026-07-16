<?php

namespace Tests\Feature\Editorial;

use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentApprovedNotification;
use App\Notifications\CommentReplyNotification;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class ReaderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_library_displays_recent_reader_updates_and_their_unread_state(): void
    {
        $article = Article::factory()->create([
            'title' => ['ar' => 'مقال تنبيهات القارئ', 'en' => 'Reader notification article'],
        ]);
        $reader = User::factory()->create();
        $replyAuthor = User::factory()->create(['name' => 'Thoughtful Reader']);
        $comment = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        $reply = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $replyAuthor->getKey(),
            'parent_id' => $comment->getKey(),
        ]);

        $this->storeNotification($reader, new CommentApprovedNotification($comment));
        $this->storeNotification($reader, new CommentReplyNotification($reply));

        $this->actingAs($reader)
            ->get('/en/reader/library')
            ->assertOk()
            ->assertSee('What changed since your last visit')
            ->assertSee('Your contribution is now live')
            ->assertSee('A reader replied to your contribution')
            ->assertSee('Thoughtful Reader added a reply to your contribution.')
            ->assertSee('Reader notification article')
            ->assertSee('2 unread updates')
            ->assertSee('Unread');

        $this->actingAs($reader)
            ->get('/reader/library')
            ->assertOk()
            ->assertSee('ما الذي استجد منذ زيارتك الأخيرة؟')
            ->assertSee('مقال تنبيهات القارئ')
            ->assertSee('تحديثان غير مقروءين');
    }

    public function test_the_library_has_a_purposeful_empty_notification_state(): void
    {
        $reader = User::factory()->create();

        $this->actingAs($reader)
            ->get('/en/reader/library')
            ->assertOk()
            ->assertSee('All caught up')
            ->assertSee('No reader updates yet')
            ->assertSee('When your contribution is approved or someone replies, the update will appear here.');
    }

    public function test_a_reader_cannot_see_or_open_another_readers_notification(): void
    {
        $article = Article::factory()->create([
            'title' => ['ar' => 'مقال خاص', 'en' => 'Private notification article'],
        ]);
        $owner = User::factory()->create();
        $otherReader = User::factory()->create();
        $comment = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $owner->getKey(),
        ]);
        $notification = $this->storeNotification($owner, new CommentApprovedNotification($comment));

        $this->actingAs($otherReader)
            ->get('/en/reader/library')
            ->assertOk()
            ->assertDontSee('Private notification article');

        $this->actingAs($otherReader)
            ->post('/en/reader/notifications/'.$notification->getKey().'/read')
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_opening_an_update_marks_it_read_and_redirects_to_the_localized_comment(): void
    {
        $article = Article::factory()->create([
            'slugs' => ['ar' => 'مقال-التنبيه', 'en' => 'notification-article'],
        ]);
        $reader = User::factory()->create();
        $comment = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        $notification = $this->storeNotification($reader, new CommentApprovedNotification($comment));
        $catalog = app(ArticleCatalog::class);
        $editorialArticle = $catalog->findByKey($article->key);

        $this->assertNotNull($editorialArticle);

        $this->actingAs($reader)
            ->post('/en/reader/notifications/'.$notification->getKey().'/read')
            ->assertRedirect(
                $catalog->url($editorialArticle, 'en').'#comment-'.$comment->getKey(),
            );

        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($reader)
            ->get('/en/reader/library')
            ->assertOk()
            ->assertSee('All caught up')
            ->assertDontSee('Unread');
    }

    public function test_an_unverified_reader_cannot_mark_a_notification_as_read(): void
    {
        $article = Article::factory()->create();
        $reader = User::factory()->unverified()->create();
        $comment = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        $notification = $this->storeNotification($reader, new CommentApprovedNotification($comment));

        $this->actingAs($reader)
            ->post('/en/reader/notifications/'.$notification->getKey().'/read')
            ->assertRedirect('/en/reader/verify-email');

        $this->assertNull($notification->fresh()->read_at);
    }

    private function storeNotification(User $reader, object $notification): DatabaseNotification
    {
        $reader->notifyNow($notification, ['database']);

        return $reader->notifications()->latest()->firstOrFail();
    }
}
