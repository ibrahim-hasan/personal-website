<?php

namespace Tests\Feature\Editorial;

use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\ArticleAppreciation;
use App\Models\ArticleBookmark;
use App\Models\ArticleReadingProgress;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReaderAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_reader_account_is_discoverable_and_preserves_the_intended_locale(): void
    {
        $this->get('/en')
            ->assertOk()
            ->assertSee('href="'.url('/en/reader/login').'"', false);

        $this->get('/en/reader/account')
            ->assertRedirect('/en/reader/login')
            ->assertSessionHas('url.intended', url('/en/reader/account'));

        $reader = User::factory()->create();

        $this->actingAs($reader)
            ->get('/en')
            ->assertOk()
            ->assertSee('href="'.url('/en/reader/library').'"', false)
            ->assertSee('href="'.url('/en/reader/account').'"', false)
            ->assertSee(__('reader_auth.logout', locale: 'en'));
    }

    public function test_an_unverified_reader_can_open_their_account_with_activity_stats(): void
    {
        $reader = User::factory()->unverified()->create();
        $article = Article::factory()->create();
        ArticleBookmark::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        ArticleReadingProgress::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        Comment::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);

        $this->actingAs($reader)
            ->get('/reader/account')
            ->assertOk()
            ->assertViewHas('reader', fn (User $value): bool => $value->is($reader))
            ->assertViewHas('stats', [
                'bookmarks' => 1,
                'progress' => 1,
                'comments' => 1,
            ])
            ->assertViewHas('canDeleteAccount', true);
    }

    public function test_a_reader_can_update_their_name_without_reentering_their_password(): void
    {
        $reader = User::factory()->create([
            'email' => 'reader@example.com',
            'password' => 'reader-password',
        ]);

        $this->actingAs($reader)
            ->patch('/reader/account', [
                'name' => 'Updated Reader',
                'email' => ' READER@EXAMPLE.COM ',
            ])
            ->assertRedirect('/reader/account')
            ->assertSessionHas('status', __('reader_auth.profile_updated'));

        $reader->refresh();
        $this->assertSame('Updated Reader', $reader->name);
        $this->assertSame('reader@example.com', $reader->email);
        $this->assertTrue($reader->hasVerifiedEmail());
    }

    public function test_changing_email_requires_the_current_password_and_restarts_verification(): void
    {
        Notification::fake();
        $reader = User::factory()->create([
            'email' => 'reader@example.com',
            'password' => 'reader-password',
        ]);

        $this->actingAs($reader)
            ->patch('/reader/account', [
                'name' => $reader->name,
                'email' => 'new-reader@example.com',
            ])
            ->assertSessionHasErrorsIn('profile', ['current_password']);

        $this->assertSame('reader@example.com', $reader->fresh()->email);

        $this->actingAs($reader)
            ->patch('/reader/account', [
                'name' => $reader->name,
                'email' => ' NEW-READER@EXAMPLE.COM ',
                'current_password' => 'reader-password',
            ])
            ->assertRedirect('/reader/verify-email')
            ->assertSessionHas('status', __('reader_auth.email_changed_verify'));

        $reader->refresh();
        $this->assertSame('new-reader@example.com', $reader->email);
        $this->assertFalse($reader->hasVerifiedEmail());
        Notification::assertSentTo($reader, VerifyEmail::class);
    }

    public function test_profile_email_must_remain_unique(): void
    {
        $reader = User::factory()->create([
            'password' => 'reader-password',
        ]);
        $existingReader = User::factory()->create();

        $this->actingAs($reader)
            ->patch('/reader/account', [
                'name' => $reader->name,
                'email' => $existingReader->email,
                'current_password' => 'reader-password',
            ])
            ->assertSessionHasErrorsIn('profile', ['email']);

        $this->assertNotSame($existingReader->email, $reader->fresh()->email);
    }

    public function test_password_update_rotates_the_remember_token_and_removes_other_database_sessions(): void
    {
        config(['session.driver' => 'database']);

        $reader = User::factory()->create([
            'password' => 'reader-password',
        ]);
        $otherReader = User::factory()->create();
        $oldRememberToken = $reader->remember_token;
        $this->insertSession('reader-session-one', $reader);
        $this->insertSession('reader-session-two', $reader);
        $this->insertSession('other-reader-session', $otherReader);

        $this->actingAs($reader)
            ->put('/reader/account/password', [
                'current_password' => 'reader-password',
                'password' => 'new-secure-reader-password!42',
                'password_confirmation' => 'new-secure-reader-password!42',
            ])
            ->assertRedirect('/reader/account')
            ->assertSessionHas('status', __('reader_auth.password_updated'));

        $reader->refresh();
        $this->assertTrue(Hash::check('new-secure-reader-password!42', $reader->password));
        $this->assertNotSame($oldRememberToken, $reader->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'reader-session-one']);
        $this->assertDatabaseMissing('sessions', ['id' => 'reader-session-two']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-reader-session']);
        $this->assertAuthenticatedAs($reader);
    }

    public function test_deleting_a_reader_account_removes_private_data_and_anonymizes_comments(): void
    {
        $reader = User::factory()->create([
            'email' => 'reader@example.com',
            'password' => 'reader-password',
        ]);
        $article = Article::factory()->create();
        $comment = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        $pendingComment = Comment::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        $rejectedComment = Comment::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
            'status' => CommentStatus::Rejected,
        ]);
        $deletedComment = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        $deletedComment->delete();
        $reportedComment = Comment::factory()->approved()->create(['article_id' => $article->getKey()]);
        ArticleBookmark::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        ArticleAppreciation::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        ArticleReadingProgress::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $reader->getKey(),
        ]);
        CommentReport::factory()->create([
            'comment_id' => $reportedComment->getKey(),
            'reporter_user_id' => $reader->getKey(),
        ]);
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'reader-test-notification',
            'notifiable_type' => User::class,
            'notifiable_id' => $reader->getKey(),
            'data' => '{}',
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->insertSession('reader-session', $reader);
        DB::table('password_reset_tokens')->insert([
            'email' => $reader->email,
            'token' => Hash::make('reset-token'),
            'created_at' => now(),
        ]);
        $this->insertAiConversationData($reader);

        $this->actingAs($reader)
            ->delete('/reader/account', [
                'current_password' => 'reader-password',
                'acknowledgement' => '1',
            ])
            ->assertRedirect(localized_route('home'))
            ->assertSessionHas('status', __('reader_auth.account_deleted'));

        $this->assertGuest();
        $this->assertModelMissing($reader);
        $this->assertDatabaseHas('comments', [
            'id' => $comment->getKey(),
            'user_id' => null,
        ]);
        $this->assertDatabaseMissing('comments', ['id' => $pendingComment->getKey()]);
        $this->assertDatabaseMissing('comments', ['id' => $rejectedComment->getKey()]);
        $this->assertDatabaseMissing('comments', ['id' => $deletedComment->getKey()]);
        $this->assertDatabaseMissing('article_bookmarks', ['user_id' => $reader->getKey()]);
        $this->assertDatabaseMissing('article_appreciations', ['user_id' => $reader->getKey()]);
        $this->assertDatabaseMissing('article_reading_progress', ['user_id' => $reader->getKey()]);
        $this->assertDatabaseMissing('comment_reports', ['reporter_user_id' => $reader->getKey()]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $reader->getKey()]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $reader->getKey()]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $reader->email]);
        $this->assertDatabaseMissing('agent_conversations', ['user_id' => $reader->getKey()]);
        $this->assertDatabaseMissing('agent_conversation_messages', ['user_id' => $reader->getKey()]);
        $this->assertDatabaseMissing('agent_conversation_messages', [
            'conversation_id' => 'reader-conversation',
        ]);
        $this->assertDatabaseHas('agent_conversations', ['id' => 'other-conversation']);
        $this->assertDatabaseHas('agent_conversation_messages', ['id' => 'other-message']);
    }

    public function test_account_deletion_requires_the_current_password_in_its_named_error_bag(): void
    {
        $reader = User::factory()->create([
            'password' => 'reader-password',
        ]);

        $this->actingAs($reader)
            ->delete('/reader/account', [
                'current_password' => 'wrong-password',
                'acknowledgement' => '1',
            ])
            ->assertSessionHasErrorsIn('accountDeletion', ['current_password']);

        $this->assertModelExists($reader);
        $this->assertAuthenticatedAs($reader);
    }

    public function test_role_and_permission_bearing_accounts_cannot_use_reader_self_deletion(): void
    {
        $roleAccount = User::factory()->create(['password' => 'reader-password']);
        $role = Role::query()->create([
            'name' => 'protected-account',
            'guard_name' => 'web',
        ]);
        $roleAccount->assignRole($role);

        $this->actingAs($roleAccount)
            ->delete('/reader/account', [
                'current_password' => 'reader-password',
                'acknowledgement' => '1',
            ])
            ->assertForbidden();

        $this->assertModelExists($roleAccount);

        $permissionAccount = User::factory()->create(['password' => 'reader-password']);
        $permissionAccount->givePermissionTo(Permission::findOrCreate('view_any articles', 'web'));

        $this->actingAs($permissionAccount)
            ->delete('/reader/account', [
                'current_password' => 'reader-password',
                'acknowledgement' => '1',
            ])
            ->assertForbidden();

        $this->assertModelExists($permissionAccount);
    }

    private function insertSession(string $id, User $reader): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $reader->getKey(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test-session',
            'last_activity' => now()->getTimestamp(),
        ]);
    }

    private function insertAiConversationData(User $reader): void
    {
        DB::table('agent_conversations')->insert([
            [
                'id' => 'reader-conversation',
                'user_id' => $reader->getKey(),
                'title' => 'Private reader conversation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'other-conversation',
                'user_id' => null,
                'title' => 'Other conversation',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('agent_conversation_messages')->insert([
            $this->aiMessage('reader-owned-message', 'reader-conversation', null),
            $this->aiMessage('reader-authored-message', 'other-conversation', $reader->getKey()),
            $this->aiMessage('other-message', 'other-conversation', null),
        ]);
    }

    /** @return array<string, mixed> */
    private function aiMessage(string $id, string $conversationId, ?int $userId): array
    {
        return [
            'id' => $id,
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'agent' => 'reader-account-test',
            'role' => 'user',
            'content' => 'Private conversation content',
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '[]',
            'meta' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
