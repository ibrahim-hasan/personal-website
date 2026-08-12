<x-layouts.front
    :title="__('site.services.title')"
    :description="__('site.services.description')"
    activeMenu="true">

    <section class="page-intro">
        <div class="site-container page-intro__grid">
            <div>
                <p class="signal-label">{{ __('site.services.eyebrow') }}</p>
                <h1 class="display-page mt-7 max-w-[13ch]">{{ __('site.services.heading') }}</h1>
            </div>
            <p class="copy-lead max-w-[58ch] lg:self-end lg:justify-self-end">{{ __('site.services.body') }}</p>
        </div>
    </section>

    <section class="section-feature service-explorer" data-service-hub>
        @if ($services !== [])
            <div class="site-container service-explorer__grid">
                <nav class="service-index" aria-label="{{ __('site.services.tabs_label') }}">
                    @foreach ($services as $service)
                        <a
                            href="#{{ $service['id'] }}"
                            class="service-index__item @if ($loop->first) is-active @endif"
                            data-service-hub-link
                            @if ($loop->first) aria-current="location" @endif
                            data-analytics-event="service_cta_click"
                            data-analytics-ui-location="services_hub"
                            data-analytics-destination-category="service"
                            data-analytics-service-key="{{ $service['key'] }}"
                        >
                            <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                            <strong>{{ $service['name'] }}</strong>
                            <i aria-hidden="true"></i>
                        </a>
                    @endforeach
                </nav>

                <div class="service-hub min-w-0">
                    @foreach ($services as $service)
                        <article id="{{ $service['id'] }}" class="service-hub__entry" data-service-hub-section>
                            <p class="signal-label">{{ __('site.services.selected_track') }}</p>
                            <h2 class="display-section mt-6 max-w-[13ch]">{{ $service['name'] }}</h2>
                            <p class="copy-lead mt-7 max-w-[58ch]">{{ $service['summary'] }}</p>

                            <dl class="service-hub__facts mt-12">
                                <div>
                                    <dt>{{ __('site.services.problem_pattern') }}</dt>
                                    <dd>{{ $service['problem'] }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('site.services.approach') }}</dt>
                                    <dd>{{ $service['approach'] }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('site.services.useful_result') }}</dt>
                                    <dd>{{ $service['result'] }}</dd>
                                </div>
                            </dl>

                            <section class="service-hub__fit mt-12" aria-labelledby="{{ $service['id'] }}-fit">
                                <p class="signal-label">{{ __('site.services.good_fit_eyebrow') }}</p>
                                <h3 id="{{ $service['id'] }}-fit">{{ __('site.services.good_fit_title') }}</h3>
                                <ul>
                                    @foreach ($service['fit_signals'] as $signal)
                                        <li><span aria-hidden="true"></span><strong>{{ $signal }}</strong></li>
                                    @endforeach
                                </ul>
                            </section>

                            <section class="service-deliverables mt-12" aria-labelledby="{{ $service['id'] }}-deliverables">
                                <h3 id="{{ $service['id'] }}-deliverables">{{ __('site.services.deliverables') }}</h3>
                                <ul>
                                    @foreach ($service['deliverables'] as $deliverable)
                                        <li><span aria-hidden="true"></span><strong>{{ $deliverable }}</strong></li>
                                    @endforeach
                                </ul>
                            </section>

                            <aside class="service-hub__note mt-12">
                                <p>{{ __('site.services.starting_engagement') }}</p>
                                <strong>{{ $service['engagement_note'] }}</strong>
                            </aside>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="site-container">
                <a
                    href="{{ localized_route('contact') }}#consultation"
                    class="button-primary mt-12"
                    data-magnetic
                    data-analytics-event="primary_cta_click"
                    data-analytics-ui-location="services_hub_cta"
                    data-analytics-destination-category="consultation"
                >
                    <span>{{ __('site.actions.free_consultation') }}</span>
                    <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
                </a>
            </div>
        @else
            <div class="site-container">
                <x-partials.content-empty
                    :eyebrow="__('site.services.empty_eyebrow')"
                    :title="__('site.services.empty_title')"
                    :body="__('site.services.empty_body')"
                    :action-url="localized_route('contact').'#consultation'"
                    :action-label="__('site.actions.start_project')"
                    analytics-event="primary_cta_click"
                    analytics-ui-location="services_hub_empty"
                    analytics-destination-category="consultation"
                />
            </div>
        @endif
    </section>

    <section class="method-band">
        <div class="site-container">
            <h2 class="display-section max-w-[15ch] text-canvas" data-reveal>{{ __('site.services.engagement_title') }}</h2>
            <div class="method-band__steps mt-16">
                @foreach ($process as $step)
                    <article data-reveal>
                        <div class="method-band__step-head">
                            <span class="method-band__step-number">{{ $step['step'] }}</span>
                            @switch($loop->iteration)
                                @case(1)
                                    <span class="method-band__icon method-band__icon--focus" aria-hidden="true">
                                        <x-phosphor-crosshair />
                                    </span>
                                    @break

                                @case(2)
                                    <span class="method-band__icon method-band__icon--map" aria-hidden="true">
                                        <x-phosphor-graph />
                                    </span>
                                    @break

                                @case(3)
                                    <span class="method-band__icon method-band__icon--prioritize" aria-hidden="true">
                                        <x-phosphor-strategy />
                                    </span>
                                    @break

                                @default
                                    <span class="method-band__icon method-band__icon--measure" aria-hidden="true">
                                        <x-phosphor-chart-line-up />
                                    </span>
                            @endswitch
                        </div>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <x-athar.proof :cards="$athar" placement="services" />

</x-layouts.front>
