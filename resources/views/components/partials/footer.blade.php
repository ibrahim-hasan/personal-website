@php
    $year = now()->year;
    $contact = \App\Support\SiteContent::contact();
@endphp

<footer class="bg-graphite-950 text-white" aria-label="{{ __('site.footer.label') }}">
    <section class="border-y border-white/10 bg-[linear-gradient(90deg,rgba(45,212,191,0.12),rgba(245,158,11,0.10),rgba(255,255,255,0.02))]">
        <div class="site-container grid gap-8 py-12 lg:grid-cols-[1fr_0.7fr] lg:items-center">
            <div>
                <p class="text-sm font-bold uppercase text-emerald-200">{{ __('site.footer.cta_eyebrow') }}</p>
                <h2 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight md:text-5xl">
                    {{ __('site.footer.cta_title') }}
                </h2>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
                <a href="{{ localized_route('contact') }}" class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-300 px-5 py-3 text-sm font-bold text-graphite-950 transition hover:bg-emerald-200">
                    <x-phosphor-paper-plane-tilt class="h-5 w-5" />
                    {{ __('site.actions.start_project') }}
                </a>
                <a href="{{ localized_route('work') }}" class="inline-flex items-center justify-center gap-2 rounded-md border border-white/14 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                    <x-phosphor-squares-four class="h-5 w-5" />
                    {{ __('site.actions.view_work') }}
                </a>
            </div>
        </div>
    </section>

    <div class="site-container grid gap-10 py-12 md:grid-cols-[1.2fr_0.7fr_0.7fr_0.8fr]">
        <div>
            <a href="{{ localized_route('home') }}" class="inline-flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-md bg-white text-sm font-extrabold text-graphite-950">{{ __('site.brand.initials') }}</span>
                <span>
                    <span class="block text-sm font-bold">{{ __('site.brand.name') }}</span>
                    <span class="block text-xs font-medium text-white/55">{{ __('site.brand.tagline') }}</span>
                </span>
            </a>
            <p class="mt-5 max-w-sm text-sm leading-7 text-white/62">
                {{ __('site.footer.body') }}
            </p>
        </div>

        <div>
            <h3 class="text-sm font-bold text-white">{{ __('site.footer.navigate') }}</h3>
            <ul class="mt-4 space-y-3 text-sm text-white/64">
                <li><a class="transition hover:text-white" href="{{ localized_route('services') }}">{{ __('site.nav.services') }}</a></li>
                <li><a class="transition hover:text-white" href="{{ localized_route('work') }}">{{ __('site.nav.work') }}</a></li>
                <li><a class="transition hover:text-white" href="{{ localized_route('writing') }}">{{ __('site.nav.writing') }}</a></li>
                <li><a class="transition hover:text-white" href="{{ localized_route('about') }}">{{ __('site.nav.about') }}</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-bold text-white">{{ __('site.footer.focus') }}</h3>
            <ul class="mt-4 space-y-3 text-sm text-white/64">
                @foreach (__('site.footer.focus_items') as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-bold text-white">{{ __('site.footer.contact') }}</h3>
            <ul class="mt-4 space-y-3 text-sm text-white/64">
                <li><a class="transition hover:text-white" href="mailto:hello@ibrahimhasan.dev">hello@ibrahimhasan.dev</a></li>
                <li>{{ $contact['location'] }}</li>
                <li><a class="transition hover:text-white" href="{{ localized_route('contact') }}">{{ __('site.footer.project_intake') }}</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="site-container flex flex-col justify-between gap-3 py-5 text-xs font-medium text-white/45 sm:flex-row">
            <p>{{ __('site.footer.rights', ['year' => $year]) }}</p>
            <p>{{ __('site.footer.built_with') }}</p>
        </div>
    </div>
</footer>
