<x-layouts.front
    :title="__('site.services.title')"
    :description="__('site.services.description')"
    activeMenu="true">

    <section class="page-hero bg-graphite-950 text-white">
        <div class="site-container grid gap-10 lg:grid-cols-[0.8fr_1fr] lg:items-end">
            <div>
                <p class="eyebrow text-emerald-200">{{ __('site.services.eyebrow') }}</p>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight md:text-6xl">{{ __('site.services.heading') }}</h1>
            </div>
            <p class="max-w-2xl text-base leading-8 text-white/68 lg:justify-self-end">
                {{ __('site.services.body') }}
            </p>
        </div>
    </section>

    <section class="section-band bg-stone-50" x-data="serviceTabs({ services: @js($services) })">
        <div class="site-container">
            <div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ __('site.services.tabs_label') }}">
                @foreach ($services as $service)
                    <button
                        type="button"
                        role="tab"
                        @click="active = '{{ $service['id'] }}'"
                        :aria-selected="active === '{{ $service['id'] }}'"
                        class="rounded-md border px-4 py-3 text-sm font-bold transition"
                        :class="active === '{{ $service['id'] }}' ? 'border-graphite-950 bg-graphite-950 text-white' : 'border-graphite-200 bg-white text-graphite-700 hover:border-emerald-300 hover:bg-emerald-50'"
                    >
                        {{ $service['name'] }}
                    </button>
                @endforeach
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
                <article class="rounded-md bg-graphite-950 p-7 text-white shadow-[0_24px_70px_rgba(16,24,24,0.18)]">
                    <p class="eyebrow text-emerald-200">{{ __('site.services.selected_track') }}</p>
                    <h2 class="mt-4 text-3xl font-extrabold leading-tight" x-text="current().name"></h2>
                    <p class="mt-5 text-base leading-8 text-white/68" x-text="current().summary"></p>
                    <div class="mt-8">
                        <a href="{{ localized_route('contact') }}" class="inline-flex items-center gap-2 rounded-md bg-emerald-300 px-5 py-3 text-sm font-bold text-graphite-950 transition hover:bg-emerald-200">
                            <x-phosphor-paper-plane-tilt class="h-5 w-5" />
                            {{ __('site.actions.discuss_service') }}
                        </a>
                    </div>
                </article>

                <div class="grid gap-4 md:grid-cols-2">
                    <article class="surface-card">
                        <span class="service-icon"><x-phosphor-warning-diamond class="h-5 w-5" /></span>
                        <h3 class="mt-5 text-lg font-bold text-graphite-950">{{ __('site.services.problem_pattern') }}</h3>
                        <p class="mt-3 text-sm leading-7 text-graphite-650" x-text="current().problem"></p>
                    </article>
                    <article class="surface-card">
                        <span class="service-icon"><x-phosphor-path class="h-5 w-5" /></span>
                        <h3 class="mt-5 text-lg font-bold text-graphite-950">{{ __('site.services.approach') }}</h3>
                        <p class="mt-3 text-sm leading-7 text-graphite-650" x-text="current().approach"></p>
                    </article>
                    <article class="surface-card">
                        <span class="service-icon"><x-phosphor-check-circle class="h-5 w-5" /></span>
                        <h3 class="mt-5 text-lg font-bold text-graphite-950">{{ __('site.services.deliverables') }}</h3>
                        <ul class="mt-3 space-y-2 text-sm font-semibold text-graphite-650">
                            <template x-for="deliverable in current().deliverables" :key="deliverable">
                                <li class="flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    <span x-text="deliverable"></span>
                                </li>
                            </template>
                        </ul>
                    </article>
                    <article class="surface-card">
                        <span class="service-icon"><x-phosphor-chart-line-up class="h-5 w-5" /></span>
                        <h3 class="mt-5 text-lg font-bold text-graphite-950">{{ __('site.services.useful_result') }}</h3>
                        <p class="mt-3 text-sm leading-7 text-graphite-650" x-text="current().result"></p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="section-band bg-white">
        <div class="site-container">
            <div class="max-w-2xl">
                <p class="eyebrow text-amber-700">{{ __('site.services.engagement_eyebrow') }}</p>
                <h2 class="section-title mt-3">{{ __('site.services.engagement_title') }}</h2>
            </div>
            <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($process as $step)
                    <article class="surface-card">
                        <span class="text-3xl font-extrabold text-emerald-700">{{ $step['step'] }}</span>
                        <h3 class="mt-4 text-lg font-bold text-graphite-950">{{ $step['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-graphite-650">{{ $step['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.front>
