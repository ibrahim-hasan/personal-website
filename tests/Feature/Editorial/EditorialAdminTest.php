<?php

namespace Tests\Feature\Editorial;

use App\Enums\CommentReportStatus;
use App\Enums\CommentStatus;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\Comments\Pages\ListComments;
use App\Models\Article;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use App\Notifications\CommentApprovedNotification;
use App\Notifications\CommentReplyNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EditorialAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_a_bilingual_rich_article_draft_with_a_managed_hero(): void
    {
        Storage::fake('public');
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $body = [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => str_repeat('Useful context for a focused article. ', 30)]]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'A clear section']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => str_repeat('A practical next step. ', 30)]]],
            ],
        ];

        $this->bootAdminPanel();

        Livewire::actingAs($admin)
            ->test(CreateArticle::class)
            ->fillForm([
                'key' => 'rich-editor-draft',
                'title' => ['ar' => 'مسودة محرر غني', 'en' => 'Rich editor draft'],
                'slug' => ['ar' => 'مسودة-محرر-غني', 'en' => 'rich-editor-draft'],
                'type' => ['ar' => 'مقال', 'en' => 'Article'],
                'summary' => ['ar' => 'ملخص واضح للمسودة.', 'en' => 'A clear summary for the draft.'],
                'body_ar' => $body,
                'body_en' => $body,
                'image_alt' => ['ar' => 'رسم يوضح سير العمل', 'en' => 'Illustration of a workflow'],
                'image_caption' => ['ar' => null, 'en' => null],
                'published_at' => today()->toDateString(),
                'modified_at' => today()->toDateString(),
                'featured' => false,
                Article::IMAGE_COLLECTION => [UploadedFile::fake()->image('hero.jpg', 1600, 900)],
                'topic_keys' => ['products'],
                'source_url' => null,
                'seo_title' => ['ar' => 'مسودة محرر غني', 'en' => 'Rich Editor Draft'],
                'seo_description' => ['ar' => 'وصف موجز لمسودة مقال غني.', 'en' => 'A concise description for a rich article draft.'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $article = Article::query()->where('key', 'rich-editor-draft')->firstOrFail();

        $this->assertFalse($article->is_published);
        $this->assertSame($body, $article->getTranslation('body', 'ar', false));
        $this->assertSame($body, $article->getTranslation('body', 'en', false));
        $this->assertNotEmpty($article->getTranslations('read_minutes'));
        $this->assertTrue($article->hasMedia(Article::IMAGE_COLLECTION));
    }

    public function test_an_authorized_admin_can_manage_articles_and_moderate_comments(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $article = Article::factory()->create();
        Comment::factory()->create(['article_id' => $article->getKey()]);

        $this->actingAs($admin)
            ->get('/admin/articles')
            ->assertOk()
            ->assertSee($article->key, false);

        $this->actingAs($admin)
            ->get('/admin/articles/'.$article->getKey().'/edit')
            ->assertOk()
            ->assertSee(__('editorial_admin.fields.image_path'))
            ->assertSee(__('editorial_admin.hints.image_upload'))
            ->assertSee(__('editorial_admin.fields.body'))
            ->assertSee(__('editorial_admin.hints.body'))
            ->assertDontSee(__('editorial_admin.hints.image_path'));

        $this->actingAs($admin)
            ->get('/admin/comments')
            ->assertOk();
    }

    public function test_an_admin_can_edit_a_draft_that_remains_unavailable_on_the_public_slug(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $draft = Article::factory()->create(['is_published' => false]);

        $this->actingAs($admin)
            ->get('/admin/articles/'.$draft->getKey().'/edit')
            ->assertOk();

        $this->get(localized_route(
            'writing.show',
            ['article' => $draft],
            absolute: false,
            locale: 'ar',
        ))->assertNotFound();
    }

    public function test_a_published_article_form_is_read_only_until_a_publisher_unpublishes_it(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $article = Article::factory()->create(['is_published' => true]);

        $this->bootAdminPanel();

        $component = Livewire::actingAs($admin)
            ->test(EditArticle::class, ['record' => $article->getKey()]);

        $this->assertTrue($component->instance()->getSchema('form')?->isDisabled());
        $component->assertActionVisible('unpublish');
    }

    public function test_approving_a_reply_notifies_both_contributors_after_moderation(): void
    {
        Notification::fake();
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $article = Article::factory()->create();
        $parentAuthor = User::factory()->create();
        $replyAuthor = User::factory()->create();
        $parent = Comment::factory()->approved()->create([
            'article_id' => $article->getKey(),
            'user_id' => $parentAuthor->getKey(),
        ]);
        $reply = Comment::factory()->create([
            'article_id' => $article->getKey(),
            'user_id' => $replyAuthor->getKey(),
            'parent_id' => $parent->getKey(),
        ]);

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();

        Livewire::actingAs($admin)
            ->test(ListComments::class)
            ->callTableAction('approve', $reply);

        $this->assertSame(CommentStatus::Approved, $reply->fresh()->status);
        Notification::assertSentTo($replyAuthor, CommentApprovedNotification::class);
        Notification::assertSentTo($parentAuthor, CommentReplyNotification::class);
    }

    public function test_an_authorized_moderator_can_dismiss_reports_without_unpublishing_a_contribution(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $comment = Comment::factory()->approved()->create();
        $report = CommentReport::factory()->create(['comment_id' => $comment->getKey()]);

        $this->assertSame('1', CommentResource::getNavigationBadge());
        $this->assertSame('danger', CommentResource::getNavigationBadgeColor());

        $this->bootAdminPanel();

        Livewire::actingAs($admin)
            ->test(ListComments::class)
            ->assertTableActionVisible('dismiss_reports', $comment)
            ->callTableAction('dismiss_reports', $comment);

        $report->refresh();
        $this->assertSame(CommentReportStatus::Dismissed, $report->status);
        $this->assertSame($admin->getKey(), $report->reviewed_by_user_id);
        $this->assertNotNull($report->reviewed_at);
        $this->assertSame(CommentStatus::Approved, $comment->fresh()->status);
        $this->assertNull(CommentResource::getNavigationBadge());
    }

    public function test_custom_moderation_actions_are_hidden_without_update_authorization(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $viewer = User::factory()->create();
        $viewer->givePermissionTo(['view_any comments', 'view comments']);
        $pending = Comment::factory()->create();
        $reported = Comment::factory()->approved()->create();
        CommentReport::factory()->create(['comment_id' => $reported->getKey()]);

        $this->bootAdminPanel();

        Livewire::actingAs($viewer)
            ->test(ListComments::class)
            ->assertTableActionHidden('approve', $pending)
            ->assertTableActionHidden('reject', $pending)
            ->assertTableActionHidden('dismiss_reports', $reported);

        $this->assertSame(CommentStatus::Pending, $pending->fresh()->status);
        $this->assertSame(CommentReportStatus::Pending, $reported->reports()->firstOrFail()->status);
    }

    public function test_navigation_badge_counts_pending_comments_and_reports(): void
    {
        Comment::factory()->create();
        CommentReport::factory()->create();
        CommentReport::factory()->create(['status' => CommentReportStatus::Dismissed]);

        $this->assertSame('2', CommentResource::getNavigationBadge());
    }

    public function test_deleting_a_reported_contribution_closes_its_reports(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $comment = Comment::factory()->approved()->create();
        $report = CommentReport::factory()->create(['comment_id' => $comment->getKey()]);

        $this->bootAdminPanel();

        Livewire::actingAs($admin)
            ->test(ListComments::class)
            ->callTableAction('delete', $comment);

        $this->assertSoftDeleted($comment);
        $report->refresh();
        $this->assertSame(CommentReportStatus::Resolved, $report->status);
        $this->assertSame($admin->getKey(), $report->reviewed_by_user_id);
        $this->assertNotNull($report->reviewed_at);
    }

    private function bootAdminPanel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }
}
