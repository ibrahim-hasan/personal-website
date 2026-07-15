<x-layouts.front
    :title="__('site.work.title')"
    :description="__('site.work.description')"
    activeMenu="true">

    <section class="page-intro work-intro">
        <div class="site-container page-intro__grid">
            <div>
                <p class="signal-label">{{ __('site.work.eyebrow') }}</p>
                <h1 class="display-page mt-7 max-w-[13ch]">{{ __('site.work.heading') }}</h1>
            </div>
            <p class="copy-lead max-w-[58ch] lg:self-end lg:justify-self-end">{{ __('site.work.body') }}</p>
        </div>
    </section>

    <section class="work-archive bg-canvas-bright" x-data="projectFilter({ projects: @js($work) })">
        <div class="site-container">
            <div class="work-archive__toolbar">
                <p>{{ __('site.work.categories_label') }}</p>
                <div class="filter-bar" role="group" aria-label="{{ __('site.work.categories_label') }}">
                    <button type="button" @click="select('all')" :class="lens === 'all' ? 'is-active' : ''">
                        {{ __('site.work.all') }}
                    </button>
                    @foreach ($lenses as $lens)
                        <button type="button" @click="select(@js($lens['id']))" :class="lens === @js($lens['id']) ? 'is-active' : ''">
                            {{ $lens['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="case-list">
                @foreach ($work as $item)
                    <article
                        class="case-study case-study--real"
                        x-show="matches(@js($item['lens']))"
                        x-transition.opacity.duration.300ms
                        style="--reveal-index: {{ $loop->index }}"
                        data-reveal="case"
                    >
                        <figure class="case-study__media" data-depth="media">
                            <img
                                src="{{ asset($item['image']) }}"
                                alt="{{ $item['alt'] }}"
                                width="1400"
                                height="900"
                                loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                                decoding="async"
                                class="case-study__image"
                            >
                            <span class="project-brand">
                                <img
                                    src="{{ asset($item['logo']) }}"
                                    alt="{{ $item['logo_alt'] }}"
                                    loading="lazy"
                                    decoding="async"
                                    class="project-brand__logo"
                                >
                            </span>
                            <figcaption>
                                <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                                <span>{{ $item['sector'] }}</span>
                            </figcaption>
                        </figure>

                        <div class="case-study__copy">
                            <div class="case-study__meta">
                                <span>{{ $item['sector'] }}</span>
                                <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                            </div>
                            <h2>{{ $item['title'] }}</h2>
                            <p>{{ $item['summary'] }}</p>

                            <dl class="case-study__story">
                                <div>
                                    <dt>{{ __('site.work.challenge') }}</dt>
                                    <dd>{{ $item['challenge'] }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('site.work.response') }}</dt>
                                    <dd>{{ $item['response'] }}</dd>
                                </div>
                                <div class="case-study__outcome">
                                    <dt>{{ __('site.work.outcome') }}</dt>
                                    <dd>{{ $item['outcome'] }}</dd>
                                </div>
                            </dl>

                            <ul class="tag-list" aria-label="{{ __('site.work.sector') }}">
                                @foreach ($item['tags'] as $tag)
                                    <li>{{ $tag }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section-standard">
        <div class="site-container editorial-sidebar">
            <div class="editorial-sidebar__intro">
                <p class="signal-label">{{ __('site.work.entry_eyebrow') }}</p>
                <h2 class="display-section mt-6 max-w-[13ch]" data-reveal="headline">{{ __('site.work.entry_title') }}</h2>
            </div>
            <div class="open-list">
                @foreach ($services as $service)
                    <a href="{{ localized_route('services') }}#{{ $service['id'] }}" class="open-list__item" style="--reveal-index: {{ $loop->index }}" data-reveal="row">
                        <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                        <div>
                            <h3>{{ $service['name'] }}</h3>
                            <p>{{ $service['problem'] }}</p>
                        </div>
                        <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" />
                    </a>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.front>
