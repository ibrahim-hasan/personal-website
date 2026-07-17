@props([
    'activeMenu' => false,
    'alternateUrls' => [],
])

@php
    $links = [
        ['label' => __('site.nav.home'), 'route' => 'home'],
        ['label' => __('site.nav.services'), 'route' => 'services'],
        ['label' => __('site.nav.work'), 'route' => 'work'],
        ['label' => __('site.nav.writing'), 'route' => 'writing'],
        ['label' => __('site.nav.about'), 'route' => 'about'],
    ];
@endphp

<header
    x-data="layout"
    @keydown.escape.window="close()"
    class="site-nav fixed inset-x-0 top-0 z-50"
    :class="show ? 'site-nav--open' : (scrolled || {{ $activeMenu ? 'true' : 'false' }} ? 'site-nav--scrolled' : '')"
>
    <div class="site-container flex h-20 items-center justify-between gap-6">
        <a href="{{ localized_route('home') }}" wire:navigate class="brand-mark" aria-label="{{ __('site.brand.home_aria') }}">
            <span class="brand-mark__name">{{ __('site.brand.name') }}</span>
            <span class="brand-mark__signal" aria-hidden="true"></span>
        </a>

        <nav class="site-nav__desktop-links" aria-label="{{ __('site.nav.main') }}">
            @foreach ($links as $link)
                @php
                    $patterns = $link['route'] === 'writing'
                        ? ['writing', 'writing.*', '*.writing', '*.writing.*']
                        : [$link['route'], '*.'.$link['route']];
                    $isActive = request()->routeIs(...$patterns);
                @endphp
                <a
                    href="{{ localized_route($link['route']) }}"
                    wire:navigate
                    class="nav-link {{ $isActive ? 'nav-link--active' : '' }}"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="site-nav__actions">
            <a href="{{ localized_route('contact') }}#consultation" wire:navigate class="site-nav__consultation button-primary" data-magnetic>
                <span>{{ __('site.actions.free_consultation') }}</span>
                <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
            </a>

            <div class="site-nav__utility-rail">
                <div class="language-switch" aria-label="{{ __('site.nav.languages') }}">
                    @foreach (config('app.supported_locales', []) as $locale => $language)
                        @continue(current_locale() === $locale)
                        <a href="{{ $alternateUrls[$locale] ?? localized_current_url($locale) }}" data-no-navigate class="language-switch__action" hreflang="{{ $locale }}" lang="{{ $locale }}">
                            <x-phosphor-translate class="size-4" aria-hidden="true" />
                            <span>{{ $language['native'] }}</span>
                        </a>
                    @endforeach
                </div>

                @guest
                    <a
                        href="{{ localized_route('reader.login') }}"
                        wire:navigate
                        class="site-nav__account-trigger"
                        aria-label="{{ __('reader_auth.sign_in') }}"
                    >
                        <x-phosphor-user-circle class="size-5" aria-hidden="true" />
                    </a>
                @else
                    <div
                        x-data="{ accountMenuOpen: false }"
                        @click.outside="accountMenuOpen = false"
                        @keydown.escape.window="accountMenuOpen = false"
                        class="site-nav__account"
                    >
                        <button
                            type="button"
                            @click="accountMenuOpen = ! accountMenuOpen"
                            :aria-expanded="accountMenuOpen"
                            aria-controls="reader-account-menu"
                            aria-label="{{ __('reader_auth.open_account_menu') }}"
                            class="site-nav__account-trigger site-nav__account-trigger--authenticated"
                        >
                            {{ auth()->user()->initials() }}
                        </button>

                        <div
                            x-cloak
                            x-show="accountMenuOpen"
                            x-transition:enter="transition duration-200 ease-out"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition duration-150 ease-in"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                            id="reader-account-menu"
                            class="site-nav__account-menu absolute end-0 top-[calc(100%+0.75rem)] z-50 w-72 border border-ink/15 bg-canvas-bright p-3"
                        >
                            <div class="border-b border-ink/10 px-3 pb-3">
                                <p class="truncate font-display text-base font-bold text-ink">{{ auth()->user()->name }}</p>
                                <p class="mt-1 truncate font-sans text-xs text-ink-muted" dir="ltr">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="grid gap-1 pt-2">
                                <a href="{{ localized_route('reader.library') }}" wire:navigate @click="accountMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-[var(--control-radius)] px-3 font-sans text-sm font-bold text-ink transition hover:bg-violet-100 hover:text-violet-800">
                                    <x-phosphor-bookmark-simple class="size-4" aria-hidden="true" />
                                    <span>{{ __('reader_auth.library_title') }}</span>
                                </a>
                                <a href="{{ localized_route('reader.account') }}" wire:navigate @click="accountMenuOpen = false" class="flex min-h-11 items-center gap-3 rounded-[var(--control-radius)] px-3 font-sans text-sm font-bold text-ink transition hover:bg-violet-100 hover:text-violet-800">
                                    <x-phosphor-user-circle class="size-4" aria-hidden="true" />
                                    <span>{{ __('reader_auth.account_settings') }}</span>
                                </a>
                                <form method="POST" action="{{ localized_route('reader.logout') }}">
                                    @csrf
                                    <button type="submit" class="flex min-h-11 w-full items-center gap-3 rounded-[var(--control-radius)] px-3 font-sans text-sm font-bold text-ink transition hover:bg-violet-100 hover:text-violet-800">
                                        <x-phosphor-sign-out class="size-4 rtl:rotate-180" aria-hidden="true" />
                                        <span>{{ __('reader_auth.logout') }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endguest
            </div>

            <button
                x-ref="menuToggle"
                type="button"
                @click="toggle()"
                class="menu-toggle"
                :aria-expanded="show"
                aria-controls="mobile-menu"
                aria-label="{{ __('site.nav.open_menu') }}"
                x-bind:aria-label="show ? @js(__('site.nav.close_menu')) : @js(__('site.nav.open_menu'))"
            >
                <x-phosphor-list class="h-5 w-5" x-show="!show" />
                <x-phosphor-x class="h-5 w-5" x-show="show" style="display: none;" />
            </button>
        </div>
    </div>

    <div
        x-ref="mobileMenu"
        x-show="show"
        @keydown.tab="trapFocus($event)"
        x-transition:enter="transition duration-500 ease-out"
        x-transition:enter-start="opacity-0 -translate-y-6"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition duration-300 ease-in"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-4"
        style="display: none;"
        id="mobile-menu"
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('site.nav.mobile') }}"
        class="mobile-menu fixed inset-x-0 bottom-0 top-20 z-40"
    >
        <nav class="site-container flex h-full flex-col justify-between gap-10 overflow-y-auto py-10" aria-label="{{ __('site.nav.mobile') }}">
            <div class="flex flex-col">
                @foreach ($links as $link)
                    @php
                        $patterns = $link['route'] === 'writing'
                            ? ['writing', 'writing.*', '*.writing', '*.writing.*']
                            : [$link['route'], '*.'.$link['route']];
                        $isActive = request()->routeIs(...$patterns);
                    @endphp
                    <a
                        href="{{ localized_route($link['route']) }}"
                        @click="close(false)"
                        wire:navigate
                        class="mobile-menu__link {{ $isActive ? 'mobile-menu__link--active' : '' }}"
                    >
                        <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                        <strong>{{ $link['label'] }}</strong>
                    </a>
                @endforeach
            </div>

            <div class="flex flex-col gap-6 border-t border-canvas/20 pt-6">
                @guest
                    <section class="border border-canvas/20 bg-canvas/5 p-5" aria-labelledby="mobile-reader-account-title">
                        <p class="font-sans text-xs font-bold uppercase tracking-[0.12em] text-violet-300">{{ __('reader_auth.community') }}</p>
                        <h2 id="mobile-reader-account-title" class="mt-2 font-display text-2xl font-bold text-canvas-bright">{{ __('reader_auth.reader_account') }}</h2>
                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <a href="{{ localized_route('reader.login') }}" wire:navigate @click="close(false)" class="button-light justify-center">{{ __('reader_auth.sign_in') }}</a>
                            <a href="{{ localized_route('reader.register') }}" wire:navigate @click="close(false)" class="inline-flex min-h-11 items-center justify-center rounded-[var(--control-radius)] border border-canvas/30 px-4 font-sans text-sm font-bold text-canvas-bright transition hover:bg-canvas/10">{{ __('reader_auth.create_account') }}</a>
                        </div>
                    </section>
                @else
                    <section class="border border-canvas/20 bg-canvas/5 p-5" aria-labelledby="mobile-reader-account-title">
                        <p class="font-sans text-xs font-bold uppercase tracking-[0.12em] text-violet-300">{{ __('reader_auth.reader_account') }}</p>
                        <h2 id="mobile-reader-account-title" class="mt-2 truncate font-display text-2xl font-bold text-canvas-bright">{{ auth()->user()->name }}</h2>
                        <p class="mt-1 truncate font-sans text-xs text-violet-200" dir="ltr">{{ auth()->user()->email }}</p>
                        <div class="mt-5 grid gap-2">
                            <a href="{{ localized_route('reader.library') }}" wire:navigate @click="close(false)" class="flex min-h-11 items-center justify-between rounded-[var(--control-radius)] border border-canvas/20 px-4 font-sans text-sm font-bold text-canvas-bright transition hover:bg-canvas/10">
                                <span>{{ __('reader_auth.library_title') }}</span>
                                <x-phosphor-bookmark-simple class="size-4" aria-hidden="true" />
                            </a>
                            <a href="{{ localized_route('reader.account') }}" wire:navigate @click="close(false)" class="flex min-h-11 items-center justify-between rounded-[var(--control-radius)] border border-canvas/20 px-4 font-sans text-sm font-bold text-canvas-bright transition hover:bg-canvas/10">
                                <span>{{ __('reader_auth.account_settings') }}</span>
                                <x-phosphor-user-circle class="size-4" aria-hidden="true" />
                            </a>
                            <form method="POST" action="{{ localized_route('reader.logout') }}">
                                @csrf
                                <button type="submit" @click="close(false)" class="flex min-h-11 w-full items-center justify-between rounded-[var(--control-radius)] border border-canvas/20 px-4 font-sans text-sm font-bold text-canvas-bright transition hover:bg-canvas/10">
                                    <span>{{ __('reader_auth.logout') }}</span>
                                    <x-phosphor-sign-out class="size-4 rtl:rotate-180" aria-hidden="true" />
                                </button>
                            </form>
                        </div>
                    </section>
                @endguest

                <a href="{{ localized_route('contact') }}#consultation" wire:navigate @click="close(false)" class="button-light w-full justify-between">
                    <span>{{ __('site.actions.free_consultation') }}</span>
                    <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" />
                </a>
                <div class="mobile-menu__language" aria-label="{{ __('site.nav.languages') }}">
                    @foreach (config('app.supported_locales', []) as $locale => $language)
                        @continue(current_locale() === $locale)
                        <a href="{{ $alternateUrls[$locale] ?? localized_current_url($locale) }}" data-no-navigate class="mobile-menu__locale" hreflang="{{ $locale }}" lang="{{ $locale }}">
                            <x-phosphor-translate class="size-4" aria-hidden="true" />
                            <span>{{ $language['native'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>
    </div>
</header>
