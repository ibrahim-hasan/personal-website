@props(['active'])

<nav
    class="flex flex-wrap items-center gap-2"
    aria-label="{{ __('reader_auth.account_navigation') }}"
>
    <a
        href="{{ localized_route('reader.library') }}"
        wire:navigate
        @class([
            'inline-flex min-h-11 items-center gap-2 border px-4 font-sans text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-700 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas',
            'border-violet-700 bg-violet-700 text-white' => $active === 'library',
            'border-ink/20 bg-canvas-bright text-ink hover:border-violet-600 hover:text-violet-800' => $active !== 'library',
        ])
        @if ($active === 'library') aria-current="page" @endif
    >
        <x-phosphor-bookmark-simple class="size-4" aria-hidden="true" />
        <span>{{ __('reader_auth.library_title') }}</span>
    </a>

    <a
        href="{{ localized_route('reader.account') }}"
        wire:navigate
        @class([
            'inline-flex min-h-11 items-center gap-2 border px-4 font-sans text-sm font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-700 focus-visible:ring-offset-2 focus-visible:ring-offset-canvas',
            'border-violet-700 bg-violet-700 text-white' => $active === 'account',
            'border-ink/20 bg-canvas-bright text-ink hover:border-violet-600 hover:text-violet-800' => $active !== 'account',
        ])
        @if ($active === 'account') aria-current="page" @endif
    >
        <x-phosphor-user-circle class="size-4" aria-hidden="true" />
        <span>{{ __('reader_auth.account_settings') }}</span>
    </a>

    <form method="POST" action="{{ localized_route('reader.logout') }}">
        @csrf
        <button type="submit" class="button-quiet min-h-11">
            <x-phosphor-sign-out class="size-4 rtl:rotate-180" aria-hidden="true" />
            <span>{{ __('reader_auth.logout') }}</span>
        </button>
    </form>
</nav>
