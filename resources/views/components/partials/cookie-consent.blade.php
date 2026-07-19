@props([
    'autoOpen' => true,
])

<div
    x-data="cookieConsent({ autoOpen: {{ $autoOpen ? 'true' : 'false' }} })"
    x-cloak
    x-show="visible"
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-end justify-center bg-ink/55 p-4 sm:items-center sm:p-6"
    data-cookie-consent
    data-cookie-consent-auto-open="{{ $autoOpen ? 'true' : 'false' }}"
    @keydown.escape.window="close"
>
    <section
        x-ref="dialog"
        class="relative max-h-[calc(100svh-2rem)] w-full max-w-lg overflow-y-auto border border-ink/15 bg-canvas-bright p-6 shadow-[0.6rem_0.6rem_0_rgba(24,16,49,0.2)] sm:p-7"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cookie-consent-title"
        aria-describedby="cookie-consent-description"
        @keydown.tab="trapFocus"
    >
        <button
            type="button"
            class="absolute end-7 top-7 grid size-11 place-items-center rounded-full border border-ink/15 text-ink transition hover:bg-violet-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-700"
            @click="close"
            aria-label="{{ __('legal.cookies.close') }}"
        >
            <x-phosphor-x class="size-5" aria-hidden="true" />
        </button>
        <p class="signal-label text-violet-700">{{ __('legal.cookies.eyebrow') }}</p>
        <h2 id="cookie-consent-title" class="mt-3 font-display text-2xl font-black text-ink sm:text-3xl">{{ __('legal.cookies.title') }}</h2>
        <p id="cookie-consent-description" class="mt-3 text-sm leading-6 text-ink-muted">{{ __('legal.cookies.body') }}</p>

        <div class="mt-6 grid grid-cols-2 gap-3">
            <button x-ref="accept" type="button" class="button-primary w-full justify-center" :aria-pressed="savedConsent === 'accepted'" @click="accept">{{ __('legal.cookies.accept') }}</button>
            <button x-ref="reject" type="button" class="button-primary w-full justify-center" :aria-pressed="savedConsent === 'rejected'" @click="reject">{{ __('legal.cookies.reject') }}</button>
        </div>

        <a href="{{ localized_route('cookies') }}" data-no-navigate class="mt-5 inline-flex text-sm font-bold text-violet-700 underline decoration-violet-700/30 underline-offset-4 hover:decoration-violet-700">{{ __('legal.cookies.learn_more') }}</a>
    </section>
</div>
