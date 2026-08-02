<x-layouts.front
    :title="__('site.about.title')"
    :description="__('site.about.description')"
    schemaType="ProfilePage"
    activeMenu="true">

    <section class="page-intro about-intro">
        <div class="site-container about-intro__grid">
            <div>
                <p class="signal-label">{{ __('site.about.eyebrow') }}</p>
                <h1 class="display-page mt-7 max-w-[13ch]">{{ __('site.about.heading') }}</h1>
                <p class="copy-lead mt-8 max-w-[64ch]">{{ $biography }}</p>
            </div>
            <figure class="about-intro__portrait" data-reveal="media" data-depth="media">
                <img
                    src="{{ asset('images/ibrahim/ibrahim-formal-portrait.webp') }}"
                    alt="{{ __('site.about.portrait_alt') }}"
                    width="1120"
                    height="1400"
                    fetchpriority="high"
                    decoding="async"
                >
            </figure>
        </div>
    </section>

    <section class="about-journey" aria-labelledby="about-journey-title">
        <div class="site-container about-journey__grid">
            <div>
                <p class="signal-label signal-label--light">{{ __('site.about.journey_eyebrow') }}</p>
                <h2 id="about-journey-title" class="display-section mt-6 max-w-[14ch] text-canvas" data-reveal="headline">{{ __('site.about.journey_title') }}</h2>
            </div>
            <p class="about-journey__body" data-reveal="copy">{{ __('site.about.journey_body') }}</p>
        </div>
    </section>

    @if ($services !== [])
        <section id="expertise" class="section-standard bg-canvas-bright" aria-labelledby="about-expertise-title">
            <div class="site-container">
                <div class="section-split">
                    <div>
                        <p class="signal-label">{{ __('site.about.expertise_eyebrow') }}</p>
                        <h2 id="about-expertise-title" class="display-section mt-6 max-w-[15ch]" data-reveal="headline">{{ __('site.about.expertise_title') }}</h2>
                    </div>
                    <p class="copy-lead max-w-[54ch] lg:justify-self-end" data-reveal="copy">{{ __('site.about.expertise_body') }}</p>
                </div>

                <div class="mt-12 grid overflow-hidden border border-ink/10 bg-ink/10 sm:grid-cols-2" data-reveal="copy">
                    @foreach ($services as $service)
                        <a
                            href="{{ localized_route('services') }}#{{ $service['id'] }}"
                            class="group min-h-52 bg-canvas p-6 transition-colors duration-200 hover:bg-violet-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-violet-700 sm:p-8"
                        >
                            <span class="font-ui text-xs font-bold tracking-[0.12em] text-violet-700">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <h3 class="mt-7 max-w-[22ch] font-display text-2xl font-black leading-tight text-ink sm:text-3xl">{{ $service['name'] }}</h3>
                            <p class="mt-4 max-w-[52ch] text-sm leading-7 text-ink-soft">{{ $service['summary'] }}</p>
                            <span class="mt-7 inline-flex items-center gap-2 font-ui text-sm font-bold text-violet-700">
                                {{ __('site.actions.discuss_service') }}
                                <x-phosphor-arrow-up-right class="h-4 w-4 transition-transform duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5 rtl:group-hover:translate-y-0.5" aria-hidden="true" />
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section-standard about-current-work" aria-labelledby="about-current-work-title">
        <div class="site-container about-current-work__grid editorial-sidebar">
            <div class="about-current-work__intro editorial-sidebar__intro">
                <div>
                    <p class="signal-label">{{ __('site.about.current_work_eyebrow') }}</p>
                    <h2 id="about-current-work-title" class="display-section mt-6 max-w-[14ch]" data-reveal="headline">{{ __('site.about.current_work_title') }}</h2>
                </div>
                <p class="copy-lead mt-7 max-w-[48ch]" data-reveal="copy">{{ __('site.about.current_work_body') }}</p>
            </div>

            <div class="about-current-work__list">
                @foreach ($companies as $company)
                    <article data-reveal="row">
                        <p class="about-current-work__role">{{ $company['relationship'] }}</p>
                        <div>
                            <h3>{{ __('site.about.current_work_items.'.$company['id'].'.title') }}</h3>
                            <p>{{ __('site.about.current_work_items.'.$company['id'].'.body') }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <x-athar.proof :cards="$athar" placement="about" />

</x-layouts.front>
