<x-layouts.front :title="__('reader_auth.accept_terms_title')" :description="__('reader_auth.accept_terms_description')" robots="noindex, follow, noarchive">
    <section class="mx-auto flex min-h-[75vh] w-full max-w-xl items-center px-6 pb-20 pt-32 sm:px-10 sm:pt-36" aria-labelledby="reader-terms-acceptance-title">
        <div class="relative w-full border border-violet-700/25 bg-canvas-bright p-7 shadow-[0.75rem_0.75rem_0_rgba(109,70,146,0.14)] sm:p-10">
            <span class="absolute inset-x-0 top-0 h-1 bg-violet-600" aria-hidden="true"></span>
            <p class="signal-label">{{ __('legal.documents.terms') }}</p>
            <h1 id="reader-terms-acceptance-title" class="mt-4 font-display text-3xl font-black leading-[1.12] text-ink sm:text-4xl">{{ __('reader_auth.accept_terms_title') }}</h1>
            <p class="mt-4 text-base leading-7 text-ink-muted">{{ __('reader_auth.accept_terms_description') }}</p>

            <div class="mt-7 border-y border-ink/10 py-5 text-sm leading-6 text-ink-muted">
                <a href="{{ localized_route('terms') }}" data-no-navigate class="text-link">{{ __('legal.documents.terms') }}</a>
                <span class="px-2" aria-hidden="true">·</span>
                <a href="{{ localized_route('privacy') }}" data-no-navigate class="text-link">{{ __('legal.documents.privacy') }}</a>
            </div>

            <form method="POST" action="{{ localized_route('reader.terms.acceptance.store') }}" class="mt-7 space-y-5">
                @csrf
                <label class="flex items-start gap-3 text-sm leading-6 text-ink-muted">
                    <input
                        name="terms_accepted"
                        type="checkbox"
                        value="1"
                        required
                        @checked(old('terms_accepted'))
                        class="mt-1 size-5 shrink-0 accent-violet-700"
                    >
                    <span>
                        {!! __('reader_auth.accept_current_terms', [
                            'terms' => '<a class="text-link" data-no-navigate href="'.e(localized_route('terms')).'">'.e(__('legal.documents.terms')).'</a>',
                        ]) !!}
                    </span>
                </label>
                @error('terms_accepted') <span class="-mt-3 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                <button class="button-primary w-full" type="submit">{{ __('reader_auth.accept_terms_continue') }}</button>
            </form>
        </div>
    </section>
</x-layouts.front>
