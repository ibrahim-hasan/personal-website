<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleBookmark;
use App\Models\ArticleReadingProgress;
use App\Models\Comment;
use App\Notifications\CommentApprovedNotification;
use App\Notifications\CommentReplyNotification;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class ReaderLibraryController extends Controller
{
    public function __invoke(Request $request, ArticleCatalog $catalog): View
    {
        $progress = ArticleReadingProgress::query()
            ->where('user_id', $request->user()->getKey())
            ->get()
            ->keyBy('article_id');

        $bookmarks = ArticleBookmark::query()
            ->where('user_id', $request->user()->getKey())
            ->with('article')
            ->latest()
            ->get()
            ->map(function (ArticleBookmark $bookmark) use ($catalog, $progress): ?array {
                $article = $bookmark->article;
                $editorialArticle = $article ? $catalog->findByKey($article->key) : null;

                if (! $article || ! $editorialArticle) {
                    return null;
                }

                $localized = $editorialArticle->localized(app()->getLocale());

                return [
                    ...$localized,
                    'url' => $catalog->url($editorialArticle, app()->getLocale()),
                    'progress_percent' => $progress->get($article->getKey())?->progress_percent ?? 0,
                    'saved_at' => $bookmark->created_at,
                ];
            })
            ->filter()
            ->values();

        $notificationTypes = [CommentApprovedNotification::class, CommentReplyNotification::class];
        $notificationQuery = $request->user()
            ->notifications()
            ->whereIn('type', $notificationTypes);
        $unreadNotificationCount = (clone $notificationQuery)
            ->whereNull('read_at')
            ->count();
        $storedNotifications = $notificationQuery
            ->latest()
            ->limit(8)
            ->get();
        $comments = Comment::query()
            ->withTrashed()
            ->with('user:id,name')
            ->whereIn('id', $storedNotifications->pluck('data')->pluck('comment_id')->filter())
            ->get()
            ->keyBy('id');
        $articles = Article::query()
            ->whereIn('id', $storedNotifications->pluck('data')->pluck('article_id')->filter())
            ->get()
            ->keyBy('id');
        $readerNotifications = $storedNotifications->map(function (DatabaseNotification $notification) use ($articles, $catalog, $comments): array {
            $kind = (string) data_get($notification->data, 'kind');
            $article = $articles->get((int) data_get($notification->data, 'article_id'));
            $editorialArticle = $article ? $catalog->findByKey($article->key) : null;
            $comment = $comments->get((int) data_get($notification->data, 'comment_id'));
            $isReply = $kind === 'comment_reply';

            return [
                'id' => $notification->getKey(),
                'title' => __($isReply
                    ? 'community_notifications.reply_subject'
                    : 'community_notifications.approved_subject'),
                'message' => $isReply
                    ? __('community_notifications.reply_line', [
                        'name' => $comment?->user?->name ?? __('community.former_reader'),
                    ])
                    : __('community_notifications.approved_line'),
                'article_title' => $editorialArticle
                    ? $editorialArticle->localized(app()->getLocale())['title']
                    : __('community_notifications.article_unavailable'),
                'created_at' => $notification->created_at,
                'created_label' => $notification->created_at->diffForHumans(),
                'unread' => $notification->unread(),
            ];
        });

        return view('website.reader-library', compact(
            'bookmarks',
            'readerNotifications',
            'unreadNotificationCount',
        ));
    }
}
