<x-layouts.front
    :title="__('site.work.title')"
    :description="__('site.work.description')"
    activeMenu="true">

    <section class="page-hero bg-graphite-950 text-white">
        <div class="site-container grid gap-10 lg:grid-cols-[0.8fr_1fr] lg:items-end">
            <div>
                <p class="eyebrow text-emerald-200">{{ __('site.work.eyebrow') }}</p>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight md:text-6xl">{{ __('site.work.heading') }}</h1>
            </div>
            <p class="max-w-2xl text-base leading-8 text-white/68 lg:justify-self-end">
                {{ __('site.work.body') }}
            </p>
        </div>
    </section>

    <section class="section-band bg-stone-50" x-data="projectFilter({ projects: @js($work) })">
        <div class="site-container">
            <div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ __('site.work.categories_label') }}">
                <button type="button" @click="category = 'All'" class="filter-pill" :class="category === 'All' ? 'filter-pill-active' : ''">{{ __('site.work.all') }}</button>
                <template x-for="item in categories()" :key="item">
                    <button type="button" @click="category = item" class="filter-pill" :class="category === item ? 'filter-pill-active' : ''" x-text="item"></button>
                </template>
            </div>

            <div class="mt-8 grid gap-6 md:grid-cols-2">
                <template x-for="project in filtered()" :key="project.title">
                    <article class="overflow-hidden rounded-md border border-graphite-150 bg-white shadow-[0_24px_70px_rgba(16,24,24,0.08)]">
                        <img :src="'/' + project.image" :alt="project.title" class="h-64 w-full object-cover">
                        <div class="p-6">
                            <p class="text-xs font-bold uppercase text-emerald-700" x-text="project.category"></p>
                            <h2 class="mt-3 text-2xl font-extrabold text-graphite-950" x-text="project.title"></h2>
                            <p class="mt-3 text-sm leading-7 text-graphite-650" x-text="project.summary"></p>
                            <p class="mt-5 rounded-md bg-stone-100 px-4 py-3 text-sm font-semibold leading-6 text-graphite-800" x-text="project.outcome"></p>
                            <div class="mt-5 flex flex-wrap gap-2">
                                <template x-for="tag in project.tags" :key="tag">
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800" x-text="tag"></span>
                                </template>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </div>
    </section>

    <section class="section-band bg-white">
        <div class="site-container grid gap-8 lg:grid-cols-[0.75fr_1fr] lg:items-start">
            <div>
                <p class="eyebrow text-amber-700">{{ __('site.work.entry_eyebrow') }}</p>
                <h2 class="section-title mt-3">{{ __('site.work.entry_title') }}</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($services as $service)
                    <article class="surface-card">
                        <h3 class="text-lg font-bold text-graphite-950">{{ $service['name'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-graphite-650">{{ $service['problem'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.front>
