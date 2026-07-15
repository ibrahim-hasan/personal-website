<x-layouts.front
    :title="__('site.about.title')"
    :description="__('site.about.description')"
    activeMenu="true">

    <section class="page-intro about-intro">
        <div class="site-container about-intro__grid">
            <div>
                <p class="signal-label">{{ __('site.about.eyebrow') }}</p>
                <h1 class="display-page mt-7 max-w-[13ch]">{{ __('site.about.heading') }}</h1>
                <p class="copy-lead mt-8 max-w-[64ch]">{{ __('site.about.body') }}</p>
            </div>
            <figure class="about-intro__portrait" data-reveal="media" data-depth="media">
                <img
                    src="{{ asset('images/ibrahim/ibrahim-speaking-hero.webp') }}"
                    alt="{{ __('site.about.portrait_alt') }}"
                    width="1200"
                    height="900"
                    fetchpriority="high"
                    decoding="async"
                >
                <figcaption aria-hidden="true">IBRAHIM HASAN / STRATEGY IN PRACTICE</figcaption>
            </figure>
        </div>
    </section>

    <section class="section-feature bg-canvas-bright">
        <div class="site-container story-split">
            <div>
                <p class="signal-label">{{ __('site.about.background_eyebrow') }}</p>
                <h2 class="display-section mt-6 max-w-[13ch]" data-reveal="headline">{{ __('site.about.background_title') }}</h2>
            </div>
            <p class="story-split__body" data-reveal="copy">{{ __('site.about.background_body') }}</p>
        </div>
    </section>

    <section class="about-chapters">
        <div class="site-container">
            <div class="about-chapters__intro">
                <div>
                    <p class="signal-label signal-label--light">{{ __('site.about.companies_eyebrow') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch] text-canvas" data-reveal="headline">{{ __('site.about.companies_title') }}</h2>
                </div>
                <p class="copy-lead max-w-[52ch] text-violet-100 lg:justify-self-end" data-reveal="copy">{{ __('site.about.companies_body') }}</p>
            </div>

            <div class="about-chapters__list">
                @foreach ($companies as $company)
                    <article style="--reveal-index: {{ $loop->index }}" data-reveal="chapter">
                        <div class="about-chapters__number">{{ sprintf('%02d', $loop->iteration) }}</div>
                        <div class="about-chapters__copy">
                            @if ($company['logo'] !== '')
                                <div @class([
                                    'about-chapters__brand',
                                    'about-chapters__brand--symbol' => $company['id'] === 'from-scratch',
                                ])>
                                    <img
                                        src="{{ asset($company['logo']) }}"
                                        alt="{{ $company['logo_alt'] }}"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                </div>
                            @endif
                            <p>{{ $company['relationship'] }}</p>
                            <h3>{{ $company['name'] }}</h3>
                            <span>{{ $company['summary'] }}</span>
                        </div>
                        <div class="about-chapters__focus">
                            <p>{{ __('site.about.focus_label') }}</p>
                            <ul>
                                @foreach ($company['focus'] as $focus)
                                    <li>{{ $focus }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-standard about-trajectory">
        <div class="site-container narrative-rail">
            <div class="narrative-rail__intro">
                <p class="signal-label">{{ __('site.about.trajectory_eyebrow') }}</p>
                <h2 class="display-section mt-6 max-w-[13ch]" data-reveal="headline">{{ __('site.about.trajectory_title') }}</h2>
            </div>
            <ol class="about-trajectory__list">
                @foreach ($experience as $stage)
                    <li style="--reveal-index: {{ $loop->index }}" data-reveal="step">
                        <span>{{ $stage['step'] }}</span>
                        <div>
                            <h3>{{ $stage['title'] }}</h3>
                            <p>{{ $stage['summary'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="perspective-band">
        <div class="site-container">
            <div class="section-split">
                <div>
                    <p class="signal-label signal-label--light">{{ __('site.about.lenses_eyebrow') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch] text-canvas" data-reveal="headline">{{ __('site.about.lenses_title') }}</h2>
                </div>
                <p class="copy-lead max-w-[52ch] text-canvas/70 lg:justify-self-end" data-reveal="copy">{{ __('site.about.fit_body') }}</p>
            </div>

            <div class="perspective-grid">
                @foreach ($lenses as $lens)
                    <article style="--reveal-index: {{ $loop->index }}" data-reveal="chapter">
                        <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                        <h3>{{ $lens['label'] }}</h3>
                        <p>{{ $lens['description'] }}</p>
                        <strong>{{ $lens['question'] }}</strong>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-standard principles-section">
        <div class="site-container narrative-rail">
            <div class="narrative-rail__intro">
                <p class="signal-label">{{ __('site.about.principles_eyebrow') }}</p>
                <h2 class="display-section mt-6 max-w-[13ch]" data-reveal="headline">{{ __('site.about.principles_title') }}</h2>
            </div>
            <div class="principle-list">
                @foreach ($process as $step)
                    <article class="principle-row" style="--reveal-index: {{ $loop->index }}" data-reveal="row">
                        <span>{{ $step['step'] }}</span>
                        <div>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['body'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-compact">
        <div class="site-container fit-statement">
            <div>
                <p class="signal-label">{{ __('site.about.fit_eyebrow') }}</p>
                <h2 class="display-section mt-6 max-w-[14ch]" data-reveal="headline">{{ __('site.about.fit_title') }}</h2>
            </div>
            <a href="{{ localized_route('contact') }}#consultation" class="button-primary" data-magnetic>
                <span>{{ __('site.actions.free_consultation') }}</span>
                <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
            </a>
        </div>
    </section>

</x-layouts.front>
