@props([
    'autoOpen' => false,
])

<div
    x-data="cookieConsent({ autoOpen: {{ $autoOpen ? 'true' : 'false' }} })"
    x-cloak
    x-show="visible"
    x-transition.opacity
    class="pointer-events-none fixed inset-x-0 bottom-0 z-40 px-4 pb-[calc(1rem+env(safe-area-inset-bottom))] sm:px-6 sm:pb-[calc(1.5rem+env(safe-area-inset-bottom))]"
    data-cookie-consent
    data-cookie-consent-auto-open="{{ $autoOpen ? 'true' : 'false' }}"
    :data-cookie-consent-status="savedConsent ?? 'unset'"
>
    <section
        x-ref="surface"
        class="pointer-events-auto mx-auto w-full max-w-5xl border border-ink/15 bg-canvas-bright px-4 py-4 sm:px-5"
        role="region"
        :aria-labelledby="showSettings ? 'cookie-consent-settings-title' : 'cookie-consent-title'"
        :aria-describedby="showSettings ? 'cookie-consent-settings-description' : 'cookie-consent-description'"
    >
        <div x-show="! showSettings" class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0 max-w-2xl">
                <h2 id="cookie-consent-title" class="font-sans text-sm font-bold text-ink">{{ __('legal.cookies.title') }}</h2>
                <p id="cookie-consent-description" class="mt-1 text-sm leading-6 text-ink-muted">
                    {{ __('legal.cookies.body') }}
                    <a href="{{ localized_route('cookies') }}" data-no-navigate class="ms-1 font-sans font-bold text-violet-700 underline decoration-violet-700/30 underline-offset-4 hover:decoration-violet-700">{{ __('legal.cookies.learn_more') }}</a>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                <button type="button" class="button-quiet min-h-11 px-4" @click="accept">{{ __('legal.cookies.accept') }}</button>
                <button type="button" class="button-quiet min-h-11 px-4" @click="reject">{{ __('legal.cookies.reject') }}</button>
                <button type="button" class="inline-flex min-h-11 items-center px-2 font-sans text-sm font-bold text-violet-700 underline decoration-violet-700/30 underline-offset-4 hover:decoration-violet-700" @click="openSettings">{{ __('legal.cookies.settings') }}</button>
            </div>
        </div>

        <div x-cloak x-show="showSettings" class="grid gap-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
            <div class="min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="cookie-consent-settings-title" class="font-sans text-sm font-bold text-ink">{{ __('legal.cookies.settings_title') }}</h2>
                        <p id="cookie-consent-settings-description" class="mt-1 text-sm leading-6 text-ink-muted">{{ __('legal.cookies.settings_body') }}</p>
                    </div>
                    <button type="button" class="font-sans text-sm font-bold text-violet-700 underline decoration-violet-700/30 underline-offset-4 hover:decoration-violet-700" @click="showChoices">{{ __('legal.cookies.back_to_choices') }}</button>
                </div>

                <div class="mt-4 grid gap-4 border-t border-ink/10 pt-4 sm:grid-cols-2">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-sans text-sm font-bold text-ink">{{ __('legal.cookies.necessary_title') }}</p>
                            <span class="border border-ink/15 px-2 py-1 font-sans text-xs font-bold text-ink-muted">{{ __('legal.cookies.always_on') }}</span>
                        </div>
                        <p class="mt-1 text-xs leading-5 text-ink-muted">{{ __('legal.cookies.necessary_body') }}</p>
                    </div>

                    <label class="flex cursor-pointer items-start gap-3">
                        <input x-model="analyticsEnabled" type="checkbox" class="mt-1 size-5 shrink-0 accent-violet-700">
                        <span>
                            <span class="block font-sans text-sm font-bold text-ink">{{ __('legal.cookies.analytics_title') }}</span>
                            <span class="mt-1 block text-xs leading-5 text-ink-muted">{{ __('legal.cookies.analytics_body') }}</span>
                        </span>
                    </label>
                </div>
            </div>

            <button type="button" class="button-quiet min-h-11 w-full px-4 sm:w-auto" @click="saveSettings">{{ __('legal.cookies.save_preferences') }}</button>
        </div>
    </section>
</div>
