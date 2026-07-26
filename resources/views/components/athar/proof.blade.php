@props(['cards' => [], 'placement' => null])
@php($proofTitle = $placement !== null ? __('athar.proof_titles.'.$placement) : __('athar.proof_title'))
@php($proofLabel = $placement !== null ? __('athar.proof_labels.'.$placement) : __('athar.public_label'))
@php($proofId = 'athar-proof-title-'.($placement ?? 'general'))
@if ($cards !== [])
    <section
        class="athar-proof{{ $placement !== null ? ' athar-proof--'.$placement : '' }}"
        aria-labelledby="{{ $proofId }}"
    >
        <div class="site-container athar-proof__inner">
            <div class="athar-proof__intro">
                <p class="signal-label">{{ $proofLabel }}</p>
                <h2 id="{{ $proofId }}" class="display-section">{{ $proofTitle }}</h2>
            </div>
            <div class="athar-proof__stage" role="region" aria-label="{{ $proofTitle }}">
                @foreach ($cards as $card)
                    <article
                        class="athar-proof__slide"
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
