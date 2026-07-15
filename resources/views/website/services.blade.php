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

    <section class="section-feature service-explorer" x-data="serviceTabs({ services: @js($services) })">
        <div class="site-container service-explorer__grid">
            <div class="service-index" role="tablist" aria-label="{{ __('site.services.tabs_label') }}">
                @foreach ($services as $service)
                    <button
                        id="{{ $service['id'] }}"
                        type="button"
                        role="tab"
                        @click="activate('{{ $service['id'] }}')"
                        :aria-selected="active === '{{ $service['id'] }}'"
                        class="service-index__item"
                        :class="active === '{{ $service['id'] }}' ? 'is-active' : ''"
                    >
                        <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                        <strong>{{ $service['name'] }}</strong>
                        <i aria-hidden="true"></i>
                    </button>
                @endforeach
            </div>

            <article class="service-detail" role="tabpanel" aria-live="polite">
                <p class="signal-label">{{ __('site.services.selected_track') }}</p>
                <h2 class="display-section mt-6 max-w-[13ch]" x-text="current().name"></h2>
                <p class="copy-lead mt-7 max-w-[58ch]" x-text="current().summary"></p>

                <dl class="service-detail__facts mt-12">
                    <div>
                        <dt>{{ __('site.services.problem_pattern') }}</dt>
                        <dd x-text="current().problem"></dd>
                    </div>
                    <div>
                        <dt>{{ __('site.services.approach') }}</dt>
                        <dd x-text="current().approach"></dd>
                    </div>
                    <div>
                        <dt>{{ __('site.services.useful_result') }}</dt>
                        <dd x-text="current().result"></dd>
                    </div>
                </dl>

                <div class="service-deliverables mt-12">
                    <h3>{{ __('site.services.deliverables') }}</h3>
                    <ul>
                        <template x-for="deliverable in current().deliverables" :key="deliverable">
                            <li><span aria-hidden="true"></span><strong x-text="deliverable"></strong></li>
                        </template>
                    </ul>
                </div>

                <a href="{{ localized_route('contact') }}#consultation" class="button-primary mt-12" data-magnetic>
                    <span>{{ __('site.actions.free_consultation') }}</span>
                    <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
                </a>
            </article>
        </div>
    </section>

    <section class="method-band">
        <div class="site-container">
            <h2 class="display-section max-w-[15ch] text-canvas" data-reveal>{{ __('site.services.engagement_title') }}</h2>
            <div class="method-band__steps mt-16">
                @foreach ($process as $step)
                    <article data-reveal>
                        <span>{{ $step['step'] }}</span>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.front>
