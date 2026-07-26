@props([
    'image' => null,
    'src' => '',
    'alt' => '',
    'width' => null,
    'height' => null,
    'sizes' => '100vw',
    'loading' => 'lazy',
    'fetchpriority' => null,
    'decoding' => 'async',
])

@php
    $imageData = is_array($image) ? $image : [];
    $source = (string) ($imageData['src'] ?? $src);
    $srcset = (string) ($imageData['srcset'] ?? '');
    $intrinsicWidth = (int) ($imageData['width'] ?? $width ?? 0);
    $intrinsicHeight = (int) ($imageData['height'] ?? $height ?? 0);
@endphp

@if (filled($source))
    <img
        src="{{ $source }}"
        @if (filled($srcset))
            srcset="{{ $srcset }}"
            sizes="{{ $sizes }}"
        @endif
        alt="{{ $alt }}"
        @if ($intrinsicWidth > 0) width="{{ $intrinsicWidth }}" @endif
        @if ($intrinsicHeight > 0) height="{{ $intrinsicHeight }}" @endif
        loading="{{ $loading }}"
        @if ($fetchpriority !== null) fetchpriority="{{ $fetchpriority }}" @endif
        decoding="{{ $decoding }}"
        {{ $attributes }}
    >
@endif
