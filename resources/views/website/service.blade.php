<x-layouts.front
    :title="$service['seo_title']"
    :description="$service['seo_description']"
    :canonicalUrl="$canonicalUrl"
    :alternateUrls="$alternateUrls"
    schemaType="Service"
    :structuredData="$structuredData"
    activeMenu="true"
    suppressTerminalCta>

    <article class="service-page">
        <header class="page-intro service-intro">
            <div class="site-container page-intro__grid">
                <div>
                    <nav aria-label="{{ __('site.services.breadcrumb_label') }}">
                        <a href="{{ localized_route('services') }}" class="text-link">
                            <x-phosphor-arrow-left class="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                            <span>{{ __('site.services.back_to_index') }}</span>
                        </a>
                    </nav>
                    <p class="signal-label mt-10">{{ __('site.services.eyebrow') }}</p>
                    <h1 class="display-page mt-7 max-w-[13ch]">{{ $service['name'] }}</h1>
                </div>
                <div class="lg:self-end lg:justify-self-end">
                    <p class="copy-lead max-w-[58ch]">{{ $service['summary'] }}</p>
                    <a
                        href="{{ localized_route('contact') }}#consultation"
                        class="button-primary mt-10"
                        data-magnetic
                        data-analytics-event="primary_cta_click"
                        data-analytics-ui-location="service_detail"
                        data-analytics-destination-category="consultation"
                    >
                        <span>{{ __('site.actions.free_consultation') }}</span>
                        <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" aria-hidden="true" />
                    </a>
                </div>
            </div>
        </header>

        <section class="section-standard">
            <div class="site-container editorial-sidebar">
                <div class="editorial-sidebar__intro">
                    <p class="signal-label">{{ __('site.services.good_fit_eyebrow') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.services.good_fit_title') }}</h2>
                </div>
                <ul class="open-list" aria-label="{{ __('site.services.good_fit_title') }}">
                    @foreach ($service['fit_signals'] as $signal)
                        <li class="open-list__item">
                            <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                            <p>{{ $signal }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="section-feature service-explorer">
            <div class="site-container service-explorer__grid">
                <div>
                    <p class="signal-label">{{ __('site.services.problem_pattern') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.services.problem_pattern') }}</h2>
                    <p class="copy-lead mt-7 max-w-[58ch]">{{ $service['problem'] }}</p>
                </div>
                <div class="min-w-0">
                    <article class="service-detail pt-0">
                        <p class="signal-label">{{ __('site.services.approach') }}</p>
                        <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.services.approach') }}</h2>
                        <p class="copy-lead mt-7 max-w-[58ch]">{{ $service['approach'] }}</p>

                        <div class="service-deliverables mt-12">
                            <h3>{{ __('site.services.deliverables') }}</h3>
                            <ul>
                                @foreach ($service['deliverables'] as $deliverable)
                                    <li><span aria-hidden="true"></span><strong>{{ $deliverable }}</strong></li>
                                @endforeach
                            </ul>
                        </div>

                        <dl class="service-detail__facts mt-12">
                            <div>
                                <dt>{{ __('site.services.useful_result') }}</dt>
                                <dd>{{ $service['result'] }}</dd>
                            </div>
                            <div>
                                <dt>{{ __('site.services.starting_engagement') }}</dt>
                                <dd>{{ $service['engagement_note'] }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>
            </div>
        </section>

        <section class="method-band">
            <div class="site-container">
                <h2 class="display-section max-w-[15ch] text-canvas">{{ __('site.services.engagement_title') }}</h2>
                <div class="method-band__steps mt-16">
                    @foreach ($process as $step)
                        <article>
                            <span class="method-band__step-number">{{ $step['step'] }}</span>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($relatedProjects !== [])
            <section class="section-standard">
                <div class="site-container">
                    <p class="signal-label">{{ __('site.services.related_projects') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.services.related_projects') }}</h2>
                    <div class="open-list mt-12">
                        @foreach ($relatedProjects as $project)
                            <a href="{{ $project['url'] }}" class="open-list__item">
                                <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                                <div>
                                    <h3>{{ $project['title'] }}</h3>
                                    <p class="mt-3">{{ $project['summary'] }}</p>
                                </div>
                                <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" aria-hidden="true" />
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($relatedArticles !== [])
            <section class="section-feature">
                <div class="site-container">
                    <p class="signal-label">{{ __('site.services.related_articles') }}</p>
                    <h2 class="display-section mt-6 max-w-[13ch]">{{ __('site.services.related_articles') }}</h2>
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

        <x-athar.proof :cards="$athar" placement="services" />

        <section class="section-standard">
            <div class="site-container">
                <a
                    href="{{ localized_route('contact') }}#consultation"
                    class="button-primary"
                    data-magnetic
                    data-analytics-event="primary_cta_click"
                    data-analytics-ui-location="service_detail"
                    data-analytics-destination-category="consultation"
                >
                    <span>{{ __('site.actions.free_consultation') }}</span>
                    <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" aria-hidden="true" />
                </a>
            </div>
        </section>
    </article>
</x-layouts.front>
