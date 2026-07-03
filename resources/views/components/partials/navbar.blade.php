@props([
    'activeMenu' => false,
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
    class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
    :class="scrolled || {{ $activeMenu ? 'true' : 'false' }} ? 'bg-graphite-950/90 shadow-[0_16px_40px_rgba(8,13,16,0.18)] backdrop-blur-xl' : 'bg-transparent'"
>
    <div class="site-container flex h-[76px] items-center justify-between">
        <a href="{{ localized_route('home') }}" class="group flex items-center gap-3" aria-label="{{ __('site.brand.home_aria') }}">
            <span class="grid h-10 w-10 place-items-center rounded-md bg-white text-sm font-extrabold text-graphite-950 shadow-sm transition group-hover:bg-emerald-200">
                {{ __('site.brand.initials') }}
            </span>
            <span class="hidden leading-tight sm:block">
                <span class="block text-sm font-bold text-white">{{ __('site.brand.name') }}</span>
                <span class="block text-xs font-medium text-white/62">{{ __('site.brand.tagline') }}</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('site.nav.main') }}">
            @foreach ($links as $link)
                <a
                    href="{{ localized_route($link['route']) }}"
                    class="rounded-md px-4 py-2 text-sm font-semibold transition {{ request()->routeIs($link['route']) ? 'bg-white text-graphite-950' : 'text-white/76 hover:bg-white/10 hover:text-white' }}"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <div class="hidden items-center gap-1 rounded-md border border-white/12 bg-white/8 p-1 text-xs font-bold text-white/70 md:flex" aria-label="{{ __('site.nav.languages') }}">
                @foreach (array_keys(config('app.supported_locales', [])) as $locale)
                    <a href="{{ localized_current_url($locale) }}" class="rounded px-2 py-1 transition {{ current_locale() === $locale ? 'bg-white text-graphite-950' : 'hover:bg-white/10 hover:text-white' }}">
                        {{ strtoupper($locale) }}
                    </a>
                @endforeach
            </div>

            <a href="{{ localized_route('contact') }}" class="hidden items-center gap-2 rounded-md bg-emerald-300 px-4 py-2 text-sm font-bold text-graphite-950 transition hover:bg-emerald-200 md:inline-flex">
                <x-phosphor-paper-plane-tilt class="h-4 w-4" />
                {{ __('site.actions.start_project') }}
            </a>

            <button
                type="button"
                @click="toggle()"
                class="inline-grid h-10 w-10 place-items-center rounded-md border border-white/14 bg-white/8 text-white transition hover:bg-white/14 lg:hidden"
                :aria-expanded="show"
                aria-label="{{ __('site.nav.open_menu') }}"
            >
                <x-phosphor-list class="h-5 w-5" x-show="!show" />
                <x-phosphor-x class="h-5 w-5" x-show="show" style="display: none;" />
            </button>
        </div>
    </div>

    <div
        x-show="show"
        x-transition
        style="display: none;"
        class="border-t border-white/10 bg-graphite-950/96 px-5 py-4 backdrop-blur-xl lg:hidden"
    >
        <nav class="site-container flex flex-col gap-2" aria-label="{{ __('site.nav.mobile') }}">
            @foreach ($links as $link)
                <a
                    href="{{ localized_route($link['route']) }}"
                    class="rounded-md px-4 py-3 text-base font-semibold transition {{ request()->routeIs($link['route']) ? 'bg-white text-graphite-950' : 'text-white/78 hover:bg-white/10 hover:text-white' }}"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
            <div class="mt-2 grid grid-cols-2 gap-2">
                @foreach (array_keys(config('app.supported_locales', [])) as $locale)
                    <a href="{{ localized_current_url($locale) }}" class="rounded-md border border-white/12 px-4 py-3 text-center text-sm font-bold transition {{ current_locale() === $locale ? 'bg-white text-graphite-950' : 'text-white/78 hover:bg-white/10 hover:text-white' }}">
                        {{ strtoupper($locale) }}
                    </a>
                @endforeach
            </div>
            <a href="{{ localized_route('contact') }}" class="mt-2 inline-flex items-center justify-center gap-2 rounded-md bg-emerald-300 px-4 py-3 text-base font-bold text-graphite-950">
                <x-phosphor-paper-plane-tilt class="h-5 w-5" />
                {{ __('site.actions.start_project') }}
            </a>
        </nav>
    </div>
</header>
