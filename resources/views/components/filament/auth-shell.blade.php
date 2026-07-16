@props([
    'heading',
    'description',
])

<div {{ $attributes->class(['fi-auth-shell']) }}>
    <section class="fi-auth-shell__context" aria-labelledby="admin-auth-context-title">
        <div class="fi-auth-shell__context-content">
            @include('filament.partials.auth-brand-logo')

            <h1 id="admin-auth-context-title" class="fi-auth-shell__title">{{ __('admin.brand.title') }}</h1>
            <p class="fi-auth-shell__subtitle">{{ __('admin.brand.subtitle') }}</p>

            <a class="fi-auth-shell__website-link" href="{{ route('home') }}">
                <span>{{ __('admin.auth.open_website') }}</span>
                <x-filament::icon icon="heroicon-o-arrow-up-right" class="h-4 w-4 rtl:-scale-x-100" />
            </a>
        </div>
    </section>

    <section class="fi-auth-shell__form-panel" aria-labelledby="admin-auth-form-title">
        <div class="fi-auth-shell__form-frame">
            <h2 id="admin-auth-form-title" class="fi-auth-shell__heading">{{ $heading }}</h2>
            <p class="fi-auth-shell__subtext">{{ $description }}</p>

            <div class="fi-auth-shell__card">
                {{ $slot }}
            </div>
        </div>
    </section>
</div>
