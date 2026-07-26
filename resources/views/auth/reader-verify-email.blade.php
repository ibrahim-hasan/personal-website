<x-layouts.front :title="__('reader_auth.verify_title')" :description="__('reader_auth.verify_description')" robots="noindex, nofollow, noarchive">
    <section class="mx-auto flex min-h-[75vh] w-full max-w-xl items-center px-6 pb-20 pt-32 sm:px-10 sm:pt-36" aria-labelledby="verify-email-title">
        <div class="reader-auth-card relative w-full p-7 text-center sm:p-10">
            <span class="absolute inset-x-0 top-0 h-1 bg-violet-600" aria-hidden="true"></span>
            <div class="reader-icon-surface mx-auto grid size-14 place-items-center" aria-hidden="true">
                <x-phosphor-envelope-simple class="size-7" />
            </div>
            <h1 id="verify-email-title" class="mt-6 font-display text-3xl font-black leading-[1.12] text-ink">{{ __('reader_auth.verify_title') }}</h1>
            <p class="mt-4 text-base leading-7 text-ink-muted">{{ __('reader_auth.verify_description') }}</p>
            @if (session('status') === 'verification-link-sent')
                <p class="mt-5 border border-success/25 bg-success/10 px-4 py-3 text-sm font-bold text-ink" role="status">{{ __('reader_auth.verification_sent') }}</p>
            @endif
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <form method="POST" action="{{ localized_route('verification.send') }}">
                    @csrf
                    <button class="button-primary w-full sm:w-auto" type="submit">{{ __('reader_auth.resend') }}</button>
                </form>
                <form method="POST" action="{{ localized_route('reader.logout') }}">
                    @csrf
                    <button class="button-quiet w-full sm:w-auto" type="submit">{{ __('reader_auth.logout') }}</button>
                </form>
            </div>
        </div>
    </section>
</x-layouts.front>
