<div
    @class([
        'fi-auth-shell grid grid-cols-1 relative',
        'text-right' => app()->getLocale() === 'ar',
    ])
>
    <img
        src="{{ asset('images/objects/manage-layers-section-bg.svg') }}"
        class="absolute bottom-0 left-0 w-full h-full object-cover opacity-70 dark:opacity-30 z-0"
        alt=""
        aria-hidden="true"
    />

    <section class="fi-auth-shell__section px-6 py-8 sm:px-10 sm:py-10 flex items-center justify-center flex-col relative z-10">
        <div class="max-w-3xl mb-6">
            @include('filament.partials.auth-brand-logo')
            <h2 class="fi-auth-shell__title">
                {{ __('admin.brand.title') }}
            </h2>
        </div>
        <div class="w-full max-w-md">
            <div class="fi-auth-shell__card">
                <h2 class="fi-auth-shell__heading !mb-2">{{ __('Reset your password') }}</h2>
                <p class="fi-auth-shell__subtext mb-6">{{ __('Choose a strong new password to secure your admin account.') }}</p>

                {{ $this->content }}
            </div>
        </div>
    </section>
</div>
