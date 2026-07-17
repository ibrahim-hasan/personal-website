<x-layouts.front :title="__('reader_auth.register_title')" :description="__('reader_auth.register_description')" robots="noindex, follow, noarchive">
    <section class="mx-auto flex min-h-[75vh] w-full max-w-xl items-center px-6 pb-20 pt-32 sm:px-10 sm:pt-36" aria-labelledby="reader-register-title">
        <div class="relative w-full border border-violet-700/25 bg-canvas-bright p-7 shadow-[0.75rem_0.75rem_0_rgba(109,70,146,0.14)] sm:p-10">
            <span class="absolute inset-x-0 top-0 h-1 bg-violet-600" aria-hidden="true"></span>
            <p class="signal-label">{{ __('reader_auth.community') }}</p>
            <h1 id="reader-register-title" class="mt-4 font-display text-3xl font-black leading-[1.12] text-ink sm:text-4xl">{{ __('reader_auth.register_title') }}</h1>
            <p class="mt-4 text-base leading-7 text-ink-muted">{{ __('reader_auth.register_description') }}</p>

            <form method="POST" action="{{ localized_route('reader.register.store') }}" class="mt-8 space-y-5">
                @csrf
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.name') }}</span>
                    <input name="name" type="text" autocomplete="name" required value="{{ old('name') }}" class="min-h-13 w-full rounded-[var(--control-radius)] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50">
                    @error('name') <span class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.email') }}</span>
                    <input name="email" type="email" autocomplete="email" required value="{{ old('email') }}" class="min-h-13 w-full rounded-[var(--control-radius)] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50">
                    @error('email') <span class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.password') }}</span>
                    <input name="password" type="password" autocomplete="new-password" required class="min-h-13 w-full rounded-[var(--control-radius)] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50">
                    @error('password') <span class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.password_confirmation') }}</span>
                    <input name="password_confirmation" type="password" autocomplete="new-password" required class="min-h-13 w-full rounded-[var(--control-radius)] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50">
                </label>
                <button class="button-primary w-full" type="submit">{{ __('reader_auth.create_account') }}</button>
            </form>

            <p class="mt-7 text-center text-sm leading-6 text-ink-muted">
                {{ __('reader_auth.have_account') }}
                <a href="{{ localized_route('reader.login') }}" class="text-link ms-1 inline-flex">{{ __('reader_auth.login') }}</a>
            </p>
        </div>
    </section>
</x-layouts.front>
