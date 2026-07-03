<?php

namespace App\Filament\Widgets;

use App\Models\GuideDownloader;
use App\Support\DashboardCache;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class GuideDownloadsChart extends ChartWidget
{
    protected ?string $heading = null;

    public ?string $filter = null;

    public function getHeading(): string
    {
        return __('admin.stats.guide_downloads_this_month');
    }

    protected function getFilters(): ?array
    {
        $filters = [];

        for ($i = 0; $i < 12; $i++) {
            $month = now()->startOfMonth()->subMonths($i);
            $filters[$month->format('Y-m')] = $month->translatedFormat('F Y');
        }

        return $filters;
    }

    protected function getData(): array
    {
        $selectedMonth = $this->filter ?? now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        $end = (clone $start)->endOfMonth();
        $daysInMonth = (int) $start->daysInMonth;
        $cacheKey = 'charts:guide_downloads:'.$selectedMonth;

        $values = DashboardCache::rememberForever($cacheKey, function () use ($start, $end): array {
            return GuideDownloader::query()
                ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('day')
                ->pluck('total', 'day')
                ->mapWithKeys(fn ($count, $day): array => [(string) $day => (int) $count])
                ->toArray();
        });

        $labels = [];
        $dataset = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $start->copy()->day($day)->toDateString();
            $labels[] = (string) $day;
            $dataset[] = $values[$date] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => __('admin.stats.guide_downloaders'),
                    'data' => $dataset,
                    'borderColor' => '#C4B37D',
                    'backgroundColor' => 'rgba(196,179,125,0.25)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
