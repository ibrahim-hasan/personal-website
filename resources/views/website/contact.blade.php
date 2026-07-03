<x-layouts.front
    :title="__('site.contact.title')"
    :description="__('site.contact.description')"
    activeMenu="true">

    <section class="page-hero bg-graphite-950 text-white">
        <div class="site-container grid gap-10 lg:grid-cols-[0.9fr_0.8fr] lg:items-end">
            <div>
                <p class="eyebrow text-emerald-200">{{ __('site.contact.eyebrow') }}</p>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight md:text-6xl">{{ __('site.contact.heading') }}</h1>
                <p class="mt-6 max-w-2xl text-base leading-8 text-white/68">
                    {{ $contact['availability'] }}
                </p>
            </div>
            <div class="rounded-md border border-white/10 bg-white/[0.04] p-6">
                <p class="text-sm font-bold uppercase text-amber-200">{{ __('site.contact.context_title') }}</p>
                <ul class="mt-5 space-y-3 text-sm leading-7 text-white/68">
                    @foreach (__('site.contact.context_items') as $item)
                        <li class="flex gap-3"><span class="mt-2 h-1.5 w-1.5 rounded-full bg-emerald-300"></span><span>{{ $item }}</span></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section class="section-band bg-stone-50">
        <div class="site-container grid gap-8 lg:grid-cols-[0.75fr_1fr]">
            <div>
                <p class="eyebrow text-emerald-700">{{ __('site.contact.channels_eyebrow') }}</p>
                <h2 class="section-title mt-3">{{ __('site.contact.channels_title') }}</h2>
                <p class="mt-5 text-base leading-8 text-graphite-650">
                    {{ __('site.contact.channels_body') }}
                </p>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($contact['channels'] as $channel)
                    <a href="{{ $channel['href'] }}" class="surface-card group min-w-0">
                        <span class="service-icon transition group-hover:bg-emerald-200"><x-phosphor-arrow-up-right class="h-5 w-5" /></span>
                        <h3 class="mt-5 text-lg font-bold text-graphite-950">{{ $channel['label'] }}</h3>
                        <p class="mt-3 break-words text-sm leading-7 text-graphite-650">{{ $channel['value'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-band bg-white">
        <div class="site-container">
            <div class="max-w-2xl">
                <p class="eyebrow text-amber-700">{{ __('site.contact.ask_eyebrow') }}</p>
                <h2 class="section-title mt-3">{{ __('site.contact.ask_title') }}</h2>
            </div>
            <div class="mt-10 grid gap-4 md:grid-cols-2">
                @foreach ($services as $service)
                    <article class="surface-card">
                        <h3 class="text-lg font-bold text-graphite-950">{{ $service['name'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-graphite-650">{{ $service['summary'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.front>
