<?php

namespace App\Filament\Widgets;

use App\Models\Newsletter;
use App\Support\DashboardCache;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class NewsletterJoinsChart extends ChartWidget
{
    protected ?string $heading = null;

    public ?string $filter = null;

    public function getHeading(): string
    {
        return __('admin.stats.newsletter_joins_this_month');
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
        $cacheKey = 'charts:newsletter_joins:'.$selectedMonth;

        $values = DashboardCache::rememberForever($cacheKey, function () use ($start, $end): array {
            return Newsletter::query()
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
                    'label' => __('admin.stats.newsletter_joiners'),
                    'data' => $dataset,
                    'borderColor' => '#B4A571',
                    'backgroundColor' => 'rgba(180,165,113,0.25)',
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
