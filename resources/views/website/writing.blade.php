@php
    $featuredArticle = $articles[0] ?? null;
    $topicMap = collect($articles)
        ->flatMap(function (array $article): array {
            return collect($article['topic_keys'])
                ->mapWithKeys(fn (string $key, int $index): array => [$key => $article['topics'][$index]])
                ->all();
        })
        ->unique();
@endphp

<x-layouts.front
    :title="__('site.writing.title')"
    :description="__('site.writing.description')"
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
                    <img
                        src="{{ $featuredArticle['image_url'] }}"
                        alt="{{ $featuredArticle['title'] }}"
                        width="1600"
                        height="900"
                        fetchpriority="high"
                        decoding="async"
                    >
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
        <section class="publication-library" x-data="articleLibrary">
        <div class="site-container">
            <div class="publication-library__toolbar">
                <div>
                    <p>{{ __('articles.index.library') }}</p>
                    <h2>{{ __('articles.index.all_articles') }}</h2>
                </div>
                <div class="publication-topics-shell">
                    <button type="button" class="publication-topics__arrow" @click="scrollTopics(-1)" :disabled="topicCursor === 0" aria-label="{{ __('articles.index.previous_topics') }}">
                        <x-phosphor-caret-left class="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                    </button>
                    <div x-ref="topics" class="publication-topics" role="toolbar" aria-label="{{ __('articles.index.filter_by_topic') }}">
                    <button
                        type="button"
                        @click="select('all')"
                        @keydown="navigate($event)"
                        :aria-pressed="active === 'all'"
                        :tabindex="active === 'all' ? 0 : -1"
                        :class="active === 'all' ? 'is-active' : ''"
                    >
                        {{ __('articles.index.all_topics') }}
                    </button>
                    @foreach ($topicMap as $key => $topic)
                        <button
                            type="button"
                            @click="select(@js($key))"
                            @keydown="navigate($event)"
                            :aria-pressed="active === @js($key)"
                            :tabindex="active === @js($key) ? 0 : -1"
                            :class="active === @js($key) ? 'is-active' : ''"
                        >
                            {{ $topic }}
                        </button>
                    @endforeach
                    </div>
                    <button type="button" class="publication-topics__arrow" @click="scrollTopics(1)" :disabled="topicCursor === topicCount - 1" aria-label="{{ __('articles.index.next_topics') }}">
                        <x-phosphor-caret-right class="h-4 w-4 rtl:rotate-180" aria-hidden="true" />
                    </button>
                </div>
            </div>

            <div class="publication-list">
                @foreach ($articles as $article)
                    <a
                        href="{{ $article['url'] }}"
                        wire:navigate
                        class="publication-row"
                        x-show="matches(@js($article['topic_keys']))"
                        x-transition.opacity.duration.250ms
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
    @endif

</x-layouts.front>
