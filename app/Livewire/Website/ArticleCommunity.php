<?php

namespace App\Livewire\Website;

use App\Enums\CommentReportStatus;
use App\Enums\CommentStatus;
use App\Models\Article;
use App\Models\ArticleAppreciation;
use App\Models\ArticleBookmark;
use App\Models\ArticleReadingProgress;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class ArticleCommunity extends Component
{
    use WithPagination;

    #[Locked]
    public int $articleId = 0;

    #[Locked]
    public string $returnPath = '/writing';

    public string $commentBody = '';

    public string $replyBody = '';

    public ?int $replyTo = null;

    public ?int $reportingComment = null;

    public string $reportReason = 'spam';

    public string $reportDetails = '';

    public string $notice = '';

    public function mount(string $articleKey, string $returnPath = '/writing'): void
    {
        $this->articleId = Schema::hasTable('articles')
            ? (int) (Article::query()->where('key', $articleKey)->value('id') ?? 0)
            : 0;
        $this->returnPath = $this->safeReturnPath($returnPath);
    }

    public function toggleAppreciation(): void
    {
        $user = $this->verifiedUser();

        $appreciation = ArticleAppreciation::query()->firstOrCreate([
            'article_id' => $this->articleId,
            'user_id' => $user->getKey(),
        ]);

        if ($appreciation->wasRecentlyCreated) {
            $this->notice = __('community.appreciation_added');

            return;
        }

        $appreciation->delete();
        $this->notice = __('community.appreciation_removed');
    }

    public function toggleBookmark(): void
    {
        $user = $this->verifiedUser();

        $bookmark = ArticleBookmark::query()->firstOrCreate([
            'article_id' => $this->articleId,
            'user_id' => $user->getKey(),
        ]);

        if ($bookmark->wasRecentlyCreated) {
            $this->notice = __('community.bookmark_added');

            return;
        }

        $bookmark->delete();
        $this->notice = __('community.bookmark_removed');
    }

    public function postComment(): void
    {
        $user = $this->verifiedUser();
        Gate::forUser($user)->authorize('create', Comment::class);
        $this->enforceRateLimit('comment', 4, 300);

        $validated = $this->validate([
            'commentBody' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        Comment::query()->create([
            'article_id' => $this->articleId,
            'user_id' => $user->getKey(),
            'body' => trim($validated['commentBody']),
            'status' => CommentStatus::Pending,
        ]);

        $this->reset('commentBody');
        $this->notice = __('community.awaiting_moderation');
    }

    public function startReply(int $commentId): void
    {
        $this->verifiedUser();
        $this->replyTo = $this->approvedRootComment($commentId)->getKey();
        $this->reset('replyBody');
    }

    public function postReply(): void
    {
        $user = $this->verifiedUser();
        Gate::forUser($user)->authorize('create', Comment::class);
        $this->enforceRateLimit('reply', 4, 300);

        $validated = $this->validate([
            'replyBody' => ['required', 'string', 'min:3', 'max:2000'],
            'replyTo' => ['required', 'integer'],
        ]);

        $parent = $this->approvedRootComment((int) $validated['replyTo']);

        Comment::query()->create([
            'article_id' => $this->articleId,
            'user_id' => $user->getKey(),
            'parent_id' => $parent->getKey(),
            'body' => trim($validated['replyBody']),
            'status' => CommentStatus::Pending,
        ]);

        $this->reset('replyBody', 'replyTo');
        $this->notice = __('community.reply_awaiting_moderation');
    }

    public function openReport(int $commentId): void
    {
        $this->verifiedUser();
        $this->reportingComment = $this->approvedComment($commentId)->getKey();
        $this->reset('reportDetails');
        $this->reportReason = 'spam';
    }

    public function submitReport(): void
    {
        $user = $this->verifiedUser();
        $this->enforceRateLimit('report', 5, 3600);

        $validated = $this->validate([
            'reportingComment' => ['required', 'integer'],
            'reportReason' => ['required', Rule::in(['spam', 'abuse', 'misinformation', 'privacy', 'other'])],
            'reportDetails' => ['nullable', 'string', 'max:500'],
        ]);

        $comment = $this->approvedComment((int) $validated['reportingComment']);

        CommentReport::query()->updateOrCreate(
            [
                'comment_id' => $comment->getKey(),
                'reporter_user_id' => $user->getKey(),
            ],
            [
                'reason' => $validated['reportReason'],
                'details' => filled($validated['reportDetails']) ? trim($validated['reportDetails']) : null,
                'status' => CommentReportStatus::Pending,
            ],
        );

        $this->reset('reportingComment', 'reportDetails');
        $this->notice = __('community.report_received');
    }

    public function deleteComment(int $commentId): void
    {
        $user = $this->verifiedUser();
        $comment = Comment::query()
            ->where('article_id', $this->articleId)
            ->findOrFail($commentId);

        Gate::forUser($user)->authorize('delete', $comment);

        DB::transaction(function () use ($comment): void {
            $comment->reports()->pending()->update([
                'status' => CommentReportStatus::Resolved,
                'reviewed_at' => now(),
            ]);
            $comment->delete();
        });
        $this->notice = __('community.comment_deleted');
    }

    public function updateProgress(int $percent): void
    {
        $user = auth()->user();

        if (! $user || $this->articleId === 0) {
            return;
        }

        ArticleReadingProgress::record($this->verifiedUser(), $this->articleId, $percent);
    }

    public function render(): View
    {
        $article = $this->articleId > 0
            ? Article::query()->withCount('appreciations')->find($this->articleId)
            : null;
        $userId = auth()->id();

        return view('livewire.website.article-community', [
            'article' => $article,
            'comments' => $this->comments(),
            'hasAppreciated' => $article && $userId
                ? ArticleAppreciation::query()->where('article_id', $article->getKey())->where('user_id', $userId)->exists()
                : false,
            'hasBookmarked' => $article && $userId
                ? ArticleBookmark::query()->where('article_id', $article->getKey())->where('user_id', $userId)->exists()
                : false,
            'loginUrl' => localized_route('reader.login', ['return' => $this->returnPath]),
            'verifyUrl' => localized_route('reader.verification.notice'),
            'libraryUrl' => localized_route('reader.library'),
        ]);
    }

    /** @return LengthAwarePaginator<int, Comment> */
    private function comments(): LengthAwarePaginator
    {
        if ($this->articleId === 0) {
            return new LengthAwarePaginator([], 0, 8);
        }

        return Comment::query()
            ->where('article_id', $this->articleId)
            ->whereNull('parent_id')
            ->approved()
            ->with([
                'user:id,name',
                'replies' => fn ($query) => $query->approved()->oldest()->with('user:id,name'),
            ])
            ->oldest()
            ->paginate(8);
    }

    private function verifiedUser(): User
    {
        $user = auth()->user();

        if (! $user) {
            throw ValidationException::withMessages(['auth' => __('community.sign_in_required')]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['auth' => __('community.account_inactive')]);
        }

        if (! $user->hasVerifiedEmail()) {
            throw ValidationException::withMessages(['auth' => __('community.verify_required')]);
        }

        if ($this->articleId === 0 || ! Article::query()->whereKey($this->articleId)->published()->exists()) {
            abort(404);
        }

        return $user;
    }

    private function approvedRootComment(int $commentId): Comment
    {
        return Comment::query()
            ->where('article_id', $this->articleId)
            ->whereNull('parent_id')
            ->approved()
            ->findOrFail($commentId);
    }

    private function approvedComment(int $commentId): Comment
    {
        return Comment::query()
            ->where('article_id', $this->articleId)
            ->approved()
            ->findOrFail($commentId);
    }

    private function enforceRateLimit(string $action, int $attempts, int $decaySeconds): void
    {
        $key = "article-community:{$action}:".auth()->id().'|'.request()->ip();

        if (! RateLimiter::attempt($key, $attempts, fn (): bool => true, $decaySeconds)) {
            throw ValidationException::withMessages([
                'rate_limit' => __('community.rate_limited'),
            ]);
        }
    }

    private function safeReturnPath(string $path): string
    {
        return str_starts_with($path, '/') && ! str_starts_with($path, '//')
            ? $path
            : '/writing';
    }
}
