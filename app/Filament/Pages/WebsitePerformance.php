<?php

namespace App\Filament\Pages;

use App\Services\WebsitePerformance\WebsitePerformanceSnapshotStore;
use BackedEnum;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;
use Throwable;

class WebsitePerformance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.website-performance';

    #[Locked]
    public string $snapshotState = 'empty';

    /** @var array<string, mixed>|null */
    #[Locked]
    public ?array $latestReport = null;

    /** @var list<array<string, mixed>> */
    #[Locked]
    public array $reportHistory = [];

    public function mount(WebsitePerformanceSnapshotStore $snapshots): void
    {
        abort_unless(static::canAccess(), 403);

        try {
            $summaries = $snapshots->summaries();
        } catch (Throwable $exception) {
            report($exception);
            $this->snapshotState = 'unavailable';

            return;
        }

        $this->snapshotState = $summaries['state'];
        $this->latestReport = $summaries['latest'];
        $this->reportHistory = $summaries['history'];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.website_performance.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin.website_performance.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') === true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function diagnosticLabel(string $code): string
    {
        return match (true) {
            $code === 'source_unavailable' => __('admin.website_performance.diagnostics.labels.source_unavailable'),
            in_array($code, ['google_credentials_unavailable', 'google_authentication_unavailable'], true) => __('admin.website_performance.diagnostics.labels.google_access_unavailable'),
            $code === 'ga4_configuration_unavailable' => __('admin.website_performance.diagnostics.labels.ga4_configuration_unavailable'),
            str_starts_with($code, 'ga4_') && str_ends_with($code, '_unavailable') => __('admin.website_performance.diagnostics.labels.ga4_data_unavailable'),
            str_starts_with($code, 'ga4_') => __('admin.website_performance.diagnostics.labels.ga4_data_invalid'),
            $code === 'first_party_configuration_unavailable' => __('admin.website_performance.diagnostics.labels.first_party_configuration_unavailable'),
            $code === 'first_party_authentication_unavailable' => __('admin.website_performance.diagnostics.labels.first_party_authentication_unavailable'),
            str_starts_with($code, 'first_party_') => __('admin.website_performance.diagnostics.labels.first_party_data_unavailable'),
            $code === 'search_console_configuration_unavailable' => __('admin.website_performance.diagnostics.labels.search_console_configuration_unavailable'),
            str_starts_with($code, 'url_inspection_') => __('admin.website_performance.diagnostics.labels.url_inspection_unavailable'),
            str_starts_with($code, 'search_console_') && str_ends_with($code, '_invalid') => __('admin.website_performance.diagnostics.labels.search_console_data_invalid'),
            str_starts_with($code, 'search_console_') => __('admin.website_performance.diagnostics.labels.search_console_data_unavailable'),
            default => __('admin.website_performance.diagnostics.labels.data_unavailable'),
        };
    }

    public function diagnosticMetricLabel(string $metric): string
    {
        return match ($metric) {
            'sessions' => __('admin.website_performance.diagnostics.metrics.sessions'),
            'relevant_events' => __('admin.website_performance.diagnostics.metrics.relevant_events'),
            'query_impressions' => __('admin.website_performance.diagnostics.metrics.query_impressions'),
            'page_impressions' => __('admin.website_performance.diagnostics.metrics.page_impressions'),
            'inquiries_for_trend' => __('admin.website_performance.diagnostics.metrics.inquiries_for_trend'),
            'canonical_mismatches' => __('admin.website_performance.diagnostics.metrics.canonical_mismatches'),
            'indexing_issues' => __('admin.website_performance.diagnostics.metrics.indexing_issues'),
            'robots_issues' => __('admin.website_performance.diagnostics.metrics.robots_issues'),
            'page_fetch_issues' => __('admin.website_performance.diagnostics.metrics.page_fetch_issues'),
            'verdict_issues' => __('admin.website_performance.diagnostics.metrics.verdict_issues'),
            default => __('admin.website_performance.values.unavailable'),
        };
    }

    public function diagnosticStatusLabel(string $status): string
    {
        return match ($status) {
            'sufficient' => __('admin.website_performance.diagnostics.statuses.sufficient'),
            'insufficient_sample' => __('admin.website_performance.diagnostics.statuses.insufficient_sample'),
            'clear' => __('admin.website_performance.diagnostics.statuses.clear'),
            'issues_detected' => __('admin.website_performance.diagnostics.statuses.issues_detected'),
            default => __('admin.website_performance.diagnostics.statuses.unavailable'),
        };
    }

    /**
     * @param  array<string, mixed>  $flag
     */
    public function isIndexingDiagnostic(array $flag): bool
    {
        return in_array($flag['metric'] ?? null, [
            'canonical_mismatches',
            'indexing_issues',
            'robots_issues',
            'page_fetch_issues',
            'verdict_issues',
        ], true);
    }
}
