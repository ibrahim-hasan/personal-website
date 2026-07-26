<x-layouts.front
    :title="$caseStudy['title']"
    :description="$caseStudy['summary']"
    :canonicalUrl="$canonicalUrl"
    :alternateUrls="$alternateUrls"
    :ogImage="$caseStudy['image_media']['src'] !== '' ? $caseStudy['image_media']['src'] : null"
    :ogImageAlt="$caseStudy['image_alt'] !== '' ? $caseStudy['image_alt'] : null"
    :schemaType="$caseStudy['anonymized'] ? 'WebPage' : 'CreativeWork'"
    :structuredData="$structuredData"
    activeMenu="true"
    suppressTerminalCta>

    <article class="service-page">
        <header class="page-intro service-intro">
            <div class="site-container page-intro__grid">
                <div>
                    <nav aria-label="{{ __('site.work.breadcrumb_label') }}">
                        <a href="{{ localized_route('work') }}" class="text-link">
                            <x-phosphor-arrow-left class="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                            <span>{{ __('site.work.back_to_index') }}</span>
                        </a>
                    </nav>
                    <p class="signal-label mt-10">{{ __('site.work.case_study') }}</p>
                    <h1 class="display-page mt-7 max-w-[13ch]">{{ $caseStudy['title'] }}</h1>
                    <p class="mt-5 text-sm text-ink/60">{{ $caseStudy['sector'] }}</p>
                </div>
                <div class="lg:self-end lg:justify-self-end">
                    <p class="copy-lead max-w-[58ch]">{{ $caseStudy['summary'] }}</p>
                    <p class="mt-6 max-w-[58ch] text-ink/70">{{ $caseStudy['sections']['executive_summary'] }}</p>
                </div>
            </div>

            @if ($caseStudy['image'] !== '')
                <div class="site-container mt-12">
                    <figure class="case-study__media">
                        <x-media.responsive-image
                            :image="$caseStudy['image_media']"
                            :alt="$caseStudy['image_alt']"
                            sizes="(min-width: 86rem) 86rem, calc(100vw - 2rem)"
                            loading="eager"
                            fetchpriority="high"
                            decoding="async"
                            class="case-study__image"
                        />
                    </figure>
                </div>
            @endif
        </header>

        <section class="section-standard">
            <div class="site-container editorial-sidebar">
                <div class="editorial-sidebar__intro">
                    <p class="signal-label">{{ __('site.work.context') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.context') }}</h2>
                </div>
                <p class="copy-lead max-w-[58ch]">{{ $caseStudy['sections']['context'] }}</p>
            </div>
        </section>

        <section class="section-feature service-explorer">
            <div class="site-container service-explorer__grid">
                <div>
                    <p class="signal-label">{{ __('site.work.challenge') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.challenge') }}</h2>
                    <p class="copy-lead mt-7 max-w-[58ch]">{{ $caseStudy['challenge'] }}</p>
                </div>
                <dl class="service-detail__facts mt-0">
                    <div>
                        <dt>{{ __('site.work.role') }}</dt>
                        <dd>{{ $caseStudy['role'] }}</dd>
                    </div>
                    @if ($caseStudy['delivery_entity'] !== null)
                        <div>
                            <dt>{{ __('site.work.delivery_entity') }}</dt>
                            <dd>{{ __('site.work.delivery_entities.'.$caseStudy['delivery_entity']) }}</dd>
                        </div>
                    @endif
                    @if ($caseStudy['delivery_period'] !== null)
                        <div>
                            <dt>{{ __('site.work.delivery_period') }}</dt>
                            <dd>{{ $caseStudy['delivery_period'] }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt>{{ __('site.work.confidentiality_note') }}</dt>
                        <dd>{{ $caseStudy['confidentiality_note'] }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section class="section-standard">
            <div class="site-container editorial-sidebar">
                <div class="editorial-sidebar__intro">
                    <p class="signal-label">{{ __('site.work.constraints') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.constraints') }}</h2>
                </div>
                <ul class="open-list" aria-label="{{ __('site.work.constraints') }}">
                    @foreach ($caseStudy['sections']['constraints'] as $constraint)
                        <li class="open-list__item">
                            <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                            <p>{{ $constraint }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="section-feature service-explorer">
            <div class="site-container service-explorer__grid">
                <div>
                    <p class="signal-label">{{ __('site.work.what_changed') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.what_changed') }}</h2>
                </div>
                <div class="open-list">
                    @foreach ($caseStudy['sections']['changes'] as $change)
                        <article class="open-list__item">
                            <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                            <div>
                                <h3>{{ __('site.work.change_areas.'.$change['area']) }}</h3>
                                <p>{{ $change['body'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section-standard">
            <div class="site-container service-explorer__grid">
                <article class="service-detail pt-0">
                    <p class="signal-label">{{ __('site.work.solution') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.solution') }}</h2>
                    <p class="copy-lead mt-7 max-w-[58ch]">{{ $caseStudy['response'] }}</p>
                    <p class="mt-7 max-w-[58ch]">{{ $caseStudy['sections']['solution'] }}</p>
                </article>
                <article class="service-detail pt-0">
                    <p class="signal-label">{{ __('site.work.implementation') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.implementation') }}</h2>
                    <p class="mt-7 max-w-[58ch]">{{ $caseStudy['sections']['implementation'] }}</p>
                    <p class="signal-label mt-12">{{ __('site.work.adoption') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.adoption') }}</h2>
                    <p class="mt-7 max-w-[58ch]">{{ $caseStudy['sections']['adoption'] }}</p>
                </article>
            </div>
        </section>

        @if ($caseStudy['evidence'] !== [])
            <section class="section-feature service-explorer">
                <div class="site-container">
                    <p class="signal-label">{{ __('site.work.evidence') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.evidence') }}</h2>
                    <div class="open-list mt-12">
                        @foreach ($caseStudy['evidence'] as $evidence)
                            <article class="open-list__item">
                                <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                                <div>
                                    <p class="signal-label">{{ __('site.work.evidence_kinds.'.$evidence['kind']) }}</p>
                                    <h3 class="mt-3">{{ $evidence['label'] }}</h3>
                                    <p class="mt-3">{{ $evidence['result_text'] }}</p>

                                    @if ($evidence['kind'] !== 'qualitative')
                                        <dl class="service-detail__facts mt-8">
                                            @if ($evidence['baseline_value'] !== null)
                                                <div>
                                                    <dt>{{ __('site.work.baseline') }}</dt>
                                                    <dd><bdi dir="ltr">{{ $evidence['baseline_value'] }} {{ $evidence['unit'] }}</bdi></dd>
                                                </div>
                                            @endif
                                            @if ($evidence['result_value'] !== null)
                                                <div>
                                                    <dt>{{ __('site.work.result') }}</dt>
                                                    <dd><bdi dir="ltr">{{ $evidence['result_value'] }} {{ $evidence['unit'] }}</bdi></dd>
                                                </div>
                                            @endif
                                            @if ($evidence['range_min'] !== null)
                                                <div>
                                                    <dt>{{ __('site.work.range') }}</dt>
                                                    <dd><bdi dir="ltr">{{ $evidence['range_min'] }}–{{ $evidence['range_max'] }} {{ $evidence['unit'] }}</bdi></dd>
                                                </div>
                                            @endif
                                            @if ($evidence['threshold_value'] !== null)
                                                <div>
                                                    <dt>{{ __('site.work.threshold') }}</dt>
                                                    <dd><bdi dir="ltr">{{ $evidence['threshold_value'] }} {{ $evidence['unit'] }}</bdi></dd>
                                                </div>
                                            @endif
                                            <div>
                                                <dt>{{ __('site.work.method') }}</dt>
                                                <dd>{{ $evidence['method'] }}</dd>
                                            </div>
                                            <div>
                                                <dt>{{ __('site.work.scope') }}</dt>
                                                <dd>{{ $evidence['scope'] }}</dd>
                                            </div>
                                            <div>
                                                <dt>{{ __('site.work.period') }}</dt>
                                                <dd>{{ $evidence['result_period'] }}</dd>
                                            </div>
                                        </dl>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="section-standard">
            <div class="site-container service-explorer__grid">
                <article>
                    <p class="signal-label">{{ __('site.work.outcome') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.outcome') }}</h2>
                    <p class="copy-lead mt-7 max-w-[58ch]">{{ $caseStudy['outcome'] }}</p>
                </article>
                <article>
                    <p class="signal-label">{{ __('site.work.lessons') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.lessons') }}</h2>
                    <ul class="service-deliverables mt-7">
                        @foreach ($caseStudy['sections']['lessons'] as $lesson)
                            <li><span aria-hidden="true"></span><strong>{{ $lesson }}</strong></li>
                        @endforeach
                    </ul>
                </article>
            </div>
        </section>

        @if ($relatedServices !== [])
            <section class="section-feature">
                <div class="site-container">
                    <p class="signal-label">{{ __('site.work.related_services') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.related_services') }}</h2>
                    <div class="open-list mt-12">
                        @foreach ($relatedServices as $service)
                            <a
                                href="{{ $service['url'] }}"
                                class="open-list__item"
                                data-analytics-event="service_cta_click"
                                data-analytics-ui-location="project_detail"
                                data-analytics-destination-category="service"
                            >
                                <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                                <h3>{{ $service['name'] }}</h3>
                                <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" aria-hidden="true" />
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($relatedArticles !== [])
            <section class="section-standard">
                <div class="site-container">
                    <p class="signal-label">{{ __('site.work.related_articles') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.work.related_articles') }}</h2>
                    <div class="open-list mt-12">
                        @foreach ($relatedArticles as $article)
                            <a href="{{ $article['url'] }}" class="open-list__item">
                                <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                                <div>
                                    <p class="signal-label">{{ $article['type'] }}</p>
                                    <h3 class="mt-3">{{ $article['title'] }}</h3>
                                    <p class="mt-3">{{ $article['summary'] }}</p>
                                </div>
                                <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" aria-hidden="true" />
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <x-athar.proof :cards="$athar" placement="work" />

        <section class="section-standard">
            <div class="site-container">
                <a
                    href="{{ localized_route('contact') }}#consultation"
                    class="button-primary"
                    data-magnetic
                    data-analytics-event="primary_cta_click"
                    data-analytics-ui-location="project_detail"
                    data-analytics-destination-category="consultation"
                >
                    <span>{{ __('site.actions.free_consultation') }}</span>
                    <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" aria-hidden="true" />
                </a>
            </div>
        </section>
    </article>
</x-layouts.front>
