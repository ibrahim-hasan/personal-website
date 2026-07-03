<?php

namespace App\Filament\Widgets;

use App\Models\IntellectualLibrary;
use App\Models\Service;
use App\Models\Setting;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminPageHealth extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user !== null
            && (
                $user->can('view_any settings')
                || $user->can('view_any services')
                || $user->can('view_any intellectual_libraries')
            );
    }

    protected function getStats(): array
    {
        $stats = [];
        $user = Filament::auth()->user();

        // if ($user?->can('view_any settings')) {
        //     $stats[] = Stat::make(__('admin.stats.configured_settings'), (string) Setting::query()->count())
        //         ->description(__('admin.stats.guide_refs_prefix').' '.Setting::query()->where('group', 'guide')->count());
        // }

        // if ($user?->can('view_any services')) {
        //     $stats[] = Stat::make(__('admin.stats.draft_services'), (string) Service::query()->where('is_draft', true)->count())
        //         ->description(__('admin.stats.inactive_prefix').' '.Service::query()->where('is_active', false)->count());
        // }

        // if ($user?->can('view_any intellectual_libraries')) {
        //     $stats[] = Stat::make(__('admin.stats.scheduled_libraries'), (string) IntellectualLibrary::query()
        //         ->whereNotNull('scheduled_at')
        //         ->where('scheduled_at', '>', now())
        //         ->count())
        //         ->description(__('admin.stats.drafts_prefix').' '.IntellectualLibrary::query()->where('is_draft', true)->count());
        // }

        return $stats;
    }
}
