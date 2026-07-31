<x-layouts.front
    :title="__('site.work.title')"
    :description="__('site.work.description')"
    :canonicalUrl="localized_route('work')"
    :robots="$isFiltered ? 'noindex, follow, noarchive' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'"
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

    <section class="work-archive bg-canvas-bright">
        <div class="site-container">
            <div class="work-archive__toolbar">
                <p>{{ __('site.work.categories_label') }}</p>
                <div class="filter-bar-shell" data-overflow-rail>
                    <button type="button" class="filter-bar__arrow" data-overflow-rail-previous hidden aria-label="{{ __('site.work.previous_categories') }}">
                        <x-phosphor-caret-left class="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                    </button>
                    <nav class="filter-bar" data-overflow-rail-scroll aria-label="{{ __('site.work.categories_label') }}">
                    <span class="filter-bar__edge" data-overflow-rail-start aria-hidden="true"></span>
                    <a
                        href="{{ localized_route('work') }}"
                        class="button-quiet min-h-11 px-4 {{ $selectedLens === null ? 'is-active' : '' }}"
                        @if ($selectedLens === null) aria-current="page" @endif
                    >{{ __('site.work.all') }}</a>
                    @foreach ($lenses as $lens)
                        <a
                            href="{{ localized_route('work', ['lens' => $lens['id']]) }}"
                            class="button-quiet min-h-11 px-4 {{ $selectedLens === $lens['id'] ? 'is-active' : '' }}"
                            @if ($selectedLens === $lens['id']) aria-current="page" @endif
                        >{{ $lens['label'] }}</a>
                    @endforeach
                    <span class="filter-bar__edge" data-overflow-rail-end aria-hidden="true"></span>
                    </nav>
                    <button type="button" class="filter-bar__arrow" data-overflow-rail-next hidden aria-label="{{ __('site.work.next_categories') }}">
                        <x-phosphor-caret-right class="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <p class="sr-only" aria-live="polite">{{ trans_choice('site.work.result_count', count($work), ['count' => count($work)]) }}</p>

            @if ($work !== [])
                <div class="case-list">
                @foreach ($work as $item)
                    <article
                        id="project-{{ $item['key'] }}"
                        class="case-study case-study--real scroll-mt-32"
                        style="--reveal-index: {{ $loop->index }}"
                        data-reveal="case"
                    >
                        <figure class="case-study__media" data-depth="media">
                            @if ($item['image'] !== '')
                            <x-media.responsive-image
                                :image="$item['image_media'] ?? null"
                                :src="asset($item['image'])"
                                :alt="$item['alt']"
                                sizes="(min-width: 72rem) 38rem, 100vw"
                                loading="lazy"
                                decoding="async"
                                class="case-study__image"
                            />
                            @else
                                <x-media-placeholder :label="__('site.work.media_withheld')" ratio="wide" />
                            @endif
                        </figure>

                        <div class="case-study__copy">
                            <div class="case-study__identity">
                                <div class="case-study__meta">
                                    <span>{{ $item['sector'] }}</span>
                                    <span>{{ sprintf('%02d', $loop->iteration) }}</span>
                                </div>
                                @if ($item['logo'] !== '')
                                    <span class="case-study__brand">
                                        <x-media.responsive-image
                                            :image="$item['logo_media'] ?? null"
                                            :src="asset($item['logo'])"
                                            :alt="$item['logo_alt']"
                                            sizes="6rem"
                                            loading="lazy"
                                            decoding="async"
                                        />
                                    </span>
                                @endif
                            </div>
                            <h2>
                                @if (filled($item['detail_url'] ?? null))
                                    <a href="{{ $item['detail_url'] }}">{{ $item['title'] }}</a>
                                @else
                                    {{ $item['title'] }}
                                @endif
                            </h2>
                            <p>{{ $item['summary'] }}</p>
                            <ul class="tag-list" aria-label="{{ __('site.work.sector') }}">
                                @foreach ($item['tags'] as $tag)
                                    <li>{{ $tag }}</li>
                                @endforeach
                            </ul>
                        </div>

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
                    </article>
                @endforeach
                </div>
            @else
                <x-partials.content-empty
                    :eyebrow="$isFiltered ? __('site.work.filtered_empty_eyebrow') : __('site.work.empty_eyebrow')"
                    :title="$isFiltered ? __('site.work.filtered_empty_title') : __('site.work.empty_title')"
                    :body="$isFiltered ? __('site.work.filtered_empty_body') : __('site.work.empty_body')"
                    :action-url="localized_route('contact').'#consultation'"
                    :action-label="__('site.actions.start_project')"
                />
            @endif
        </div>
    </section>

    @if ($services !== [])
        <section class="section-standard">
        <div class="site-container editorial-sidebar">
            <div class="editorial-sidebar__intro">
                <p class="signal-label">{{ __('site.work.entry_eyebrow') }}</p>
                <h2 class="display-section mt-6 max-w-[13ch]" data-reveal="headline">{{ __('site.work.entry_title') }}</h2>
            </div>
            <div class="open-list">
                @foreach ($services as $service)
                    <a
                        href="{{ localized_route('services') }}#{{ $service['id'] }}"
                        class="open-list__item"
                        style="--reveal-index: {{ $loop->index }}"
                        data-reveal="row"
                        data-analytics-event="service_cta_click"
                        data-analytics-destination-category="service"
                        data-analytics-service-slug="{{ $service['key'] ?? $service['id'] }}"
                    >
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
    @endif

    <x-athar.proof :cards="$athar" placement="work" />

</x-layouts.front>
