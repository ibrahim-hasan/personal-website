<?php

namespace App\Filament\Widgets;

use App\Enums\CommentReportStatus;
use App\Enums\CommentStatus;
use App\Enums\ContactInquiryStatus;
use App\Models\Article;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\ContactInquiry;
use App\Models\Project;
use App\Models\Service;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminContentStats extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && (
                $user->can('view_any services')
                || $user->can('view_any projects')
                || $user->can('view_any articles')
                || $user->can('view_any comments')
                || $user->can('view_any contact_inquiries')
            );
    }

    protected function getStats(): array
    {
        $user = Filament::auth()->user();
        $stats = [];

        if ($user?->can('view_any articles')) {
            $stats[] = Stat::make(__('admin.stats.published_articles'), (string) Article::query()->published()->count())
                ->description(__('admin.stats.public_writing'))
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->extraAttributes(['class' => 'dashboard-home-stat dashboard-home-stat--info']);
        }

        if ($user?->can('view_any comments')) {
            $pendingComments = Comment::query()->where('status', CommentStatus::Pending)->count();
            $pendingReports = CommentReport::query()
                ->where('status', CommentReportStatus::Pending)
                ->whereHas('comment')
                ->count();

            $stats[] = Stat::make(__('admin.stats.pending_comments'), (string) $pendingComments)
                ->description(__('admin.stats.pending_reports', ['count' => $pendingReports]))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color(($pendingComments + $pendingReports) > 0 ? 'warning' : 'success')
                ->extraAttributes(['class' => 'dashboard-home-stat dashboard-home-stat--success']);
        }

        if ($user?->can('view_any contact_inquiries')) {
            $newInquiries = ContactInquiry::query()->where('status', ContactInquiryStatus::New)->count();

            $stats[] = Stat::make(__('admin.stats.new_inquiries'), (string) $newInquiries)
                ->description(__('admin.stats.awaiting_response'))
                ->icon('heroicon-o-inbox-arrow-down')
                ->color($newInquiries > 0 ? 'primary' : 'success')
                ->extraAttributes(['class' => 'dashboard-home-stat dashboard-home-stat--warning']);
        }

        if ($user?->can('view_any projects')) {
            $stats[] = Stat::make(__('admin.stats.active_projects'), (string) Project::query()->published()->count())
                ->description(__('admin.stats.portfolio_projects'))
                ->icon('heroicon-o-briefcase')
                ->color('primary')
                ->extraAttributes(['class' => 'dashboard-home-stat dashboard-home-stat--primary']);
        }

        if ($user?->can('view_any services')) {
            $stats[] = Stat::make(__('admin.stats.active_services'), (string) Service::query()->posted()->count())
                ->description(__('admin.stats.public_practice'))
                ->icon('heroicon-o-squares-2x2')
                ->color('primary')
                ->extraAttributes(['class' => 'dashboard-home-stat dashboard-home-stat--primary']);
        }

        return $stats;
    }

    /**
     * @return int|array<string, int>|null
     */
    protected function getColumns(): int|array|null
    {
        return $this->columnsForStatCount(count($this->getCachedStats()));
    }

    /**
     * @return int|array<string, int>
     */
    protected function columnsForStatCount(int $statCount): int|array
    {
        return match ($statCount) {
            0, 1 => 1,
            2 => [
                'default' => 1,
                '@md' => 2,
                '!@md' => 2,
            ],
            3 => [
                'default' => 1,
                '@3xl' => 3,
                '!@lg' => 3,
            ],
            4 => [
                'default' => 1,
                '@md' => 2,
                '@4xl' => 4,
                '!@md' => 2,
                '!@xl' => 4,
            ],
            default => [
                'default' => 1,
                '@md' => 2,
                '@3xl' => 3,
                '@5xl' => 5,
                '!@md' => 2,
                '!@lg' => 3,
                '!@xl' => 5,
            ],
        };
    }
}
