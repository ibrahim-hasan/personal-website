@props([
    // JavaScript global function name Turnstile calls with the token once
    // solved. Only used by Livewire forms that need to push the token into
    // component state. Plain HTML forms leave it null; the token travels in
    // the form body as `cf-turnstile-response`.
    'callback' => null,
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
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce

    <div {{ $attributes->merge(['class' => $class]) }}>
        <div
            class="cf-turnstile"
            data-sitekey="{{ $siteKey }}"
            data-action="turnstile-spin-v2"
            @if ($callback) data-callback="{{ $callback }}" @endif
        ></div>
    </div>
@endif
