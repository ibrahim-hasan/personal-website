<x-layouts.front :title="__('reader_auth.login_title')" :description="__('reader_auth.login_description')" robots="noindex, follow, noarchive">
    <section class="mx-auto flex min-h-[75vh] w-full max-w-xl items-center px-6 pb-20 pt-32 sm:px-10 sm:pt-36" aria-labelledby="reader-login-title">
        <div class="reader-auth-card relative w-full p-7 sm:p-10">
            <span class="absolute inset-x-0 top-0 h-1 bg-violet-600" aria-hidden="true"></span>
            <p class="signal-label">{{ __('reader_auth.community') }}</p>
            <h1 id="reader-login-title" class="mt-4 font-display text-3xl font-black leading-[1.12] text-ink sm:text-4xl">{{ __('reader_auth.login_title') }}</h1>
            <p class="mt-4 text-base leading-7 text-ink-muted">{{ __('reader_auth.login_description') }}</p>

            @if (session('status'))
                <p class="mt-5 border border-success/25 bg-success/10 px-4 py-3 text-sm font-bold text-ink" role="status">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ localized_route('reader.login.store') }}" class="mt-8 space-y-5">
                @csrf
                <x-reader-validation-summary
                    id="reader-login-error-summary"
                    :fields="[
                        'email' => 'reader-login-email',
                        'password' => 'reader-login-password',
                        'cf-turnstile-response' => 'reader-login-turnstile',
                    ]"
                />
                <label class="block">
                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.email') }}</span>
                    <input id="reader-login-email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" class="reader-form-control min-h-13 w-full rounded-[var(--control-radius)] px-4 py-3" @error('email') aria-invalid="true" aria-describedby="reader-login-email-error" @enderror>
                    @error('email') <span id="reader-login-email-error" class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                </label>
                <div class="block">
                    <div class="mb-2 flex items-center justify-between gap-3 font-sans text-sm font-bold text-ink-soft">
                        <label for="reader-login-password">{{ __('reader_auth.password') }}</label>
                        <a href="{{ localized_route('reader.password.request') }}" class="text-link inline-flex min-h-11 items-center px-1">{{ __('reader_auth.forgot_password') }}</a>
                    </div>
                    <input id="reader-login-password" name="password" type="password" autocomplete="current-password" required class="reader-form-control min-h-13 w-full rounded-[var(--control-radius)] px-4 py-3" @error('password') aria-invalid="true" aria-describedby="reader-login-password-error" @enderror>
                    @error('password') <span id="reader-login-password-error" class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                </div>
                <label class="flex min-h-11 items-center gap-3 font-sans text-sm text-ink-soft">
                    <input name="remember" type="checkbox" value="1" class="reader-checkbox size-5 rounded-[var(--control-radius)] border-ink/25">
                    <span>{{ __('reader_auth.remember') }}</span>
                </label>
                <div id="reader-login-turnstile">
                    <x-turnstile.widget class="flex justify-center" />
                </div>
                @error('cf-turnstile-response') <span id="reader-login-turnstile-error" class="-mt-3 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                <button class="button-primary w-full" type="submit">{{ __('reader_auth.login') }}</button>
            </form>

            <p class="mt-7 text-center text-sm leading-6 text-ink-muted">
                {{ __('reader_auth.no_account') }}
                <a href="{{ localized_route('reader.register') }}" class="text-link ms-1 inline-flex">{{ __('reader_auth.register') }}</a>
            </p>
        </div>
    </section>
</x-layouts.front>
