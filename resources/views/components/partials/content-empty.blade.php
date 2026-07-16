@props([
    'eyebrow',
    'title',
    'body',
    'actionUrl' => null,
    'actionLabel' => null,
    'tone' => 'light',
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
        <a href="{{ $actionUrl }}" class="{{ $tone === 'dark' ? 'button-light' : 'button-primary' }}">
            <span>{{ $actionLabel }}</span>
            <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
        </a>
    @endif
</div>
