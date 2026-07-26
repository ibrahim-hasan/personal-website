<x-layouts.front
    :title="__('site.services.title')"
    :description="__('site.services.description')"
    activeMenu="true">

    <section class="page-intro page-intro--violet">
        <div class="site-container page-intro__grid">
            <div>
                <p class="signal-label signal-label--light">{{ __('site.services.eyebrow') }}</p>
                <h1 class="display-page mt-7 max-w-[13ch] text-canvas">{{ __('site.services.heading') }}</h1>
            </div>
            <p class="copy-lead max-w-[58ch] text-violet-100 lg:self-end lg:justify-self-end">{{ __('site.services.body') }}</p>
        </div>
    </section>

    <section class="section-feature service-explorer">
        @if ($services !== [])
            <div class="site-container service-explorer__grid">
                <nav class="service-index" aria-label="{{ __('site.services.tabs_label') }}">
                    @foreach ($services as $service)
                        @php($serviceAnchor = 'service-'.($service['key'] ?? $service['id']))

                        <a href="#{{ $serviceAnchor }}" class="service-index__item">
                            <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                            <strong>{{ $service['name'] }}</strong>
                            <i aria-hidden="true"></i>
                        </a>
                    @endforeach
                </nav>

                <div class="min-w-0">
                    @foreach ($services as $service)
                        @php($serviceAnchor = 'service-'.($service['key'] ?? $service['id']))

                        <article
                            id="{{ $serviceAnchor }}"
                            class="service-detail scroll-mt-32 {{ $loop->first ? 'pt-0' : 'mt-16 border-t border-ink/20 pt-16' }}"
                            aria-labelledby="{{ $serviceAnchor }}-title"
                        >
                            <p class="signal-label">{{ sprintf('%02d', $loop->iteration) }}</p>
                            <h2 id="{{ $serviceAnchor }}-title" class="display-section mt-6 max-w-[13ch]">
                                @if (filled($service['detail_url'] ?? null))
                                    <a
                                        href="{{ $service['detail_url'] }}"
                                        data-analytics-event="service_cta_click"
                                        data-analytics-destination-category="service"
                                        data-analytics-service-slug="{{ $service['key'] ?? $service['id'] }}"
                                    >{{ $service['name'] }}</a>
                                @else
                                    {{ $service['name'] }}
                                @endif
                            </h2>
                            <p class="copy-lead mt-7 max-w-[58ch]">{{ $service['summary'] }}</p>

                            <dl class="service-detail__facts mt-12">
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

                            @if ($service['deliverables'] !== [])
                                <div class="service-deliverables mt-12">
                                    <h3>{{ __('site.services.deliverables') }}</h3>
                                    <ul>
                                        @foreach ($service['deliverables'] as $deliverable)
                                            <li><span aria-hidden="true"></span><strong>{{ $deliverable }}</strong></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </article>
                    @endforeach

                    <a
                        href="{{ localized_route('contact') }}#consultation"
                        class="button-primary mt-12"
                        data-magnetic
                        data-analytics-event="primary_cta_click"
                        data-analytics-destination-category="consultation"
                    >
                        <span>{{ __('site.actions.free_consultation') }}</span>
                        <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
                    </a>
                </div>
            </div>
        @else
            <div class="site-container">
                <x-partials.content-empty
                    :eyebrow="__('site.services.empty_eyebrow')"
                    :title="__('site.services.empty_title')"
                    :body="__('site.services.empty_body')"
                    :action-url="localized_route('contact').'#consultation'"
                    :action-label="__('site.actions.start_project')"
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
