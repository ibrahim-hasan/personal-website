<x-layouts.front
    :title="__('site.home.title')"
    :description="__('site.home.description')">

    <section class="precision-hero">
        <div class="precision-hero__halo" aria-hidden="true"></div>

        <div class="site-container relative z-10">
            <div class="precision-hero__grid">
                <div class="precision-hero__copy">
                    <p class="signal-label hero-enter" style="--enter-delay: 80ms">
                        <span class="signal-label__line" aria-hidden="true"></span>
                        {{ __('site.home.hero_kicker') }}
                    </p>

                    <h1 class="display-hero mt-7">
                        <span class="hero-line"><span style="--enter-delay: 140ms">{{ __('site.home.hero_line_1') }}</span></span>
                        <span class="hero-line hero-line--violet"><span style="--enter-delay: 230ms">{{ __('site.home.hero_line_2') }}</span></span>
                        <span class="hero-line"><span style="--enter-delay: 320ms">{{ __('site.home.hero_line_3') }}</span></span>
                    </h1>

                    <p class="hero-enter copy-lead mt-7 max-w-[62ch]" style="--enter-delay: 410ms">
                        {{ __('site.home.hero_body') }}
                    </p>

                    <div class="hero-enter mt-9 flex flex-col gap-3 sm:flex-row" style="--enter-delay: 500ms">
                        <a href="{{ localized_route('contact') }}#consultation" class="button-primary" data-magnetic>
                            <span>{{ __('site.actions.free_consultation') }}</span>
                            <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
                        </a>
                        <a href="#method" class="button-quiet">
                            <span>{{ __('site.actions.explore_method') }}</span>
                            <x-phosphor-arrow-down class="h-4 w-4" />
                        </a>
                    </div>

                    <p class="hero-enter mt-7 flex items-center gap-3 font-sans text-sm font-bold text-ink-soft" style="--enter-delay: 580ms">
                        <span class="partnership-mark" aria-hidden="true"></span>
                        {{ __('site.hero.partnerships') }}
                    </p>
                </div>

                <figure
                    class="precision-stage hero-enter"
                    style="--enter-delay: 220ms"
                    data-depth="portrait"
                >
                    <div class="precision-stage__media relative isolate overflow-hidden">
                        <video
                            class="precision-stage__video"
                            data-hero-video
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            poster="{{ asset('images/ibrahim/ibrahim-hero-video-poster.webp') }}"
                            aria-hidden="true"
                        >
                            <source src="{{ asset('videos/hero/ibrahim-hero.webm') }}" type="video/webm">
                            <source src="{{ asset('videos/hero/ibrahim-hero.mp4') }}" type="video/mp4">
                        </video>
                    </div>

                </figure>

                <div class="precision-hero__orbit" aria-hidden="true"><span></span></div>
            </div>

            <div class="hero-sequence" aria-label="{{ __('site.home.method_eyebrow') }}">
                <ol class="hero-sequence__steps">
                    @foreach (array_slice($process, 0, 3) as $step)
                        <li class="hero-sequence__item hero-enter" style="--enter-delay: {{ 620 + ($loop->index * 70) }}ms">
                            <span>{{ $step['step'] }}</span>
                            <strong>{{ $step['title'] }}</strong>
                        </li>
                    @endforeach
                </ol>
                <a href="#manifesto" class="hero-scroll-cue hero-enter" style="--enter-delay: 840ms">
                    <span>{{ __('site.home.scroll_label') }}</span>
                    <x-phosphor-arrow-down class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>

    <section id="manifesto" class="manifesto-section">
        <div class="site-container manifesto-section__grid">
            <div class="manifesto-section__copy">
                <h2 class="display-section max-w-[18ch] text-canvas" data-reveal="headline">
                    {{ __('site.home.manifesto_title') }}
                </h2>
                <p class="copy-lead mt-8 max-w-[58ch] text-violet-100" data-reveal="copy">
                    {{ __('site.home.manifesto_body') }}
                </p>
            </div>
        </div>
    </section>

    <section id="practice-atlas" class="atlas-section">
        <div class="site-container">
            <div class="atlas-intro">
                <div>
                    <p class="signal-label">{{ __('site.home.atlas_eyebrow') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]" data-reveal="headline">{{ __('site.home.atlas_title') }}</h2>
                </div>
                <p class="copy-lead max-w-[58ch] lg:justify-self-end" data-reveal="copy">{{ __('site.home.atlas_body') }}</p>
            </div>

            <div class="atlas-constellation">
                @foreach ($companies as $company)
                    <article class="atlas-chapter" style="--atlas-index: {{ $loop->index }}; --reveal-index: {{ $loop->index }}" data-reveal="chapter">
                        <div class="atlas-chapter__head">
                            <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                            <p>{{ $company['relationship'] }}</p>
                        </div>
                        @if ($company['logo_on_light'] !== '')
                            <div @class([
                                'atlas-chapter__brand',
                                'atlas-chapter__brand--code-moments' => $company['id'] === 'code-moments',
                                'atlas-chapter__brand--from-scratch' => $company['id'] === 'from-scratch',
                            ])>
                                <img
                                    src="{{ asset($company['logo_on_light']) }}"
                                    alt="{{ $company['logo_alt'] }}"
                                    width="{{ $company['logo_on_light_width'] }}"
                                    height="{{ $company['logo_on_light_height'] }}"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </div>
                        @endif
                        <h3>{{ $company['name'] }}</h3>
                        <p class="atlas-chapter__summary">{{ $company['summary'] }}</p>
                        <div class="atlas-chapter__focus">
                            <span>{{ __('site.home.atlas_focus') }}</span>
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

    <section id="method" class="experience-trajectory">
        <div class="site-container experience-trajectory__grid">
            <div class="experience-trajectory__intro">
                <p class="signal-label signal-label--light">{{ __('site.home.trajectory_eyebrow') }}</p>
                <h2 class="experience-trajectory__title display-section mt-6 max-w-[15ch] text-canvas" data-reveal="headline">{{ __('site.home.trajectory_title') }}</h2>
                <p class="copy-lead mt-7 max-w-[52ch] text-violet-100" data-reveal="copy">{{ __('site.home.trajectory_body') }}</p>
            </div>

            <ol class="experience-path">
                @foreach ($experience as $stage)
                    <li class="experience-stage" style="--reveal-index: {{ $loop->index }}" data-reveal="step">
                        <span class="experience-stage__number">{{ $stage['step'] }}</span>
                        <div>
                            <h3>{{ $stage['title'] }}</h3>
                            <p>{{ $stage['summary'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="section-standard practice-section">
        <div class="site-container">
            <div class="section-split">
                <h2 class="display-section max-w-[16ch]" data-reveal="headline">{{ __('site.home.practice_title') }}</h2>
                <p class="copy-lead max-w-[52ch] lg:justify-self-end" data-reveal="copy">{{ __('site.home.practice_body') }}</p>
            </div>

            @if ($services !== [])
                <div class="practice-list mt-16">
                    @foreach ($services as $service)
                    <a href="{{ localized_route('services') }}#{{ $service['id'] }}" class="practice-row" style="--reveal-index: {{ $loop->index }}" data-reveal="row">
                        <span class="practice-row__number">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <h3>{{ $service['name'] }}</h3>
                        <p>{{ $service['summary'] }}</p>
                        <span class="practice-row__arrow" aria-hidden="true"><x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" /></span>
                    </a>
                    @endforeach
                </div>
            @else
                <x-partials.content-empty
                    class="mt-12"
                    :eyebrow="__('site.services.empty_eyebrow')"
                    :title="__('site.services.empty_title')"
                    :body="__('site.services.empty_body')"
                    :action-url="localized_route('contact').'#consultation'"
                    :action-label="__('site.actions.start_project')"
                />
            @endif
        </div>
    </section>

    <section id="selected-systems" class="work-theatre work-theatre--atlas">
        <div class="site-container">
            <div class="section-split work-theatre__heading">
                <div>
                    <p class="signal-label signal-label--light">{{ __('site.home.work_eyebrow') }}</p>
                    <h2 class="display-section mt-6 max-w-[15ch] text-canvas" data-reveal="headline">{{ __('site.home.projects_title') }}</h2>
                </div>
                <p class="copy-lead max-w-[52ch] text-canvas/70 lg:justify-self-end" data-reveal="copy">{{ __('site.home.work_body') }}</p>
            </div>

            @if ($projects !== [])
                <div class="project-atlas">
                    @foreach ($projects as $project)
                    <article class="project-atlas__case project-atlas__case--{{ $loop->iteration }}" style="--reveal-index: {{ $loop->index }}" data-reveal="case">
                        <figure class="project-atlas__media" data-depth="media">
                            <img
                                src="{{ asset($project['image']) }}"
                                alt="{{ $project['alt'] }}"
                                width="1400"
                                height="900"
                                loading="lazy"
                                decoding="async"
                                class="project-atlas__image"
                            >
                            @if ($project['logo'] !== '')
                                <span class="project-brand">
                                    <img
                                        src="{{ asset($project['logo']) }}"
                                        alt="{{ $project['logo_alt'] }}"
                                        loading="lazy"
                                        decoding="async"
                                        class="project-brand__logo"
                                    >
                                </span>
                            @endif
                            <figcaption>
                                <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                                <span>{{ $project['sector'] }}</span>
                            </figcaption>
                        </figure>
                        <div class="project-atlas__copy">
                            <h3>{{ $project['title'] }}</h3>
                            <p>{{ $project['summary'] }}</p>
                            <dl>
                                <div>
                                    <dt>{{ __('site.home.project_challenge') }}</dt>
                                    <dd>{{ $project['challenge'] }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('site.home.project_outcome') }}</dt>
                                    <dd>{{ $project['outcome'] }}</dd>
                                </div>
                            </dl>
                        </div>
                    </article>
                    @endforeach
                </div>

                <div class="mt-14 flex justify-end">
                    <a href="{{ localized_route('work') }}" class="button-outline-light">
                        <span>{{ __('site.actions.view_all_work') }}</span>
                        <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
                    </a>
                </div>
            @else
                <x-partials.content-empty
                    class="mt-12"
                    tone="dark"
                    :eyebrow="__('site.work.empty_eyebrow')"
                    :title="__('site.work.empty_title')"
                    :body="__('site.work.empty_body')"
                    :action-url="localized_route('contact').'#consultation'"
                    :action-label="__('site.actions.start_project')"
                />
            @endif
        </div>
    </section>

    <section id="decision-room-experience" class="decision-room-band">
        <div class="site-container">
            <div class="decision-room-band__intro">
                <p class="signal-label">{{ __('site.home.decision_kicker') }}</p>
                <h2 class="display-section mt-6 max-w-[13ch]" data-reveal="headline">{{ __('site.home.decision_title') }}</h2>
                <p class="copy-lead mt-6 max-w-[58ch]" data-reveal="copy">{{ __('site.home.decision_body') }}</p>
            </div>

            <livewire:website.decision-room />
        </div>
    </section>

    <section class="section-standard writing-section">
        <div class="site-container editorial-sidebar">
            <div class="editorial-sidebar__intro">
                <p class="signal-label">{{ __('site.home.writing_eyebrow') }}</p>
                <h2 class="display-section mt-6 max-w-[12ch]" data-reveal="headline">{{ __('site.home.writing_title') }}</h2>
                <p class="copy-lead mt-7 max-w-[44ch]" data-reveal="copy">{{ __('site.home.writing_body') }}</p>
                <a href="{{ localized_route('writing') }}" class="text-link mt-8 inline-flex">
                    {{ __('site.actions.read_notes') }}
                    <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
                </a>
            </div>

            @if ($articles !== [])
                <div class="writing-list">
                    @foreach ($articles as $article)
                    <a href="{{ $article['url'] }}" class="writing-row writing-row--link" style="--reveal-index: {{ $loop->index }}" data-reveal="row">
                        <div class="writing-row__meta">
                            <span>{{ $article['type'] }}</span>
                            <span>{{ $article['read_time'] }}</span>
                        </div>
                        <h3>{{ $article['title'] }}</h3>
                        <p>{{ $article['summary'] }}</p>
                        <x-phosphor-arrow-up-right class="writing-row__arrow h-5 w-5 rtl:-rotate-90" />
                    </a>
                    @endforeach
                </div>
            @else
                <x-partials.content-empty
                    :eyebrow="__('site.writing.empty_eyebrow')"
                    :title="__('site.writing.empty_title')"
                    :body="__('site.writing.empty_body')"
                />
            @endif
        </div>
    </section>

    <section class="section-compact about-teaser">
        <div class="site-container about-teaser__grid">
            <figure class="about-teaser__portrait" data-reveal="media" data-depth="media">
                <img
                    src="{{ asset('images/ibrahim/ibrahim-speaking-hero.webp') }}"
                    alt="{{ __('site.about.portrait_alt') }}"
                    width="1200"
                    height="900"
                    loading="lazy"
                    decoding="async"
                >
            </figure>
            <div class="about-teaser__copy">
                <p class="signal-label">{{ __('site.about.eyebrow') }}</p>
                <h2 class="display-section mt-6 max-w-[14ch]" data-reveal="headline">{{ __('site.home.about_teaser_title') }}</h2>
                <p class="copy-lead mt-7 max-w-[60ch]" data-reveal="copy">{{ __('site.home.about_teaser_body') }}</p>
                <a href="{{ localized_route('about') }}" class="text-link mt-8 inline-flex">
                    {{ __('site.nav.about') }}
                    <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
                </a>
            </div>
        </div>
    </section>

</x-layouts.front>
