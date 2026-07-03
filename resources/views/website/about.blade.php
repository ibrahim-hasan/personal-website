<x-layouts.front
    :title="__('site.about.title')"
    :description="__('site.about.description')"
    activeMenu="true">

    <section class="page-hero bg-graphite-950 text-white">
        <div class="site-container grid gap-10 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-end">
            <div>
                <p class="eyebrow text-emerald-200">{{ __('site.about.eyebrow') }}</p>
                <h1 class="mt-4 max-w-4xl text-4xl font-extrabold leading-tight md:text-6xl">{{ __('site.about.heading') }}</h1>
                <p class="mt-6 max-w-2xl text-base leading-8 text-white/68">
                    {{ __('site.about.body') }}
                </p>
            </div>
            <figure class="mx-auto w-full max-w-[280px] overflow-hidden rounded-md border border-white/14 bg-white/8 shadow-[0_30px_90px_rgba(0,0,0,0.34)] md:max-w-[320px] lg:mx-0">
                <img src="{{ asset('images/ibrahim/ibrahim-hasan-portrait.png') }}" alt="{{ __('site.about.portrait_alt') }}" class="aspect-[4/5] w-full object-cover object-top">
            </figure>
        </div>
    </section>

    <section class="section-band bg-stone-50">
        <div class="site-container grid gap-10 lg:grid-cols-[0.8fr_1fr]">
            <div>
                <p class="eyebrow text-amber-700">{{ __('site.about.principles_eyebrow') }}</p>
                <h2 class="section-title mt-3">{{ __('site.about.principles_title') }}</h2>
            </div>
            <div class="grid gap-4">
                @foreach ($process as $step)
                    <article class="surface-card grid gap-4 md:grid-cols-[72px_1fr]">
                        <span class="text-3xl font-extrabold text-emerald-700">{{ $step['step'] }}</span>
                        <div>
                            <h3 class="text-xl font-bold text-graphite-950">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-7 text-graphite-650">{{ $step['body'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-band bg-white">
        <div class="site-container">
            <div class="max-w-2xl">
                <p class="eyebrow text-emerald-700">{{ __('site.about.toolchain_eyebrow') }}</p>
                <h2 class="section-title mt-3">{{ __('site.about.toolchain_title') }}</h2>
            </div>
            <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($toolchain as $tool)
                    <div class="rounded-md border border-graphite-150 bg-white px-4 py-4 text-sm font-bold text-graphite-800 shadow-sm">
                        {{ $tool }}
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-band bg-graphite-950 text-white">
        <div class="site-container grid gap-8 lg:grid-cols-[1fr_0.7fr] lg:items-center">
            <div>
                <p class="eyebrow text-emerald-200">{{ __('site.about.fit_eyebrow') }}</p>
                <h2 class="mt-3 max-w-3xl text-3xl font-extrabold leading-tight md:text-5xl">
                    {{ __('site.about.fit_title') }}
                </h2>
                <p class="mt-5 max-w-2xl text-base leading-8 text-white/64">
                    {{ __('site.about.fit_body') }}
                </p>
            </div>
            <a href="{{ localized_route('contact') }}" class="inline-flex w-fit items-center justify-center gap-2 rounded-md bg-emerald-300 px-5 py-3 text-sm font-bold text-graphite-950 transition hover:bg-emerald-200 lg:justify-self-end">
                <x-phosphor-paper-plane-tilt class="h-5 w-5" />
                {{ __('site.actions.start_project') }}
            </a>
        </div>
    </section>

</x-layouts.front>
