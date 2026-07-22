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
