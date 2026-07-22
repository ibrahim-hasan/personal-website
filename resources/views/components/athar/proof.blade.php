@props(['cards' => [], 'placement' => null])
@php($proofTitle = $placement !== null ? __('athar.proof_titles.'.$placement) : __('athar.proof_title'))
@php($proofLabel = $placement !== null ? __('athar.proof_labels.'.$placement) : __('athar.public_label'))
@if ($cards !== [])
    <section
        class="athar-proof{{ $placement !== null ? ' athar-proof--'.$placement : '' }}"
        aria-labelledby="athar-proof-title"
        x-data="atharProof({ count: {{ count($cards) }} })"
        x-init="init()"
        @mouseenter="pauseForHover()"
        @mouseleave="resumeAfterHover()"
        @focusin="pauseForHover()"
        @focusout="resumeAfterHover()"
    >
        <div class="site-container athar-proof__inner">
            <div class="athar-proof__intro">
                <p class="signal-label">{{ $proofLabel }}</p>
                <h2 id="athar-proof-title" class="display-section">{{ $proofTitle }}</h2>
                <p class="athar-proof__count" aria-live="polite"><span x-text="displayPosition()">1 / {{ count($cards) }}</span> · {{ __('athar.slider.label') }}</p>
                <div class="athar-proof__controls" role="group" aria-label="{{ __('athar.slider.controls') }}">
                    <button type="button" class="athar-proof__control" @click="previous()" aria-label="{{ __('athar.slider.previous') }}">
                        @if (app()->getLocale() === 'ar')
                            <x-phosphor-arrow-right aria-hidden="true" />
                        @else
                            <x-phosphor-arrow-left aria-hidden="true" />
                        @endif
                    </button>
                    <button type="button" class="athar-proof__control" @click="next()" aria-label="{{ __('athar.slider.next') }}">
                        @if (app()->getLocale() === 'ar')
                            <x-phosphor-arrow-left aria-hidden="true" />
                        @else
                            <x-phosphor-arrow-right aria-hidden="true" />
                        @endif
                    </button>
                    <button type="button" class="athar-proof__pause" @click="toggleAutoplay()" :aria-pressed="paused.toString()" x-text="paused ? '{{ __('athar.slider.play') }}' : '{{ __('athar.slider.pause') }}'">{{ __('athar.slider.pause') }}</button>
                </div>
            </div>
            <div class="athar-proof__stage" role="region" aria-roledescription="carousel" aria-label="{{ __('athar.slider.region') }}">
                @foreach ($cards as $card)
                    <article
                        class="athar-proof__slide"
                        :class="{ 'is-active': active === {{ $loop->index }} }"
                        x-show="active === {{ $loop->index }}"
                        x-transition:enter="athar-proof__slide-enter"
                        x-transition:enter-start="athar-proof__slide-enter-start"
                        x-transition:enter-end="athar-proof__slide-enter-end"
                        x-transition:leave="athar-proof__slide-leave"
                        x-transition:leave-start="athar-proof__slide-leave-start"
                        x-transition:leave-end="athar-proof__slide-leave-end"
                        role="group"
                        aria-roledescription="slide"
                        aria-label="{{ $loop->iteration }} / {{ count($cards) }}"
                        dir="{{ $card['locale'] === 'ar' ? 'rtl' : 'ltr' }}"
                        lang="{{ $card['locale'] }}"
                    >
                        <span class="athar-proof__index" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                        @php($textLength = mb_strlen($card['text']))
                        <blockquote class="{{ $textLength > 600 ? 'athar-proof__quote--compact' : '' }}">{{ $card['text'] }}</blockquote>
                        @if ($card['context'] !== '')<p class="athar-proof__context">{{ $card['context'] }}</p>@endif
                        @if ($card['name'] !== '')<p class="athar-proof__name">{{ $card['name'] }}</p>@endif
                        <p class="athar-proof__disclosure">{{ __('athar.disclosure') }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
