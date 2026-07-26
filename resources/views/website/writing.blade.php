@php
    $featuredArticle = $articles[0] ?? null;
@endphp

<x-layouts.front
    :title="__('site.writing.title')"
    :description="__('site.writing.description')"
    :canonicalUrl="localized_route('writing')"
    :robots="$isFiltered ? 'noindex, follow, noarchive' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'"
    schemaType="CollectionPage"
    activeMenu="true">

    <section class="publication-intro">
        <div class="site-container publication-intro__grid">
            <div class="publication-intro__copy">
                <p class="signal-label">{{ __('site.writing.eyebrow') }}</p>
                <h1 class="display-page">{{ __('site.writing.heading') }}</h1>
                <p class="copy-lead">{{ __('site.writing.body') }}</p>
            </div>

            @if ($featuredArticle)
                <a href="{{ $featuredArticle['url'] }}" wire:navigate class="featured-essay">
                <figure>
                    <x-media.responsive-image
                        :image="$featuredArticle['image_media']"
                        :alt="$featuredArticle['image_alt']"
                        sizes="(min-width: 72rem) 43rem, calc(100vw - 2rem)"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                    />
                </figure>
                <div class="featured-essay__copy">
                    <span>{{ $featuredArticle['type'] }} · {{ $featuredArticle['read_time'] }}</span>
                    <h2>{{ $featuredArticle['title'] }}</h2>
                    <p>{{ $featuredArticle['summary'] }}</p>
                    <strong>
                        {{ __('articles.index.read_article') }}
                        <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" />
                    </strong>
                </div>
                </a>
            @else
                <x-partials.content-empty
                    :eyebrow="__('site.writing.empty_eyebrow')"
                    :title="__('site.writing.empty_title')"
                    :body="__('site.writing.empty_body')"
                    :action-url="localized_route('contact').'#consultation'"
                    :action-label="__('site.actions.start_project')"
                />
            @endif
        </div>
    </section>

    @if ($articles !== [])
        <section class="publication-library">
        <div class="site-container">
            <div class="publication-library__toolbar">
                <div>
                    <p>{{ __('articles.index.library') }}</p>
                    <h2>{{ __('articles.index.all_articles') }}</h2>
                </div>
                <div class="publication-topics-shell" data-overflow-rail>
                    <button type="button" class="publication-topics__arrow" data-overflow-rail-previous hidden aria-label="{{ __('articles.index.previous_topics') }}">
                        <x-phosphor-caret-left class="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                    </button>
                    <nav class="publication-topics" data-overflow-rail-scroll aria-label="{{ __('articles.index.filter_by_topic') }}">
                    <span class="publication-topics__edge" data-overflow-rail-start aria-hidden="true"></span>
                    <a
                        href="{{ localized_route('writing') }}"
                        class="{{ $selectedTopic === null ? 'is-active' : '' }}"
                        @if ($selectedTopic === null) aria-current="page" @endif
                    >
                        {{ __('articles.index.all_topics') }}
                    </a>
                    @foreach ($topics as $key => $topic)
                        <a
                            href="{{ localized_route('writing', ['topic' => $key]) }}"
                            class="{{ $selectedTopic === $key ? 'is-active' : '' }}"
                            @if ($selectedTopic === $key) aria-current="page" @endif
                        >
                            {{ $topic }}
                        </a>
                    @endforeach
                    <span class="publication-topics__edge" data-overflow-rail-end aria-hidden="true"></span>
                    </nav>
                    <button type="button" class="publication-topics__arrow" data-overflow-rail-next hidden aria-label="{{ __('articles.index.next_topics') }}">
                        <x-phosphor-caret-right class="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <p class="sr-only" aria-live="polite">{{ trans_choice('articles.index.result_count', count($articles), ['count' => count($articles)]) }}</p>

            <div class="publication-list">
                @foreach ($articles as $article)
                    <a
                        href="{{ $article['url'] }}"
                        wire:navigate
                        class="publication-row"
                        style="--reveal-index: {{ $loop->index }}"
                        data-reveal="editorial-row"
                    >
                        <span class="publication-row__number">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <div class="publication-row__copy">
                            <div class="publication-row__meta">
                                <span class="publication-row__kind">{{ $article['type'] }}</span>
                                <time datetime="{{ $article['published_at'] }}">{{ $article['published_label'] }}</time>
                                <span class="publication-row__read-time">{{ $article['read_time'] }}</span>
                            </div>
                            <h3>{{ $article['title'] }}</h3>
                            <p>{{ $article['summary'] }}</p>
                        </div>
                        <span class="publication-row__arrow" aria-hidden="true">
                            <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" />
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
        </section>
    @elseif ($isFiltered)
        <section class="section-standard">
            <div class="site-container">
                <x-partials.content-empty
                    :eyebrow="__('articles.index.filtered_empty_eyebrow')"
                    :title="__('articles.index.filtered_empty_title')"
                    :body="__('articles.index.filtered_empty_body')"
                    :action-url="localized_route('writing')"
                    :action-label="__('articles.index.clear_filters')"
                />
            </div>
        </section>
    @endif

</x-layouts.front>
