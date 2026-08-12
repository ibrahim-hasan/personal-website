@props([
    'eyebrow',
    'title',
    'body',
    'actionUrl' => null,
    'actionLabel' => null,
    'tone' => 'light',
    'analyticsEvent' => null,
    'analyticsUiLocation' => null,
    'analyticsDestinationCategory' => null,
])

<div
    role="status"
    {{ $attributes->class([
        'content-empty',
        'content-empty--dark' => $tone === 'dark',
    ]) }}
>
    <p class="signal-label {{ $tone === 'dark' ? 'signal-label--light' : '' }}">{{ $eyebrow }}</p>
    <h2>{{ $title }}</h2>
    <p>{{ $body }}</p>

    @if ($actionUrl && $actionLabel)
        <a
            href="{{ $actionUrl }}"
            class="{{ $tone === 'dark' ? 'button-light' : 'button-primary' }}"
            @if ($analyticsEvent) data-analytics-event="{{ $analyticsEvent }}" @endif
            @if ($analyticsUiLocation) data-analytics-ui-location="{{ $analyticsUiLocation }}" @endif
            @if ($analyticsDestinationCategory) data-analytics-destination-category="{{ $analyticsDestinationCategory }}" @endif
        >
            <span>{{ $actionLabel }}</span>
            <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
        </a>
    @endif
</div>
