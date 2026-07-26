<x-layouts.front :title="__('reader_auth.register_title')" :description="__('reader_auth.register_description')" robots="noindex, follow, noarchive">
    <section class="mx-auto flex min-h-[75vh] w-full max-w-xl items-center px-6 pb-20 pt-32 sm:px-10 sm:pt-36" aria-labelledby="reader-register-title">
        <div class="reader-auth-card relative w-full p-7 sm:p-10">
            <span class="absolute inset-x-0 top-0 h-1 bg-violet-600" aria-hidden="true"></span>
            <p class="signal-label">{{ __('reader_auth.community') }}</p>
            <h1 id="reader-register-title" class="mt-4 font-display text-3xl font-black leading-[1.12] text-ink sm:text-4xl">{{ __('reader_auth.register_title') }}</h1>
            <p class="mt-4 text-base leading-7 text-ink-muted">{{ __('reader_auth.register_description') }}</p>

            <form method="POST" action="{{ localized_route('reader.register.store') }}" class="mt-8 space-y-5">
                @csrf
                <x-reader-validation-summary
                    id="reader-register-error-summary"
                    :fields="[
                        'name' => 'reader-register-name',
                        'email' => 'reader-register-email',
                        'password' => 'reader-register-password',
                        'terms_accepted' => 'reader-register-terms',
                        'cf-turnstile-response' => 'reader-register-turnstile',
                    ]"
                />
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.name') }}</span>
                    <input id="reader-register-name" name="name" type="text" autocomplete="name" required value="{{ old('name') }}" class="reader-form-control min-h-13 w-full rounded-[var(--control-radius)] px-4 py-3" @error('name') aria-invalid="true" aria-describedby="reader-register-name-error" @enderror>
                    @error('name') <span id="reader-register-name-error" class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.email') }}</span>
                    <input id="reader-register-email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" class="reader-form-control min-h-13 w-full rounded-[var(--control-radius)] px-4 py-3" @error('email') aria-invalid="true" aria-describedby="reader-register-email-error" @enderror>
                    @error('email') <span id="reader-register-email-error" class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.password') }}</span>
                    <input id="reader-register-password" name="password" type="password" autocomplete="new-password" required aria-describedby="reader-register-password-guidance{{ $errors->has('password') ? ' reader-register-password-error' : '' }}" class="reader-form-control min-h-13 w-full rounded-[var(--control-radius)] px-4 py-3" @error('password') aria-invalid="true" @enderror>
                    <span id="reader-register-password-guidance" class="mt-2 block text-xs leading-6 text-ink-muted">{{ __('reader_auth.password_guidance') }}</span>
                    @error('password') <span id="reader-register-password-error" class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.password_confirmation') }}</span>
                    <input id="reader-register-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="reader-form-control min-h-13 w-full rounded-[var(--control-radius)] px-4 py-3">
                </label>
                <label class="flex items-start gap-3 text-sm leading-6 text-ink-muted">
                    <input
                        id="reader-register-terms"
                        name="terms_accepted"
                        type="checkbox"
                        value="1"
                        required
                        @checked(old('terms_accepted'))
                        class="reader-checkbox mt-1 size-5 shrink-0"
                        @error('terms_accepted') aria-invalid="true" aria-describedby="reader-register-terms-error" @enderror
                    >
                    <span>
                        {!! __('reader_auth.terms_acceptance', [
                            'terms' => '<a class="text-link" data-no-navigate href="'.e(localized_route('terms')).'">'.e(__('legal.documents.terms')).'</a>',
                        ]) !!}
                    </span>
                </label>
                @error('terms_accepted') <span id="reader-register-terms-error" class="-mt-3 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                <p class="-mt-2 text-xs leading-5 text-ink-muted">
                    {!! __('reader_auth.privacy_notice', [
                        'privacy' => '<a class="text-link" data-no-navigate href="'.e(localized_route('privacy')).'">'.e(__('legal.documents.privacy')).'</a>',
                    ]) !!}
                </p>
                <div id="reader-register-turnstile">
                    <x-turnstile.widget class="flex justify-center" />
                </div>
                @error('cf-turnstile-response') <span id="reader-register-turnstile-error" class="-mt-3 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                <button class="button-primary w-full" type="submit">{{ __('reader_auth.create_account') }}</button>
            </form>

            <p class="mt-7 text-center text-sm leading-6 text-ink-muted">
                {{ __('reader_auth.have_account') }}
                <a href="{{ localized_route('reader.login') }}" class="text-link ms-1 inline-flex">{{ __('reader_auth.login') }}</a>
            </p>
        </div>
    </section>
</x-layouts.front>
