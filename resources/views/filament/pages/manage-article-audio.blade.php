<x-filament-panels::page>
    @php
        $configuration = $this->configuration();
        $rows = $this->articleRows();
        $canGenerate = $this->canGenerate();
        $hasActiveWork = collect($rows)->contains('has_active_work', true);
    @endphp

    <div @if ($hasActiveWork) wire:poll.5s.visible @endif class="space-y-6">
        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="grid gap-6 p-6 lg:grid-cols-[1.25fr_1fr]">
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span @class([
                            'h-3 w-3 rounded-full',
                            'bg-success-500' => $configuration['ready'],
                            'bg-warning-500' => ! $configuration['ready'],
                        ])></span>
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                            {{ $configuration['ready'] ? __('article_audio.configuration.workflow_ready') : __('article_audio.configuration.workflow_incomplete') }}
                        </h2>
                    </div>
                    <p class="max-w-3xl text-sm leading-7 text-gray-600 dark:text-gray-300">
                        {{ __('article_audio.page.workflow_description') }}
                    </p>
                    <div class="grid gap-2 sm:grid-cols-4">
                        @foreach (['prepare', 'review', 'sample', 'publish'] as $index => $step)
                            <div class="rounded-xl bg-gray-50 px-3 py-3 dark:bg-white/5">
                                <span class="text-xs font-bold text-primary-600 dark:text-primary-400">{{ sprintf('%02d', $index + 1) }}</span>
                                <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">{{ __('article_audio.workflow.'.$step) }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('article_audio.configuration.openai') }}</dt>
                        <dd class="mt-1 font-semibold text-gray-950 dark:text-white">
                            {{ $configuration['openai_key_configured'] ? __('article_audio.configuration.configured') : __('article_audio.configuration.not_configured') }}
                        </dd>
                        <p class="mt-1 truncate font-mono text-xs text-gray-500" dir="ltr">{{ $configuration['preparation_model'] }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('article_audio.configuration.elevenlabs') }}</dt>
                        <dd class="mt-1 font-semibold text-gray-950 dark:text-white">
                            {{ $configuration['api_key_configured'] ? __('article_audio.configuration.configured') : __('article_audio.configuration.not_configured') }}
                        </dd>
                        <p class="mt-1 text-xs text-gray-500">{{ __('article_audio.configuration.server_only') }}</p>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('article_audio.configuration.voice') }}</dt>
                        <dd class="mt-1 font-mono font-semibold text-gray-950 dark:text-white" dir="ltr">{{ $configuration['voice_label'] }}</dd>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('article_audio.configuration.output') }}</dt>
                        <dd class="mt-1 font-mono text-xs font-semibold text-gray-950 dark:text-white" dir="ltr">{{ $configuration['output_format'] }}</dd>
                    </div>
                </dl>
            </div>

            @if ($hasActiveWork)
                <div class="flex items-center gap-3 border-t border-info-200 bg-info-50 px-6 py-3 text-sm text-info-800 dark:border-info-500/20 dark:bg-info-500/10 dark:text-info-200">
                    <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
                    <span>{{ __('article_audio.page.auto_refresh_locked') }}</span>
                </div>
            @endif
        </section>

        @unless ($canGenerate)
            <div class="rounded-xl border border-warning-300 bg-warning-50 px-4 py-3 text-sm text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-200">
                {{ __('article_audio.page.read_only') }}
            </div>
        @endunless

        <section class="space-y-3">
            <header class="flex flex-col gap-2 px-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ __('article_audio.page.library') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('article_audio.page.library_description') }}</p>
                </div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('article_audio.page.cost_guard') }}</p>
            </header>

            @foreach ($rows as $row)
                <details class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm open:border-primary-300 dark:border-white/10 dark:bg-gray-900 dark:open:border-primary-500/40">
                    <summary class="flex cursor-pointer list-none flex-col gap-4 p-5 marker:hidden sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $row['locale_label'] }}</span>
                                <x-filament::badge :color="$row['narration_status_color']">
                                    {{ __('article_audio.labels.script') }} · {{ $row['narration_status_label'] }}
                                </x-filament::badge>
                                <x-filament::badge :color="$row['status_color']">
                                    {{ __('article_audio.labels.audio') }} · {{ $row['status_label'] }}
                                </x-filament::badge>
                            </div>
                            <h3 class="mt-2 truncate text-base font-semibold leading-7 text-gray-950 dark:text-white" dir="{{ $row['locale'] === 'ar' ? 'rtl' : 'ltr' }}">
                                {{ $row['title'] }}
                            </h3>
                        </div>
                        <div class="flex shrink-0 items-center gap-2 text-sm font-semibold text-primary-700 dark:text-primary-300">
                            <span>{{ __('article_audio.actions.manage') }}</span>
                            <x-heroicon-o-chevron-down class="h-5 w-5 transition group-open:rotate-180" />
                        </div>
                    </summary>

                    <div class="space-y-6 border-t border-gray-200 p-5 dark:border-white/10">
                        @if ($row['error_message'])
                            <div class="rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm leading-6 text-danger-700 dark:border-danger-500/20 dark:bg-danger-500/10 dark:text-danger-200">
                                {{ $row['error_message'] }}
                            </div>
                        @elseif ($row['is_stale'])
                            <div class="rounded-xl border border-warning-200 bg-warning-50 px-4 py-3 text-sm text-warning-700 dark:border-warning-500/20 dark:bg-warning-500/10 dark:text-warning-200">
                                {{ __('article_audio.page.stale_explanation') }}
                            </div>
                        @endif

                        @if (! $row['narration_is_current'] || blank($row['narration']?->script))
                            <div class="grid gap-5 rounded-xl border border-dashed border-primary-300 bg-primary-50/50 p-5 dark:border-primary-500/30 dark:bg-primary-500/5 lg:grid-cols-[1fr_auto] lg:items-center">
                                <div>
                                    <h4 class="font-semibold text-gray-950 dark:text-white">{{ __('article_audio.narration.prepare_title') }}</h4>
                                    <p class="mt-1 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">{{ __('article_audio.narration.prepare_description') }}</p>
                                </div>
                                @if ($canGenerate)
                                    <form method="POST" action="{{ route('filament.admin.article-audio.narration.prepare', ['article' => $row['key'], 'locale' => $row['locale']]) }}">
                                        @csrf
                                        <x-filament::button type="submit" icon="heroicon-o-sparkles" :disabled="! $configuration['preparation_ready'] || $hasActiveWork">
                                            {{ $row['is_preparing'] ? __('article_audio.actions.preparing') : __('article_audio.actions.prepare_with_ai') }}
                                        </x-filament::button>
                                    </form>
                                @endif
                            </div>
                        @else
                            <form method="POST" action="{{ route('filament.admin.article-audio.narration.update', ['article' => $row['key'], 'locale' => $row['locale']]) }}" class="space-y-4">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="row_ref" value="{{ $row['key'] }}:{{ $row['locale'] }}">

                                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <label for="script-{{ $row['key'] }}-{{ $row['locale'] }}" class="text-sm font-semibold text-gray-950 dark:text-white">
                                            {{ __('article_audio.narration.editor_label') }}
                                        </label>
                                        <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ __('article_audio.narration.editor_hint') }}</p>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format(mb_strlen((string) $row['narration']->script)) }} {{ __('article_audio.page.characters') }}
                                    </span>
                                </div>

                                <textarea
                                    id="script-{{ $row['key'] }}-{{ $row['locale'] }}"
                                    name="script"
                                    rows="16"
                                    dir="{{ $row['locale'] === 'ar' ? 'rtl' : 'ltr' }}"
                                    @disabled($hasActiveWork || ! $canGenerate)
                                    class="block w-full rounded-xl border-gray-300 bg-white px-4 py-4 text-base leading-8 text-gray-950 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-50 dark:border-white/10 dark:bg-gray-950 dark:text-white dark:disabled:bg-white/5"
                                >{{ old('row_ref') === $row['key'].':'.$row['locale'] ? old('script') : $row['narration']->script }}</textarea>

                                @if (old('row_ref') === $row['key'].':'.$row['locale'])
                                    @error('script')
                                        <p class="text-sm text-danger-600 dark:text-danger-300">{{ $message }}</p>
                                    @enderror
                                @endif

                                @if (! empty($row['narration']->preparation_notes) || ! empty($row['narration']->pronunciation_notes))
                                    <div class="grid gap-3 lg:grid-cols-2">
                                        @if (! empty($row['narration']->preparation_notes))
                                            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                                                <h5 class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('article_audio.narration.edit_notes') }}</h5>
                                                <ul class="mt-2 space-y-1 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                                    @foreach ($row['narration']->preparation_notes as $note)
                                                        <li>• {{ $note }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                        @if (! empty($row['narration']->pronunciation_notes))
                                            <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                                                <h5 class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('article_audio.narration.pronunciation_notes') }}</h5>
                                                <ul class="mt-2 space-y-1 text-sm leading-6 text-gray-700 dark:text-gray-300">
                                                    @foreach ($row['narration']->pronunciation_notes as $note)
                                                        <li>• {{ $note }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($canGenerate)
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <x-filament::button type="submit" name="action" value="save" color="gray" icon="heroicon-o-document-check" :disabled="$hasActiveWork">
                                            {{ __('article_audio.actions.save_draft') }}
                                        </x-filament::button>
                                        <x-filament::button type="submit" name="action" value="approve" icon="heroicon-o-check-badge" :disabled="$hasActiveWork">
                                            {{ $row['narration_is_approved'] ? __('article_audio.actions.reapprove') : __('article_audio.actions.approve') }}
                                        </x-filament::button>
                                    </div>
                                @endif
                            </form>

                            <div class="space-y-3">
                                <div>
                                    <h4 class="font-semibold text-gray-950 dark:text-white">{{ __('article_audio.sample.title') }}</h4>
                                    <p class="mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ __('article_audio.sample.description') }}</p>
                                </div>

                                <div class="grid gap-4 xl:grid-cols-2">
                                    @foreach ($row['models'] as $model)
                                        <article class="flex flex-col gap-4 rounded-xl border border-gray-200 p-4 dark:border-white/10">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <h5 class="font-semibold text-gray-950 dark:text-white" dir="ltr">{{ $model['label'] }}</h5>
                                                    <p class="mt-1 font-mono text-xs text-gray-500" dir="ltr">{{ $model['id'] }}</p>
                                                </div>
                                                <x-filament::badge :color="$model['sample_status_color']">
                                                    {{ $model['sample_status_label'] }}
                                                </x-filament::badge>
                                            </div>

                                            @if ($model['sample_url'])
                                                <audio class="w-full" controls preload="metadata" src="{{ $model['sample_url'] }}">
                                                    {{ __('article_audio.page.audio_unsupported') }}
                                                </audio>
                                            @elseif (data_get($model, 'sample.last_error'))
                                                <p class="text-sm leading-6 text-danger-600 dark:text-danger-300">{{ data_get($model, 'sample.last_error') }}</p>
                                            @else
                                                <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">{{ __('article_audio.sample.empty') }}</p>
                                            @endif

                                            @if ($canGenerate)
                                                <div class="mt-auto flex flex-wrap gap-2">
                                                    <form method="POST" action="{{ route('filament.admin.article-audio.sample.generate', ['article' => $row['key'], 'locale' => $row['locale']]) }}">
                                                        @csrf
                                                        <input type="hidden" name="model_id" value="{{ $model['id'] }}">
                                                        <x-filament::button type="submit" size="sm" color="gray" icon="heroicon-o-beaker" :disabled="! $configuration['synthesis_ready'] || $hasActiveWork">
                                                            {{ $model['is_sample_generating'] ? __('article_audio.actions.generating_sample') : __('article_audio.actions.generate_sample') }}
                                                        </x-filament::button>
                                                    </form>

                                                    @if ($model['can_generate_full'])
                                                        <form
                                                            method="POST"
                                                            action="{{ route('filament.admin.article-audio.generate', ['article' => $row['key'], 'locale' => $row['locale']]) }}"
                                                            x-data
                                                            x-on:submit="if (! window.confirm(@js(__('article_audio.actions.confirm_full_generation')))) $event.preventDefault()"
                                                        >
                                                            @csrf
                                                            <input type="hidden" name="model_id" value="{{ $model['id'] }}">
                                                            <x-filament::button type="submit" size="sm" icon="heroicon-o-speaker-wave" :disabled="$hasActiveWork || $row['is_generating']">
                                                                {{ $row['is_generating'] ? __('article_audio.actions.generating') : __('article_audio.actions.generate_full') }}
                                                            </x-filament::button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>

                                @unless ($row['narration_is_approved'])
                                    <p class="rounded-lg bg-warning-50 px-3 py-2 text-xs leading-5 text-warning-700 dark:bg-warning-500/10 dark:text-warning-200">
                                        {{ __('article_audio.sample.approval_gate') }}
                                    </p>
                                @endunless
                            </div>
                        @endif

                        @if ($row['audio_url'])
                            <div class="rounded-xl bg-success-50 p-4 dark:bg-success-500/10">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <h4 class="font-semibold text-success-900 dark:text-success-100">{{ __('article_audio.final.title') }}</h4>
                                    <span class="font-mono text-xs text-success-700 dark:text-success-300" dir="ltr">{{ $row['track']?->model_id }}</span>
                                </div>
                                <audio class="w-full" controls preload="metadata" src="{{ $row['audio_url'] }}">
                                    {{ __('article_audio.page.audio_unsupported') }}
                                </audio>
                                <p class="mt-2 text-xs text-success-700 dark:text-success-300">
                                    {{ __('article_audio.page.generated_at') }}: {{ $row['track']?->generated_at?->diffForHumans() }}
                                </p>
                            </div>
                        @endif
                    </div>
                </details>
            @endforeach
        </section>

        <section class="grid gap-4 md:grid-cols-2">
            @foreach ($configuration['models'] as $modelId => $profile)
                <article class="rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-white/10 dark:bg-white/5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-950 dark:text-white" dir="ltr">{{ data_get($profile, 'label', $modelId) }}</h3>
                            <p class="mt-1 font-mono text-xs text-gray-500" dir="ltr">{{ $modelId }}</p>
                        </div>
                        <span class="text-xs text-gray-500">{{ number_format((int) data_get($profile, 'max_characters')) }} {{ __('article_audio.page.characters_per_request') }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs sm:grid-cols-5">
                        @foreach ((array) data_get($profile, 'voice_settings', []) as $name => $value)
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('article_audio.settings.'.$name) }}</dt>
                                <dd class="mt-1 font-mono font-semibold text-gray-950 dark:text-white">
                                    {{ is_bool($value) ? ($value ? __('article_audio.configuration.yes') : __('article_audio.configuration.no')) : $value }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </article>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
