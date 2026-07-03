<?php

namespace App\Filament\Widgets;

use App\Models\GuideDownloader;
use App\Models\IntellectualLibrary;
use App\Models\Newsletter;
use App\Support\DashboardCache;
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
                || $user->can('view_any intellectual_libraries')
                || $user->can('view_any authors')
                || $user->can('view_any newsletters')
                || $user->can('view_any guide_downloaders')
            );
    }

    protected function getStats(): array
    {
        $newsletterCount = DashboardCache::rememberForever(
            'cards:newsletter_count',
            fn (): int => Newsletter::query()->count()
        );

        $guideDownloadsCount = DashboardCache::rememberForever(
            'cards:guide_downloads_count',
            fn (): int => GuideDownloader::query()->count()
        );

        $libraryViews = DashboardCache::rememberForever(
            'cards:library_total_views',
            fn (): int => (int) IntellectualLibrary::query()->sum('views')
        );

        $libraryItems = DashboardCache::rememberForever(
            'cards:library_items_count',
            fn (): int => IntellectualLibrary::query()->count()
        );

        return [
            Stat::make(__('admin.stats.newsletter_joiners'), (string) $newsletterCount)
                ->description(__('admin.stats.total_subscribers'))
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->extraAttributes(['class' => 'dashboard-home-stat dashboard-home-stat--info']),
            Stat::make(__('admin.stats.guide_downloaders'), (string) $guideDownloadsCount)
                ->description(__('admin.stats.total_download_requests'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->extraAttributes(['class' => 'dashboard-home-stat dashboard-home-stat--success']),
            Stat::make(__('admin.stats.library_total_views'), (string) $libraryViews)
                ->description(__('admin.stats.total_views'))
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->extraAttributes(['class' => 'dashboard-home-stat dashboard-home-stat--warning']),
            Stat::make(__('admin.stats.intellectual_libraries'), (string) $libraryItems)
                ->description(__('admin.stats.total_items'))
                ->icon('heroicon-o-book-open')
                ->color('primary')
                ->extraAttributes(['class' => 'dashboard-home-stat dashboard-home-stat--primary']),
        ];
    }
}
