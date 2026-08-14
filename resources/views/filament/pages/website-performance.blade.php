<x-filament-panels::page>
    @php
        $statusClasses = [
            'ok' => 'border-success-200 bg-success-50 text-success-700 dark:border-success-800 dark:bg-success-950 dark:text-success-300',
            'partial' => 'border-warning-200 bg-warning-50 text-warning-700 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-300',
            'unavailable' => 'border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-800 dark:bg-danger-950 dark:text-danger-300',
        ];
        $formatCount = fn (?int $value): string => $value === null ? '—' : number_format($value);
        $formatRate = fn (?float $value): string => $value === null ? '—' : number_format($value * 100, 1).'%';
        $statusClass = fn (string $status): string => $statusClasses[$status] ?? $statusClasses['unavailable'];
        $diagnosticStatusClasses = [
            'sufficient' => 'border-success-200 bg-success-50 text-success-700 dark:border-success-800 dark:bg-success-950 dark:text-success-300',
            'clear' => 'border-success-200 bg-success-50 text-success-700 dark:border-success-800 dark:bg-success-950 dark:text-success-300',
            'insufficient_sample' => 'border-warning-200 bg-warning-50 text-warning-700 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-300',
            'issues_detected' => 'border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-800 dark:bg-danger-950 dark:text-danger-300',
            'unavailable' => 'border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-800 dark:bg-danger-950 dark:text-danger-300',
        ];
        $diagnosticStatusClass = fn (string $status): string => $diagnosticStatusClasses[$status] ?? $diagnosticStatusClasses['unavailable'];
        $qualityFlags = is_array($latestReport['quality']['flags'] ?? null) ? $latestReport['quality']['flags'] : [];
        $sampleFlags = array_values(array_filter($qualityFlags, fn (array $flag): bool => ! $this->isIndexingDiagnostic($flag)));
        $indexingFlags = array_values(array_filter($qualityFlags, fn (array $flag): bool => $this->isIndexingDiagnostic($flag)));
    @endphp

    <div class="space-y-8">
        <x-filament::section icon="heroicon-o-shield-check">
            <x-slot name="heading">{{ __('admin.website_performance.privacy.heading') }}</x-slot>
            <x-slot name="description">{{ __('admin.website_performance.privacy.description') }}</x-slot>

            <p class="text-sm leading-6 text-gray-600 dark:text-gray-400">
                {{ __('admin.website_performance.privacy.notice') }}
            </p>
        </x-filament::section>

        @if ($snapshotState === 'unavailable')
            <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="danger">
                <x-slot name="heading">{{ __('admin.website_performance.unavailable.heading') }}</x-slot>
                <x-slot name="description">{{ __('admin.website_performance.unavailable.description') }}</x-slot>
            </x-filament::section>
        @elseif ($latestReport === null)
            <x-filament::section icon="heroicon-o-chart-bar-square" icon-color="gray">
                <x-slot name="heading">{{ __('admin.website_performance.empty.heading') }}</x-slot>
                <x-slot name="description">{{ __('admin.website_performance.empty.description') }}</x-slot>
            </x-filament::section>
        @else
            <x-filament::section icon="heroicon-o-chart-bar-square">
                <x-slot name="heading">{{ __('admin.website_performance.latest.heading') }}</x-slot>
                <x-slot name="description">{{ __('admin.website_performance.latest.description') }}</x-slot>

                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <span class="inline-flex rounded-full border px-3 py-1 font-medium {{ $statusClass($latestReport['status']) }}">
                        {{ __('admin.website_performance.statuses.'.$latestReport['status']) }}
                    </span>
                    <span class="text-gray-600 dark:text-gray-400">
                        {{ __('admin.website_performance.latest.generated_at', ['value' => $latestReport['generated_at']]) }}
                    </span>
                    <span class="text-gray-600 dark:text-gray-400">
                        {{ __('admin.website_performance.latest.data_cutoff', ['value' => $latestReport['data_cutoff'] ?? __('admin.website_performance.values.unavailable')]) }}
                    </span>
                </div>

                @if ($latestReport['periods']['current'] !== null)
                    <p class="mt-4 text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('admin.website_performance.latest.current_period', [
                            'start' => $latestReport['periods']['current']['start'],
                            'end' => $latestReport['periods']['current']['end'],
                        ]) }}
                    </p>
                @endif
            </x-filament::section>

            <div class="grid gap-4 lg:grid-cols-3">
                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('admin.website_performance.indicators.inquiries.heading') }}</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.website_performance.indicators.inquiries.description') }}</p>
                        </div>
                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClass($latestReport['sources']['first_party']['status']) }}">
                            {{ __('admin.website_performance.statuses.'.$latestReport['sources']['first_party']['status']) }}
                        </span>
                    </div>

                    <dl class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.inquiries') }}</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $formatCount($latestReport['sources']['first_party']['current']['total']) }}</dd>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.previous', ['value' => $formatCount($latestReport['sources']['first_party']['previous']['total'])]) }}</p>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.response_rate') }}</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $formatRate($latestReport['sources']['first_party']['current']['response_rate']) }}</dd>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.previous', ['value' => $formatRate($latestReport['sources']['first_party']['previous']['response_rate'])]) }}</p>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('admin.website_performance.indicators.search.heading') }}</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.website_performance.indicators.search.description') }}</p>
                        </div>
                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClass($latestReport['sources']['search_console']['status']) }}">
                            {{ __('admin.website_performance.statuses.'.$latestReport['sources']['search_console']['status']) }}
                        </span>
                    </div>

                    <dl class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.organic_clicks') }}</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $formatCount($latestReport['sources']['search_console']['current']['clicks']) }}</dd>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.previous', ['value' => $formatCount($latestReport['sources']['search_console']['previous']['clicks'])]) }}</p>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.organic_impressions') }}</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $formatCount($latestReport['sources']['search_console']['current']['impressions']) }}</dd>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.previous', ['value' => $formatCount($latestReport['sources']['search_console']['previous']['impressions'])]) }}</p>
                        </div>
                    </dl>
                </section>

                <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('admin.website_performance.indicators.high_intent.heading') }}</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.website_performance.indicators.high_intent.description') }}</p>
                        </div>
                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClass($latestReport['sources']['ga4']['status']) }}">
                            {{ __('admin.website_performance.statuses.'.$latestReport['sources']['ga4']['status']) }}
                        </span>
                    </div>

                    <dl class="mt-6 grid gap-5 sm:grid-cols-3">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.cta_clicks') }}</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $formatCount($latestReport['sources']['ga4']['current']['cta_clicks']) }}</dd>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.previous', ['value' => $formatCount($latestReport['sources']['ga4']['previous']['cta_clicks'])]) }}</p>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.form_starts') }}</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $formatCount($latestReport['sources']['ga4']['current']['form_starts']) }}</dd>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.previous', ['value' => $formatCount($latestReport['sources']['ga4']['previous']['form_starts'])]) }}</p>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.successful_submissions') }}</dt>
                            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $formatCount($latestReport['sources']['ga4']['current']['successful_submissions']) }}</dd>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.metrics.previous', ['value' => $formatCount($latestReport['sources']['ga4']['previous']['successful_submissions'])]) }}</p>
                        </div>
                    </dl>
                </section>
            </div>

            <x-filament::section icon="heroicon-o-circle-stack">
                <x-slot name="heading">{{ __('admin.website_performance.sources.heading') }}</x-slot>
                <x-slot name="description">{{ __('admin.website_performance.sources.description') }}</x-slot>

                <div class="grid gap-4 lg:grid-cols-3">
                    @foreach ($latestReport['sources'] as $sourceKey => $source)
                        <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-medium text-gray-950 dark:text-white">{{ __('admin.website_performance.sources.labels.'.$sourceKey) }}</h3>
                                <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClass($source['status']) }}">
                                    {{ __('admin.website_performance.statuses.'.$source['status']) }}
                                </span>
                            </div>
                            <dl class="mt-4 space-y-2 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.sources.fresh_through') }}</dt>
                                    <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $source['fresh_through'] ?? __('admin.website_performance.values.unavailable') }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.sources.warnings') }}</dt>
                                    <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $formatCount($source['warning_count']) }}</dd>
                                </div>
                            </dl>
                            @if ($source['warning_codes'] !== [])
                                <ul class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300">
                                    @foreach ($source['warning_codes'] as $warningCode)
                                        <li class="flex gap-2">
                                            <span aria-hidden="true">•</span>
                                            <span>{{ $this->diagnosticLabel($warningCode) }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </article>
                    @endforeach
                </div>
            </x-filament::section>

            <x-filament::section icon="heroicon-o-shield-exclamation">
                <x-slot name="heading">{{ __('admin.website_performance.diagnostics.heading') }}</x-slot>
                <x-slot name="description">{{ __('admin.website_performance.diagnostics.description') }}</x-slot>

                <div class="grid gap-4 lg:grid-cols-2">
                    <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                        <h3 class="font-medium text-gray-950 dark:text-white">{{ __('admin.website_performance.diagnostics.sample_heading') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.website_performance.diagnostics.sample_description') }}</p>

                        <ul class="mt-4 space-y-3">
                            @forelse ($sampleFlags as $flag)
                                <li class="flex items-start justify-between gap-3 text-sm">
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-gray-200">{{ $this->diagnosticMetricLabel($flag['metric']) }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('admin.website_performance.diagnostics.observed_threshold', [
                                                'observed' => $formatCount($flag['observed']),
                                                'threshold' => $formatCount($flag['threshold']),
                                            ]) }}
                                        </p>
                                    </div>
                                    <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-xs font-medium {{ $diagnosticStatusClass($flag['status']) }}">
                                        {{ $this->diagnosticStatusLabel($flag['status']) }}
                                    </span>
                                </li>
                            @empty
                                <li class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.website_performance.diagnostics.no_sample_flags') }}</li>
                            @endforelse
                        </ul>
                    </article>

                    <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                        <h3 class="font-medium text-gray-950 dark:text-white">{{ __('admin.website_performance.diagnostics.indexing_heading') }}</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('admin.website_performance.diagnostics.indexing_description') }}</p>

                        <ul class="mt-4 space-y-3">
                            @foreach ($indexingFlags as $flag)
                                <li class="flex items-start justify-between gap-3 text-sm">
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-gray-200">{{ $this->diagnosticMetricLabel($flag['metric']) }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('admin.website_performance.diagnostics.observed_threshold', [
                                                'observed' => $formatCount($flag['observed']),
                                                'threshold' => $formatCount($flag['threshold']),
                                            ]) }}
                                        </p>
                                    </div>
                                    <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-xs font-medium {{ $diagnosticStatusClass($flag['status']) }}">
                                        {{ $this->diagnosticStatusLabel($flag['status']) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                </div>
            </x-filament::section>

            <x-filament::section icon="heroicon-o-clock">
                <x-slot name="heading">{{ __('admin.website_performance.history.heading') }}</x-slot>
                <x-slot name="description">{{ __('admin.website_performance.history.description') }}</x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-start text-sm">
                        <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="py-3 pe-4 font-medium">{{ __('admin.website_performance.history.generated_at') }}</th>
                                <th class="py-3 pe-4 font-medium">{{ __('admin.website_performance.history.data_cutoff') }}</th>
                                <th class="py-3 pe-4 font-medium">{{ __('admin.website_performance.history.status') }}</th>
                                <th class="py-3 pe-4 font-medium">{{ __('admin.website_performance.sources.labels.ga4') }}</th>
                                <th class="py-3 pe-4 font-medium">{{ __('admin.website_performance.sources.labels.search_console') }}</th>
                                <th class="py-3 font-medium">{{ __('admin.website_performance.sources.labels.first_party') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($reportHistory as $report)
                                <tr>
                                    <td class="py-3 pe-4 font-medium text-gray-950 dark:text-white" dir="ltr">{{ $report['generated_at'] }}</td>
                                    <td class="py-3 pe-4" dir="ltr">{{ $report['data_cutoff'] ?? __('admin.website_performance.values.unavailable') }}</td>
                                    <td class="py-3 pe-4">
                                        <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClass($report['status']) }}">
                                            {{ __('admin.website_performance.statuses.'.$report['status']) }}
                                        </span>
                                    </td>
                                    @foreach (['ga4', 'search_console', 'first_party'] as $sourceKey)
                                        <td class="py-3 pe-4">
                                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-medium {{ $statusClass($report['sources'][$sourceKey]['status']) }}">
                                                {{ __('admin.website_performance.statuses.'.$report['sources'][$sourceKey]['status']) }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
