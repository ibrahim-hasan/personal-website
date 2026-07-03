<x-layouts.front
    :title="__('site.writing.title')"
    :description="__('site.writing.description')"
    activeMenu="true">

    <section class="page-hero bg-graphite-950 text-white">
        <div class="site-container max-w-4xl">
            <p class="eyebrow text-emerald-200">{{ __('site.writing.eyebrow') }}</p>
            <h1 class="mt-4 text-4xl font-extrabold leading-tight md:text-6xl">{{ __('site.writing.heading') }}</h1>
            <p class="mt-6 max-w-2xl text-base leading-8 text-white/68">
                {{ __('site.writing.body') }}
            </p>
        </div>
    </section>

    <section class="section-band bg-stone-50">
        <div class="site-container grid gap-6 lg:grid-cols-[1fr_0.42fr]">
            <div class="space-y-4">
                @foreach ($writing as $note)
                    <article class="surface-card">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase text-emerald-800">{{ $note['type'] }}</span>
                            <span class="text-xs font-bold text-graphite-500">{{ $note['read_time'] }}</span>
                        </div>
                        <h2 class="mt-5 text-2xl font-extrabold text-graphite-950">{{ $note['title'] }}</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-graphite-650">{{ $note['summary'] }}</p>
                    </article>
                @endforeach
            </div>

            <aside class="rounded-md bg-graphite-950 p-6 text-white lg:sticky lg:top-28">
                <p class="eyebrow text-amber-200">{{ __('site.writing.toolchain_eyebrow') }}</p>
                <h2 class="mt-4 text-2xl font-extrabold">{{ __('site.writing.toolchain_title') }}</h2>
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($toolchain as $tool)
                        <span class="rounded-full border border-white/12 bg-white/6 px-3 py-1 text-xs font-bold text-white/72">{{ $tool }}</span>
                    @endforeach
                </div>
            </aside>
        </div>
    </section>

</x-layouts.front>
