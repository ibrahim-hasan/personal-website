<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Notifications\CommentApprovedNotification;
use App\Notifications\CommentReplyNotification;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReaderNotificationController extends Controller
{
    public function __invoke(Request $request, string $notification, ArticleCatalog $catalog): RedirectResponse
    {
        $readerNotification = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->whereIn('type', [CommentApprovedNotification::class, CommentReplyNotification::class])
            ->firstOrFail();

        $readerNotification->markAsRead();

        $article = Article::query()->find((int) data_get($readerNotification->data, 'article_id'));
        $editorialArticle = $article ? $catalog->findByKey($article->key) : null;
        $commentId = (int) data_get($readerNotification->data, 'comment_id');

        if (! $editorialArticle || $commentId < 1) {
            return redirect()
                ->to(localized_route('reader.library'))
                ->with('status', __('community_notifications.target_unavailable'));
        }

        return redirect()->to(
            $catalog->url($editorialArticle, app()->getLocale()).'#comment-'.$commentId,
        );
    }
}
