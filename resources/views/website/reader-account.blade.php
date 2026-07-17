<x-layouts.front
    :title="__('reader_auth.account_title')"
    :description="__('reader_auth.account_description')"
    robots="noindex, nofollow, noarchive, noimageindex"
>
    <section class="min-h-[75vh] bg-canvas pb-20 pt-32 sm:pb-24 sm:pt-36" aria-labelledby="reader-account-title">
        <div class="site-container">
            <header class="flex flex-col justify-between gap-8 border-b border-ink/15 pb-8 lg:flex-row lg:items-end">
                <div class="max-w-2xl">
                    <p class="signal-label">{{ __('reader_auth.reader_account') }}</p>
                    <h1 id="reader-account-title" class="mt-5 font-display text-4xl font-black leading-[1.08] text-ink sm:text-6xl">
                        {{ __('reader_auth.account_title') }}
                    </h1>
                    <p class="mt-4 text-base leading-8 text-ink-muted">{{ __('reader_auth.account_description') }}</p>
                </div>

                <x-reader-account-nav active="account" />
            </header>

            @if (session('status'))
                <p class="mt-6 border-s-2 border-violet-700 bg-violet-100 px-5 py-4 text-sm font-semibold leading-6 text-violet-900" role="status">
                    {{ session('status') }}
                </p>
            @endif

            <div class="mt-10 grid items-start gap-8 lg:grid-cols-[minmax(17rem,0.72fr)_minmax(0,1.28fr)] lg:gap-12">
                <aside class="grid gap-6 lg:sticky lg:top-28" aria-labelledby="reader-account-summary-title">
                    <section class="border border-violet-700/25 bg-violet-50 p-6 shadow-[0.6rem_0.6rem_0_rgba(109,70,146,0.11)] sm:p-8">
                        <p class="signal-label">{{ __('reader_auth.account_summary') }}</p>
                        <h2 id="reader-account-summary-title" class="mt-4 font-display text-2xl font-black leading-tight text-ink">
                            {{ $reader->name }}
                        </h2>
                        <p class="mt-2 break-all font-sans text-sm text-ink-muted" dir="ltr">{{ $reader->email }}</p>
                        <p class="mt-5 text-sm leading-7 text-ink-muted">{{ __('reader_auth.account_summary_description') }}</p>

                        <dl class="mt-6 grid grid-cols-3 border border-violet-700/20 bg-canvas-bright">
                            <div class="p-3 text-center sm:p-4">
                                <dt class="font-sans text-[0.65rem] font-bold leading-5 text-ink-muted">{{ __('reader_auth.saved_articles') }}</dt>
                                <dd class="mt-1 font-display text-2xl font-black text-ink">{{ $stats['bookmarks'] }}</dd>
                            </div>
                            <div class="border-x border-violet-700/15 p-3 text-center sm:p-4">
                                <dt class="font-sans text-[0.65rem] font-bold leading-5 text-ink-muted">{{ __('reader_auth.reading_activity') }}</dt>
                                <dd class="mt-1 font-display text-2xl font-black text-ink">{{ $stats['progress'] }}</dd>
                            </div>
                            <div class="p-3 text-center sm:p-4">
                                <dt class="font-sans text-[0.65rem] font-bold leading-5 text-ink-muted">{{ __('reader_auth.contributions') }}</dt>
                                <dd class="mt-1 font-display text-2xl font-black text-ink">{{ $stats['comments'] }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section class="border border-ink/15 bg-canvas-bright p-6 sm:p-8" aria-labelledby="verification-status-title">
                        <p class="font-sans text-xs font-bold uppercase tracking-[0.12em] text-violet-700">{{ __('reader_auth.verification_status') }}</p>
                        <div class="mt-3 flex items-center gap-3">
                            @if ($reader->hasVerifiedEmail())
                                <span class="grid size-9 place-items-center rounded-full bg-violet-700 text-white" aria-hidden="true">
                                    <x-phosphor-check class="size-4" />
                                </span>
                                <h2 id="verification-status-title" class="font-display text-xl font-bold text-ink">{{ __('reader_auth.verified') }}</h2>
                            @else
                                <span class="grid size-9 place-items-center rounded-full border border-violet-700/30 bg-violet-100 text-violet-800" aria-hidden="true">
                                    <x-phosphor-envelope-simple class="size-4" />
                                </span>
                                <h2 id="verification-status-title" class="font-display text-xl font-bold text-ink">{{ __('reader_auth.unverified') }}</h2>
                            @endif
                        </div>

                        @unless ($reader->hasVerifiedEmail())
                            <p class="mt-4 text-sm leading-7 text-ink-muted">{{ __('reader_auth.verification_help') }}</p>
                            <form method="POST" action="{{ localized_route('verification.send') }}" class="mt-5">
                                @csrf
                                <button type="submit" class="button-quiet w-full justify-center">{{ __('reader_auth.resend_verification') }}</button>
                            </form>
                        @endunless
                    </section>
                </aside>

                <div class="grid gap-8">
                    <section class="border border-ink/15 bg-canvas-bright p-6 sm:p-8 lg:p-10" aria-labelledby="reader-profile-title">
                        <p class="signal-label">{{ __('reader_auth.profile_title') }}</p>
                        <h2 id="reader-profile-title" class="mt-4 font-display text-3xl font-black leading-tight text-ink">{{ __('reader_auth.profile_title') }}</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-muted sm:text-base">{{ __('reader_auth.profile_description') }}</p>

                        <form method="POST" action="{{ localized_route('reader.account.update') }}" class="mt-8 grid gap-5">
                            @csrf
                            @method('PATCH')

                            <label class="block">
                                <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.name') }}</span>
                                <input name="name" type="text" autocomplete="name" required value="{{ old('name', $reader->name) }}" class="min-h-13 w-full rounded-[var(--control-radius)] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50">
                                @error('name', 'profile') <span class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.email') }}</span>
                                <input name="email" type="email" autocomplete="email" required value="{{ old('email', $reader->email) }}" class="min-h-13 w-full rounded-[var(--control-radius)] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50" dir="ltr">
                                @error('email', 'profile') <span class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                            </label>

                            <label class="block">
                                <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.current_password') }}</span>
                                <input name="current_password" type="password" autocomplete="current-password" aria-describedby="profile-current-password-hint" class="min-h-13 w-full rounded-[var(--control-radius)] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50">
                                <span id="profile-current-password-hint" class="mt-2 block text-xs leading-6 text-ink-muted">{{ __('reader_auth.current_password_email_hint') }}</span>
                                @error('current_password', 'profile') <span class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                            </label>

                            <button type="submit" class="button-primary mt-2 w-full justify-center sm:w-fit">{{ __('reader_auth.save_profile') }}</button>
                        </form>
                    </section>

                    <section class="border border-ink/15 bg-canvas-bright p-6 sm:p-8 lg:p-10" aria-labelledby="reader-security-title">
                        <p class="signal-label">{{ __('reader_auth.security_title') }}</p>
                        <h2 id="reader-security-title" class="mt-4 font-display text-3xl font-black leading-tight text-ink">{{ __('reader_auth.security_title') }}</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-muted sm:text-base">{{ __('reader_auth.security_description') }}</p>

                        <form method="POST" action="{{ localized_route('reader.account.password.update') }}" class="mt-8 grid gap-5">
                            @csrf
                            @method('PUT')

                            <label class="block">
                                <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.current_password') }}</span>
                                <input name="current_password" type="password" autocomplete="current-password" required class="min-h-13 w-full rounded-[var(--control-radius)] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50">
                                @error('current_password', 'password') <span class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                            </label>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <label class="block">
                                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.new_password') }}</span>
                                    <input name="password" type="password" autocomplete="new-password" required class="min-h-13 w-full rounded-[var(--control-radius)] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50">
                                    @error('password', 'password') <span class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.password_confirmation') }}</span>
                                    <input name="password_confirmation" type="password" autocomplete="new-password" required class="min-h-13 w-full rounded-[var(--control-radius)] border border-ink/20 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-violet-600 focus-visible:bg-violet-50">
                                </label>
                            </div>

                            <button type="submit" class="button-primary mt-2 w-full justify-center sm:w-fit">{{ __('reader_auth.change_password') }}</button>
                        </form>
                    </section>

                    <section class="border border-danger/35 bg-canvas-bright p-6 sm:p-8 lg:p-10" aria-labelledby="reader-delete-title">
                        <p class="font-sans text-xs font-black uppercase tracking-[0.12em] text-danger">{{ __('reader_auth.danger_zone') }}</p>
                        <h2 id="reader-delete-title" class="mt-4 font-display text-3xl font-black leading-tight text-ink">{{ __('reader_auth.delete_title') }}</h2>

                        @if ($canDeleteAccount)
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-ink-muted sm:text-base">{{ __('reader_auth.delete_description') }}</p>
                            <p id="reader-delete-contributions-note" class="mt-4 border-s-2 border-danger/55 bg-canvas px-4 py-3 text-sm font-semibold leading-7 text-ink-soft">
                                {{ __('reader_auth.delete_contributions_note') }}
                            </p>

                            <form method="POST" action="{{ localized_route('reader.account.destroy') }}" class="mt-8 grid gap-5">
                                @csrf
                                @method('DELETE')

                                <label class="block">
                                    <span class="mb-2 block font-sans text-sm font-bold text-ink-soft">{{ __('reader_auth.current_password') }}</span>
                                    <input name="current_password" type="password" autocomplete="current-password" required aria-describedby="reader-delete-contributions-note" class="min-h-13 w-full rounded-[var(--control-radius)] border border-danger/35 bg-canvas px-4 py-3 text-ink outline-none transition-colors focus-visible:border-danger focus-visible:bg-violet-50">
                                    @error('current_password', 'accountDeletion') <span class="mt-2 block text-sm text-danger" role="alert">{{ $message }}</span> @enderror
                                </label>

                                <label class="flex min-h-11 items-start gap-3 text-sm font-semibold leading-7 text-ink-soft">
                                    <input name="acknowledgement" type="checkbox" value="1" required class="mt-1 size-5 shrink-0 rounded-[var(--control-radius)] border-ink/25 text-danger focus:ring-danger">
                                    <span>{{ __('reader_auth.delete_acknowledgement') }}</span>
                                </label>
                                @error('acknowledgement', 'accountDeletion') <span class="text-sm text-danger" role="alert">{{ $message }}</span> @enderror

                                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-[var(--control-radius)] border border-danger bg-danger px-5 py-3 font-sans text-sm font-black text-white transition hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-danger focus-visible:ring-offset-2 focus-visible:ring-offset-canvas sm:w-fit">
                                    {{ __('reader_auth.delete_account') }}
                                </button>
                            </form>
                        @else
                            <div class="mt-6 border border-violet-700/20 bg-violet-50 p-5 sm:p-6">
                                <h3 class="font-display text-xl font-bold text-ink">{{ __('reader_auth.account_protected_title') }}</h3>
                                <p class="mt-2 text-sm leading-7 text-ink-muted">{{ __('reader_auth.account_protected_body') }}</p>
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </section>
</x-layouts.front>
