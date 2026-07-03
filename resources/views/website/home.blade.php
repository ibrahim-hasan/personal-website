<x-layouts.front
    :title="__('site.home.title')"
    :description="__('site.home.description')">

    <section class="hero-shell overflow-hidden pt-[76px] text-white">
        <img src="{{ asset('images/ibrahim/hero-workspace.png') }}" alt="{{ __('site.home.hero_image_alt') }}" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 {{ is_rtl() ? 'bg-[linear-gradient(270deg,rgba(8,13,16,0.92),rgba(8,13,16,0.68),rgba(8,13,16,0.32))]' : 'bg-[linear-gradient(90deg,rgba(8,13,16,0.92),rgba(8,13,16,0.68),rgba(8,13,16,0.32))]' }}"></div>
        <div class="absolute inset-x-0 bottom-0 h-36 bg-[linear-gradient(0deg,rgb(250,250,247),rgba(250,250,247,0))]"></div>

        <div class="site-container relative z-10 grid gap-10 py-8 md:py-12 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-end">
            <div class="max-w-3xl">
                <p class="eyebrow text-emerald-200">{{ __('site.home.hero_eyebrow') }}</p>
                <h1 class="mt-4 text-4xl font-extrabold leading-[1.02] sm:text-5xl md:mt-5 md:text-7xl">
                    {{ __('site.brand.name') }}
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-white/76 md:mt-6 md:text-xl md:leading-8">
                    {{ __('site.home.hero_body') }}
                </p>
                <div class="mt-6 flex flex-col gap-3 sm:flex-row md:mt-8">
                    <a href="{{ localized_route('contact') }}" class="inline-flex items-center justify-center gap-2 rounded-md bg-emerald-300 px-5 py-3 text-sm font-bold text-graphite-950 transition hover:bg-emerald-200">
                        <x-phosphor-paper-plane-tilt class="h-5 w-5" />
                        {{ __('site.actions.start_project') }}
                    </a>
                    <a href="{{ localized_route('work') }}" class="inline-flex items-center justify-center gap-2 rounded-md border border-white/18 bg-white/8 px-5 py-3 text-sm font-bold text-white backdrop-blur transition hover:bg-white/14">
                        <x-phosphor-squares-four class="h-5 w-5" />
                        {{ __('site.actions.see_work') }}
                    </a>
                </div>

                <dl class="mt-7 grid max-w-2xl grid-cols-2 gap-3 md:mt-10 md:grid-cols-4">
                    @foreach ($stats as $stat)
                        <div class="rounded-md border border-white/12 bg-white/8 p-3 backdrop-blur md:p-4">
                            <dt class="text-xl font-extrabold text-white md:text-2xl">{{ $stat['value'] }}</dt>
                            <dd class="mt-2 text-xs font-semibold leading-5 text-white/62">{{ $stat['label'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <figure class="mx-auto w-full max-w-[300px] overflow-hidden rounded-md border border-white/18 bg-white/8 shadow-[0_30px_90px_rgba(0,0,0,0.38)] backdrop-blur md:max-w-[340px] lg:mx-0">
                <img src="{{ asset('images/ibrahim/ibrahim-hasan-portrait.png') }}" alt="{{ __('site.home.portrait_alt') }}" class="aspect-[4/5] w-full object-cover object-top">
            </figure>
        </div>
    </section>

    <section class="bg-stone-50 py-12 md:py-16">
        <div class="site-container grid gap-10 lg:grid-cols-[0.75fr_1fr] lg:items-start">
            <div>
                <p class="eyebrow text-emerald-700">{{ __('site.home.intro_eyebrow') }}</p>
                <h2 class="section-title mt-3">{{ __('site.home.intro_title') }}</h2>
                <p class="mt-5 text-base leading-8 text-graphite-650">
                    {{ __('site.home.intro_body') }}
                </p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($services as $service)
                    <article class="surface-card">
                        <div class="flex items-center gap-3">
                            <span class="grid h-10 w-10 place-items-center rounded-md bg-emerald-100 text-emerald-800">
                                <x-phosphor-cube-focus class="h-5 w-5" />
                            </span>
                            <h3 class="text-lg font-bold text-graphite-950">{{ $service['name'] }}</h3>
                        </div>
                        <p class="mt-4 text-sm leading-7 text-graphite-650">{{ $service['summary'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-band bg-white">
        <div class="site-container">
            <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end">
                <div>
                    <p class="eyebrow text-amber-700">{{ __('site.home.work_eyebrow') }}</p>
                    <h2 class="section-title mt-3 max-w-2xl">{{ __('site.home.work_title') }}</h2>
                </div>
                <a href="{{ localized_route('work') }}" class="inline-flex w-fit items-center gap-2 rounded-md border border-graphite-200 px-4 py-2 text-sm font-bold text-graphite-900 transition hover:border-emerald-300 hover:bg-emerald-50">
                    {{ __('site.actions.view_all_work') }}
                    <x-phosphor-arrow-right class="h-4 w-4" />
                </a>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                @foreach ($work as $item)
                    <article class="overflow-hidden rounded-md border border-graphite-150 bg-white shadow-[0_24px_70px_rgba(16,24,24,0.08)]">
                        <img src="{{ asset($item['image']) }}" alt="{{ $item['title'] }}" class="h-56 w-full object-cover">
                        <div class="p-6">
                            <p class="text-xs font-bold uppercase text-emerald-700">{{ $item['category'] }}</p>
                            <h3 class="mt-3 text-xl font-extrabold text-graphite-950">{{ $item['title'] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-graphite-650">{{ $item['summary'] }}</p>
                            <p class="mt-5 rounded-md bg-stone-100 px-4 py-3 text-sm font-semibold leading-6 text-graphite-800">{{ $item['outcome'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-band bg-graphite-950 text-white">
        <div class="site-container grid gap-12 lg:grid-cols-[0.8fr_1fr] lg:items-start">
            <div>
                <p class="eyebrow text-emerald-200">{{ __('site.home.method_eyebrow') }}</p>
                <h2 class="section-title mt-3 text-white">{{ __('site.home.method_title') }}</h2>
                <p class="mt-5 text-base leading-8 text-white/64">
                    {{ __('site.home.method_body') }}
                </p>
            </div>
            <div class="grid gap-4">
                @foreach ($process as $step)
                    <article class="grid gap-4 rounded-md border border-white/10 bg-white/[0.04] p-5 md:grid-cols-[72px_1fr]">
                        <span class="text-2xl font-extrabold text-amber-200">{{ $step['step'] }}</span>
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-7 text-white/62">{{ $step['body'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-band bg-stone-50">
        <div class="site-container">
            <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end">
                <div>
                    <p class="eyebrow text-emerald-700">{{ __('site.home.writing_eyebrow') }}</p>
                    <h2 class="section-title mt-3 max-w-2xl">{{ __('site.home.writing_title') }}</h2>
                </div>
                <a href="{{ localized_route('writing') }}" class="inline-flex w-fit items-center gap-2 rounded-md border border-graphite-200 px-4 py-2 text-sm font-bold text-graphite-900 transition hover:border-emerald-300 hover:bg-emerald-50">
                    {{ __('site.actions.read_notes') }}
                    <x-phosphor-arrow-right class="h-4 w-4" />
                </a>
            </div>

            <div class="mt-10 grid gap-4 md:grid-cols-3">
                @foreach ($writing as $note)
                    <article class="surface-card">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-xs font-bold uppercase text-amber-700">{{ $note['type'] }}</p>
                            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-bold text-graphite-650">{{ $note['read_time'] }}</span>
                        </div>
                        <h3 class="mt-5 text-xl font-extrabold text-graphite-950">{{ $note['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-graphite-650">{{ $note['summary'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.front>
