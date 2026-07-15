@props([
    'label' => __('site.media.placeholder'),
    'ratio' => 'landscape',
    'index' => null,
    'dark' => false,
])

@php
    $ratioClass = match ($ratio) {
        'portrait' => 'aspect-[4/5]',
        'square' => 'aspect-square',
        'wide' => 'aspect-[16/9]',
        default => 'aspect-[4/3]',
    };
@endphp

<div
    role="img"
    aria-label="{{ $label }}"
    {{ $attributes->class([
        'media-placeholder group relative isolate overflow-hidden',
        $ratioClass,
        'media-placeholder--dark' => $dark,
    ]) }}
>
    <span class="media-placeholder__axis media-placeholder__axis--x" aria-hidden="true"></span>
    <span class="media-placeholder__axis media-placeholder__axis--y" aria-hidden="true"></span>
    <span class="media-placeholder__scan" aria-hidden="true"></span>

    @if ($index)
        <span class="media-placeholder__index">{{ $index }}</span>
    @endif

    <span class="media-placeholder__label">
        <span>{{ $label }}</span>
        <span aria-hidden="true">↗</span>
    </span>
</div>
