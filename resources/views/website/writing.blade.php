@php
    $featuredArticle = $articles[0];
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
    activeMenu="true">

    <section class="publication-intro">
        <div class="site-container publication-intro__grid">
            <div class="publication-intro__copy">
                <p class="signal-label">{{ __('site.writing.eyebrow') }}</p>
                <h1 class="display-page">{{ __('site.writing.heading') }}</h1>
                <p class="copy-lead">{{ __('site.writing.body') }}</p>
            </div>

            <a href="{{ $featuredArticle['url'] }}" class="featured-essay">
                <figure>
                    <img
                        src="{{ $featuredArticle['image_url'] }}"
                        alt=""
                        width="1200"
                        height="900"
                        fetchpriority="high"
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
        </div>
    </section>

    <section class="publication-library" x-data="articleLibrary">
        <div class="site-container">
            <div class="publication-library__toolbar">
                <div>
                    <p>{{ __('articles.index.library') }}</p>
                    <h2>{{ __('articles.index.all_articles') }}</h2>
                </div>
                <div class="publication-topics" role="group" aria-label="{{ __('articles.index.filter_by_topic') }}">
                    <button type="button" @click="select('all')" :class="active === 'all' ? 'is-active' : ''">
                        {{ __('articles.index.all_topics') }}
                    </button>
                    @foreach ($topicMap as $key => $topic)
                        <button type="button" @click="select(@js($key))" :class="active === @js($key) ? 'is-active' : ''">
                            {{ $topic }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="publication-list">
                @foreach ($articles as $article)
                    <a
                        href="{{ $article['url'] }}"
                        class="publication-row"
                        x-show="matches(@js($article['topic_keys']))"
                        x-transition.opacity.duration.250ms
                        data-reveal
                    >
                        <span class="publication-row__number">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <div class="publication-row__copy">
                            <div>
                                <span>{{ $article['type'] }}</span>
                                <time datetime="{{ $article['published_at'] }}">{{ $article['published_label'] }}</time>
                                <span>{{ $article['read_time'] }}</span>
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

    <section class="statement-band">
        <div class="site-container publication-statement">
            <p class="display-section" data-reveal>{{ __('articles.index.statement') }}</p>
            <a href="{{ localized_route('contact') }}#consultation" class="button-light" data-magnetic>
                <span>{{ __('site.actions.free_consultation') }}</span>
                <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" />
            </a>
        </div>
    </section>

</x-layouts.front>
