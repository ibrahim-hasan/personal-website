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

        <nav class="hidden items-center gap-7 lg:flex" aria-label="{{ __('site.nav.main') }}">
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

        <div class="flex items-center gap-2 sm:gap-3">
            <div class="language-switch hidden items-center md:flex" aria-label="{{ __('site.nav.languages') }}">
                @foreach (config('app.supported_locales', []) as $locale => $language)
                    <a href="{{ $alternateUrls[$locale] ?? localized_current_url($locale) }}" data-no-navigate class="language-switch__item {{ current_locale() === $locale ? 'language-switch__item--active' : '' }}">
                        {{ strtoupper($language['code']) }}
                    </a>
                @endforeach
            </div>

            <a href="{{ localized_route('contact') }}#consultation" wire:navigate class="site-nav__consultation button-primary" data-magnetic>
                <span>{{ __('site.actions.free_consultation') }}</span>
                <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
            </a>

            <button
                x-ref="menuToggle"
                type="button"
                @click="toggle()"
                class="menu-toggle lg:hidden"
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
        class="mobile-menu fixed inset-x-0 bottom-0 top-20 z-40 lg:hidden"
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
                <a href="{{ localized_route('contact') }}#consultation" wire:navigate @click="close(false)" class="button-light w-full justify-between">
                    <span>{{ __('site.actions.free_consultation') }}</span>
                    <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" />
                </a>
                <div class="flex gap-3" aria-label="{{ __('site.nav.languages') }}">
                    @foreach (config('app.supported_locales', []) as $locale => $language)
                        <a href="{{ $alternateUrls[$locale] ?? localized_current_url($locale) }}" data-no-navigate class="mobile-menu__locale {{ current_locale() === $locale ? 'mobile-menu__locale--active' : '' }}">
                            {{ strtoupper($language['code']) }}
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>
    </div>
</header>
