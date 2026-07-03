@php
$current = app()->getLocale();
@endphp

<div class="flex items-center gap-2 me-2">
    @if ($current !== 'ar')
    <form method="POST" action="{{ locale_switch_url('ar') }}">
        @csrf
        <button type="submit" class="fi-btn fi-btn-size-sm fi-btn-color-gray">
            <span class="fi-btn-label">{{ __('admin.locales.ar') }}</span>
        </button>
    </form>
    @endif

    @if ($current !== 'en')
    <form method="POST" action="{{ locale_switch_url('en') }}">
        @csrf
        <button type="submit" class="fi-btn fi-btn-size-sm fi-btn-color-gray">
            <span class="fi-btn-label">{{ __('admin.locales.en') }}</span>
        </button>
    </form>
    @endif

    <div class="flex items-center me-2">
        <x-filament::button
            tag="a"
            :href="localized_route('home')"
            target="_blank"
            rel="noopener noreferrer"
            size="sm"
            color="yellow"
            outlined
        >
            {{ __('admin.auth.open_website') }}
        </x-filament::button>
    </div>
</div>
