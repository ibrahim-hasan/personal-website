@props([
    // JavaScript global function name Turnstile calls with the token once
    // solved. Only used by Livewire forms that need to push the token into
    // component state. Plain HTML forms leave it null; the token travels in
    // the form body as `cf-turnstile-response`.
    'callback' => null,
    // When true, render only a data container and let the caller render the
    // widget explicitly via turnstile.render(). Used by Livewire/SPA forms so
    // the widget can be reset by id after a failed submission. Plain HTML forms
    // use auto-render (cf-turnstile class) instead.
    'explicit' => false,
    // Stable id for explicit-mode containers, e.g. 'consultation-turnstile'.
    'id' => null,
    // Class added to the wrapper for layout (forms center the widget).
    'class' => null,
])

@php
    /** @var \App\Support\Turnstile $turnstile */
    $turnstile = app(\App\Support\Turnstile::class);
    $siteKey = $turnstile->siteKey();
@endphp

@if ($siteKey !== null)
    @once
        @push('scripts')
            @if ($explicit)
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
            @else
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
            @endif
        @endpush
    @endonce

    <div {{ $attributes->merge(['class' => $class]) }}>
        @if ($explicit)
            <div
                id="{{ $id }}"
                data-sitekey="{{ $siteKey }}"
                data-action="turnstile-spin-v2"
            ></div>
        @else
            <div
                class="cf-turnstile"
                data-sitekey="{{ $siteKey }}"
                data-action="turnstile-spin-v2"
                @if ($callback) data-callback="{{ $callback }}" @endif
            ></div>
        @endif
    </div>
@endif
