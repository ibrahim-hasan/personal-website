@props(['active'])

<nav
    class="reader-account-nav"
    aria-label="{{ __('reader_auth.account_navigation') }}"
>
    <div class="reader-account-nav__switcher">
        <a
            href="{{ localized_route('reader.library') }}"
            wire:navigate
            @class([
                'reader-account-nav__link',
                'is-active' => $active === 'library',
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
                'reader-account-nav__link',
                'is-active' => $active === 'account',
            ])
            @if ($active === 'account') aria-current="page" @endif
        >
            <x-phosphor-user-circle class="size-4" aria-hidden="true" />
            <span>{{ __('reader_auth.account_settings') }}</span>
        </a>
    </div>

    <form method="POST" action="{{ localized_route('reader.logout') }}" class="reader-account-nav__logout">
        @csrf
        <button type="submit" class="reader-account-nav__logout-button">
            <x-phosphor-sign-out class="size-4 rtl:rotate-180" aria-hidden="true" />
            <span>{{ __('reader_auth.logout') }}</span>
        </button>
    </form>
</nav>
