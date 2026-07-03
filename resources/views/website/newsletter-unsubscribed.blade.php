<x-layouts.front
    :title="__('site.newsletter.unsubscribed_title')"
    :description="__('site.newsletter.unsubscribed_description')"
    activeMenu="true">

    <section class="page-hero bg-graphite-950 text-white">
        <div class="site-container max-w-2xl text-center">
            <span class="mx-auto grid h-14 w-14 place-items-center rounded-md bg-emerald-300 text-graphite-950">
                <x-phosphor-check class="h-7 w-7" />
            </span>
            <h1 class="mt-6 text-4xl font-extrabold leading-tight md:text-5xl">{{ __('site.newsletter.unsubscribed_heading') }}</h1>
            <p class="mt-5 text-base leading-8 text-white/68">{{ __('site.newsletter.unsubscribed_body') }}</p>
            <a href="{{ localized_route('home') }}" class="mt-8 inline-flex items-center justify-center gap-2 rounded-md bg-emerald-300 px-5 py-3 text-sm font-bold text-graphite-950 transition hover:bg-emerald-200">
                <x-phosphor-arrow-left class="h-5 w-5" />
                {{ __('site.actions.return_home') }}
            </a>
        </div>
    </section>

</x-layouts.front>
