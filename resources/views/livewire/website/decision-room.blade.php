<section
    class="decision-room relative overflow-hidden border border-violet-800 bg-violet-900 text-canvas shadow-2xl"
    aria-labelledby="decision-room-title"
    wire:loading.attr="aria-busy"
    wire:target="selectChallenge,selectFriction,selectOutcome,showRecommendation,back,resetDecisionRoom"
>
    <div class="decision-room__layout grid min-h-[38rem] lg:grid-cols-[minmax(18rem,0.78fr)_minmax(0,2fr)]">
        <aside class="decision-room__rail flex flex-col justify-between gap-10 border-b border-canvas/15 bg-ink/20 p-6 sm:p-8 lg:border-b-0 lg:border-e">
            <div>
                <p class="signal-label signal-label--light">{{ $copy['eyebrow'] }}</p>
                <h2 id="decision-room-title" class="decision-room__title mt-6 max-w-[13ch] font-display text-3xl font-black leading-tight text-canvas sm:text-4xl">
                    {{ $copy['title'] }}
                </h2>
                <p class="mt-5 max-w-[34rem] font-body text-base leading-7 text-canvas/70">
                    {{ $copy['intro'] }}
                </p>
                <a class="mt-6 inline-flex font-sans text-sm font-bold text-violet-200 underline decoration-violet-400 underline-offset-4 hover:text-canvas" href="{{ $directConsultationUrl }}">
                    {{ $copy['direct'] }}
                </a>
            </div>

            <ol class="decision-room__progress grid gap-3" aria-label="{{ $copy['progress_label'] }}">
                @foreach ($copy['steps'] as $stepName)
                    <li
                        wire:key="decision-room-progress-{{ $loop->iteration }}"
                        @class([
                            'decision-room__progress-item grid grid-cols-[2.5rem_1fr] items-center gap-3 border-t py-3 font-sans text-sm',
                            'border-violet-300 text-canvas' => $activeStep >= $loop->iteration,
                            'border-canvas/15 text-canvas/45' => $activeStep < $loop->iteration,
                        ])
                        @if ($activeStep === $loop->iteration) aria-current="step" @endif
                    >
                        <span
                            @class([
                                'grid h-8 w-8 place-items-center border text-xs font-bold',
                                'border-violet-300 bg-violet-700 text-canvas' => $activeStep === $loop->iteration,
                                'border-canvas/20 text-canvas/60' => $activeStep !== $loop->iteration,
                            ])
                            aria-hidden="true"
                        >
                            {{ sprintf('%02d', $loop->iteration) }}
                        </span>
                        <span>{{ $stepName }}</span>
                    </li>
                @endforeach
            </ol>
        </aside>

        <div class="decision-room__stage flex min-w-0 flex-col bg-canvas-bright text-ink">
            <header class="decision-room__status flex min-h-16 flex-wrap items-center justify-between gap-3 border-b border-ink/15 px-5 py-4 sm:px-8">
                <p class="font-sans text-xs font-bold uppercase tracking-[0.14em] text-violet-700">
                    {{ $copy['step_label'] }} {{ $activeStep }} / 3
                </p>

                <div class="flex min-h-8 items-center gap-4">
                    <p
                        class="items-center gap-2 font-sans text-xs font-bold text-violet-700"
                        role="status"
                        wire:loading.flex
                        wire:target="selectChallenge,selectFriction,selectOutcome,showRecommendation,back,resetDecisionRoom"
                    >
                        <span class="h-2 w-2 animate-pulse bg-violet-500" aria-hidden="true"></span>
                        {{ $copy['loading'] }}
                    </p>

                    @if ($selectedChallenge !== null)
                        <button
                            type="button"
                            class="font-sans text-xs font-bold text-ink-muted underline decoration-ink/25 underline-offset-4 hover:text-violet-700"
                            wire:click="resetDecisionRoom"
                            wire:loading.attr="disabled"
                            wire:target="resetDecisionRoom"
                        >
                            {{ $copy['reset'] }}
                        </button>
                    @endif
                </div>
            </header>

            <div class="decision-room__workspace flex flex-1 flex-col p-5 sm:p-8 lg:p-10">
                @if ($activeStep === 1)
                    <fieldset class="decision-room__fieldset min-w-0 border-0 p-0" aria-describedby="decision-room-challenge-hint">
                        <legend class="block w-full max-w-[18ch] font-display text-3xl font-black leading-tight text-ink sm:text-4xl">
                            {{ $copy['choose_challenge'] }}
                        </legend>
                        <p id="decision-room-challenge-hint" class="mt-4 max-w-[58ch] font-body text-base leading-7 text-ink-muted">
                            {{ $copy['challenge_hint'] }}
                        </p>

                        <div class="decision-room__option-grid mt-8 grid gap-3 md:grid-cols-2">
                            @foreach ($challenges as $challengeId => $challenge)
                                <button
                                    wire:key="decision-room-challenge-{{ $challengeId }}"
                                    type="button"
                                    wire:click="selectChallenge('{{ $challengeId }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="selectChallenge"
                                    aria-pressed="{{ $selectedChallenge === $challengeId ? 'true' : 'false' }}"
                                    @class([
                                        'decision-room__option group flex min-h-44 flex-col justify-between gap-6 rounded-[var(--control-radius)] border p-5 text-start motion-safe:transition sm:p-6',
                                        'border-violet-600 bg-violet-50 shadow-lg' => $selectedChallenge === $challengeId,
                                        'border-ink/15 bg-canvas-bright hover:border-violet-400 hover:bg-violet-50/60' => $selectedChallenge !== $challengeId,
                                    ])
                                >
                                    <span class="flex items-start justify-between gap-4">
                                        <span class="font-sans text-xs font-bold text-violet-700">{{ sprintf('%02d', $loop->iteration) }}</span>
                                        @if ($selectedChallenge === $challengeId)
                                            <span class="rounded-full bg-violet-700 px-2 py-1 font-sans text-[0.68rem] font-bold text-violet-50">
                                                {{ $copy['selected'] }}
                                            </span>
                                        @endif
                                    </span>
                                    <span>
                                        <strong class="block font-display text-2xl font-black leading-tight text-ink">{{ $challenge['label'] }}</strong>
                                        <span class="mt-3 block font-body text-sm leading-6 text-ink-muted">{{ $challenge['description'] }}</span>
                                    </span>
                                    <span class="flex items-center justify-end text-xl text-violet-600 motion-safe:transition group-hover:translate-x-1 rtl:group-hover:-translate-x-1" aria-hidden="true">
                                        <span class="rtl:rotate-180">→</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>
                @elseif ($activeStep === 2)
                    <div class="decision-room__context-intro">
                        <p class="font-sans text-xs font-bold uppercase tracking-[0.14em] text-violet-700">
                            {{ $challenges[$selectedChallenge]['label'] }}
                        </p>
                        <h3 class="mt-3 max-w-[17ch] font-display text-3xl font-black leading-tight text-ink sm:text-4xl">
                            {{ $copy['context_title'] }}
                        </h3>
                        <p class="mt-4 max-w-[58ch] font-body text-base leading-7 text-ink-muted">
                            {{ $copy['context_intro'] }}
                        </p>
                    </div>

                    <div class="decision-room__context-grid mt-8 grid gap-6 xl:grid-cols-2">
                        <fieldset class="decision-room__fieldset min-w-0 border border-ink/15 bg-canvas p-5 sm:p-6" aria-describedby="decision-room-friction-hint">
                            <legend class="px-2 font-display text-xl font-black text-ink">
                                {{ $copy['choose_friction'] }}
                            </legend>
                            <p id="decision-room-friction-hint" class="mt-1 font-body text-sm leading-6 text-ink-muted">
                                {{ $copy['friction_hint'] }}
                            </p>

                            <div class="decision-room__choice-list mt-5 grid gap-2">
                                @foreach ($frictions as $frictionId => $friction)
                                    <button
                                        wire:key="decision-room-friction-{{ $frictionId }}"
                                        type="button"
                                        wire:click="selectFriction('{{ $frictionId }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="selectFriction"
                                        aria-pressed="{{ $primaryFriction === $frictionId ? 'true' : 'false' }}"
                                        @class([
                                            'decision-room__choice min-h-16 rounded-[var(--control-radius)] border px-4 py-3 text-start font-body text-sm font-bold leading-6 motion-safe:transition',
                                            'border-violet-600 bg-violet-100/70 text-violet-900' => $primaryFriction === $frictionId,
                                            'border-ink/15 bg-canvas-bright text-ink-soft hover:border-violet-400 hover:bg-violet-50' => $primaryFriction !== $frictionId,
                                        ])
                                    >
                                        {{ $friction['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="decision-room__fieldset min-w-0 border border-ink/15 bg-canvas p-5 sm:p-6" aria-describedby="decision-room-outcome-hint">
                            <legend class="px-2 font-display text-xl font-black text-ink">
                                {{ $copy['choose_outcome'] }}
                            </legend>
                            <p id="decision-room-outcome-hint" class="mt-1 font-body text-sm leading-6 text-ink-muted">
                                {{ $copy['outcome_hint'] }}
                            </p>

                            <div class="decision-room__choice-list mt-5 grid gap-2">
                                @foreach ($outcomes as $outcomeId => $outcome)
                                    <button
                                        wire:key="decision-room-outcome-{{ $outcomeId }}"
                                        type="button"
                                        wire:click="selectOutcome('{{ $outcomeId }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="selectOutcome"
                                        aria-pressed="{{ $desiredOutcome === $outcomeId ? 'true' : 'false' }}"
                                        @class([
                                            'decision-room__choice min-h-16 rounded-[var(--control-radius)] border px-4 py-3 text-start font-body text-sm font-bold leading-6 motion-safe:transition',
                                            'border-violet-600 bg-violet-100/70 text-violet-900' => $desiredOutcome === $outcomeId,
                                            'border-ink/15 bg-canvas-bright text-ink-soft hover:border-violet-400 hover:bg-violet-50' => $desiredOutcome !== $outcomeId,
                                        ])
                                    >
                                        {{ $outcome['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>

                    <div class="decision-room__actions mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-ink/15 pt-6">
                        <button
                            type="button"
                            class="button-quiet"
                            wire:click="back"
                            wire:loading.attr="disabled"
                            wire:target="back"
                        >
                            {{ $copy['back'] }}
                        </button>
                        <button
                            type="button"
                            class="button-primary disabled:cursor-not-allowed"
                            wire:click="showRecommendation"
                            wire:loading.attr="disabled"
                            wire:target="showRecommendation"
                            @disabled(! $canShowRecommendation)
                        >
                            <span wire:loading.remove wire:target="showRecommendation">{{ $copy['review'] }}</span>
                            <span wire:loading wire:target="showRecommendation">{{ $copy['review_loading'] }}</span>
                            <span wire:loading.remove wire:target="showRecommendation" class="text-lg rtl:rotate-180" aria-hidden="true">→</span>
                        </button>
                    </div>
                @endif

                <div class="decision-room__live-region" aria-live="polite" aria-atomic="true">
                    @if ($activeStep === 3 && $recommendation !== null && $consultationUrl !== null)
                        <article class="decision-room__summary">
                            <p class="font-sans text-xs font-bold uppercase tracking-[0.14em] text-violet-700">
                                {{ $copy['recommendation_eyebrow'] }}
                            </p>
                            <h3 class="mt-4 max-w-[17ch] font-display text-4xl font-black leading-tight text-ink sm:text-5xl">
                                {{ $recommendation['title'] }}
                            </h3>
                            <p class="mt-6 max-w-[68ch] font-body text-lg leading-8 text-ink-soft">
                                {{ $recommendation['body'] }}
                            </p>

                            <div class="decision-room__context mt-8 border border-ink/15 bg-canvas p-5 sm:p-6">
                                <p class="font-sans text-xs font-bold uppercase tracking-[0.12em] text-violet-700">
                                    {{ $copy['context_label'] }}
                                </p>
                                <dl class="mt-5 grid gap-5 md:grid-cols-3">
                                    <div class="border-t border-ink/15 pt-4">
                                        <dt class="font-sans text-xs font-bold text-ink-muted">{{ $copy['challenge_label'] }}</dt>
                                        <dd class="mt-2 font-body text-base font-bold leading-6 text-ink">{{ $recommendation['challenge'] }}</dd>
                                    </div>
                                    <div class="border-t border-ink/15 pt-4">
                                        <dt class="font-sans text-xs font-bold text-ink-muted">{{ $copy['friction_label'] }}</dt>
                                        <dd class="mt-2 font-body text-base font-bold leading-6 text-ink">{{ $recommendation['friction'] }}</dd>
                                    </div>
                                    <div class="border-t border-ink/15 pt-4">
                                        <dt class="font-sans text-xs font-bold text-ink-muted">{{ $copy['outcome_label'] }}</dt>
                                        <dd class="mt-2 font-body text-base font-bold leading-6 text-ink">{{ $recommendation['outcome'] }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <p class="mt-5 max-w-[65ch] font-body text-sm leading-6 text-ink-muted">
                                {{ $copy['disclaimer'] }}
                            </p>

                            <div class="decision-room__actions mt-8 flex flex-wrap items-center gap-3 border-t border-ink/15 pt-6">
                                <button
                                    type="button"
                                    class="button-quiet"
                                    wire:click="back"
                                    wire:loading.attr="disabled"
                                    wire:target="back"
                                >
                                    {{ $copy['back'] }}
                                </button>
                                <a
                                    class="button-primary"
                                    href="{{ $consultationUrl }}"
                                    data-no-navigate
                                >
                                    <span>{{ $copy['consultation'] }}</span>
                                    <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
                                </a>
                            </div>
                        </article>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
