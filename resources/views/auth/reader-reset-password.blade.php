<x-layouts.front :title="__('reader_auth.reset_title')" :description="__('reader_auth.reset_description')" robots="noindex, follow, noarchive">
    <section class="mx-auto flex min-h-[75vh] w-full max-w-xl items-center px-6 pb-20 pt-32 sm:px-10 sm:pt-36" aria-labelledby="reader-reset-title">
        <div class="reader-auth-card relative w-full p-7 sm:p-10">
            <span class="absolute inset-x-0 top-0 h-1 bg-violet-600" aria-hidden="true"></span>
            <p class="signal-label">{{ __('reader_auth.community') }}</p>
            <h1 id="reader-reset-title" class="mt-4 font-display text-3xl font-black leading-[1.12] text-ink sm:text-4xl">{{ __('reader_auth.reset_title') }}</h1>
            <p class="mt-4 text-base leading-7 text-ink-muted">{{ __('reader_auth.reset_description') }}</p>

            <form method="POST" action="{{ localized_route('reader.password.update') }}" class="mt-8 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <x-reader-validation-summary
                    id="reader-reset-password-error-summary"
                    :fields="[
                        'email' => 'reader-reset-password-email',
                        'password' => 'reader-reset-password-password',
                    ]"
                />
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.email') }}</span>
                    <input id="reader-reset-password-email" name="email" type="email" autocomplete="email" required value="{{ old('email', $email) }}" class="reader-form-control min-h-13 w-full rounded-[var(--control-radius)] px-4 py-3" dir="ltr" @error('email') aria-invalid="true" aria-describedby="reader-reset-password-email-error" @enderror>
                    @error('email') <span id="reader-reset-password-email-error" class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.new_password') }}</span>
                    <input id="reader-reset-password-password" name="password" type="password" autocomplete="new-password" required aria-describedby="reader-reset-password-password-guidance{{ $errors->has('password') ? ' reader-reset-password-password-error' : '' }}" class="reader-form-control min-h-13 w-full rounded-[var(--control-radius)] px-4 py-3" @error('password') aria-invalid="true" @enderror>
                    <span id="reader-reset-password-password-guidance" class="mt-2 block text-xs leading-6 text-ink-muted">{{ __('reader_auth.password_guidance') }}</span>
                    @error('password') <span id="reader-reset-password-password-error" class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.password_confirmation') }}</span>
                    <input id="reader-reset-password-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="reader-form-control min-h-13 w-full rounded-[var(--control-radius)] px-4 py-3">
                </label>
                <button class="button-primary w-full" type="submit">{{ __('reader_auth.reset_password') }}</button>
            </form>
        </div>
    </section>
</x-layouts.front>
