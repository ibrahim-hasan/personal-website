<x-layouts.front
    :title="$article['seo_title']"
    :description="$article['seo_description']"
    :canonicalUrl="$canonicalUrl"
    :alternateUrls="$alternateUrls"
    ogType="article"
    :ogImage="asset($article['image'])"
    :ogImageAlt="$article['title']"
    :publishedAt="$article['published_at']"
    :modifiedAt="$article['modified_at']"
    :articleAuthorUrl="localized_route('about')"
    :articleSection="$article['type']"
    :articleTags="$article['topics']"
    :structuredData="$structuredData"
    bodyClass="article-surface"
    activeMenu="true">

    <article
        class="article-page"
        data-article-page
        data-article-lang="{{ current_locale() }}"
        data-status-loading="{{ __('articles.reader.loading') }}"
        data-status-playing="{{ __('articles.reader.playing') }}"
        data-status-paused="{{ __('articles.reader.paused') }}"
        data-status-finished="{{ __('articles.reader.finished') }}"
        data-status-error="{{ __('articles.reader.error') }}"
        data-label-listen="{{ __('articles.reader.listen') }}"
        data-label-resume="{{ __('articles.reader.resume') }}"
        data-label-pause="{{ __('articles.reader.pause') }}"
    >
        <header class="article-hero">
            <div class="site-container article-hero__grid">
                <div class="article-hero__copy">
                    <a href="{{ localized_route('writing') }}" wire:navigate class="article-back-link">
                        <x-phosphor-arrow-left class="h-4 w-4 rtl:rotate-180" />
                        <span>{{ __('articles.index.back') }}</span>
                    </a>

                    <div class="article-hero__meta">
                        <span>{{ $article['type'] }}</span>
                        <span>{{ $article['read_time'] }}</span>
                    </div>

                    <h1>{{ $article['title'] }}</h1>
                    <p>{{ $article['summary'] }}</p>

                    <div class="article-hero__footer">
                        <time datetime="{{ $article['published_at'] }}">
                            {{ __('articles.reader.published') }} {{ $article['published_label'] }}
                        </time>
                        <ul aria-label="{{ __('articles.index.topics') }}">
                            @foreach ($article['topics'] as $topic)
                                <li>{{ $topic }}</li>
                            @endforeach
                        </ul>
                    </div>

                </div>

                <figure class="article-hero__media">
                    <img
                        src="{{ asset($article['image']) }}"
                        alt="{{ $article['title'] }}"
                        width="1600"
                        height="900"
                        fetchpriority="high"
                        decoding="async"
                    >
                </figure>
            </div>

            <div class="site-container article-share-rail">
                <x-article-share
                    :title="$article['title']"
                    :description="$article['summary']"
                    :canonical-url="$canonicalUrl"
                />
            </div>
        </header>

        <div class="site-container article-layout">
            <aside class="reader-panel" aria-label="{{ __('articles.reader.tools') }}">
                <div class="reader-panel__primary">
                    @if ($articleAudio)
                        <div
                            id="article-audio"
                            class="article-audio article-audio--launcher"
                            data-article-audio-source
                            data-audio-url="{{ $articleAudio['url'] }}"
                            data-audio-title="{{ $article['title'] }}"
                            data-audio-article-key="{{ $articleAudio['article_key'] }}"
                            data-audio-locale="{{ $articleAudio['locale'] }}"
                            data-audio-duration-seconds="{{ $articleAudio['duration_seconds'] ?? '' }}"
                            data-status-loading="{{ __('articles.reader.loading') }}"
                            data-status-playing="{{ __('articles.reader.playing') }}"
                            data-status-paused="{{ __('articles.reader.paused') }}"
                            data-status-finished="{{ __('articles.reader.finished') }}"
                            data-status-error="{{ __('articles.reader.error') }}"
                            data-label-listen="{{ __('articles.reader.listen') }}"
                            data-label-resume="{{ __('articles.reader.resume') }}"
                            data-label-pause="{{ __('articles.reader.pause') }}"
                        >
                            <button
                                type="button"
                                class="article-audio__launch"
                                data-article-audio-launch
                                aria-label="{{ __('articles.reader.listen') }}"
                            >
                                <span class="article-audio__launch-icon" aria-hidden="true">
                                    <x-phosphor-play class="h-5 w-5" />
                                </span>
                                <span>
                                    <strong>{{ __('articles.reader.listen') }}</strong>
                                    <small>{{ __('articles.reader.continue_listening') }}</small>
                                </span>
                                <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" aria-hidden="true" />
                            </button>
                        </div>
                    @endif

                    <button
                        type="button"
                        class="reader-mode-toggle"
                        data-reader-mode-toggle
                        data-enter-label="{{ __('articles.reader.enter_reader') }}"
                        data-exit-label="{{ __('articles.reader.exit_reader') }}"
                        aria-pressed="false"
                    >
                        <x-phosphor-book-open class="h-5 w-5" />
                        <span>{{ __('articles.reader.enter_reader') }}</span>
                    </button>
                </div>

                <nav class="article-contents" aria-label="{{ __('articles.reader.contents') }}">
                    <p>{{ __('articles.reader.contents') }}</p>
                    <ol>
                        @foreach ($article['sections'] as $section)
                            <li>
                                <a href="#article-section-{{ $loop->iteration }}">
                                    <span class="article-contents__number">{{ sprintf('%02d', $loop->iteration) }}</span>
                                    <span class="article-contents__label">{{ preg_replace('/^\s*[\d٠-٩]+[.)-]\s*/u', '', $section['heading']) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            </aside>

            <div class="article-prose" data-article-content>
                <p class="article-prose__lead">{{ $article['lead'] }}</p>

                @foreach ($article['sections'] as $section)
                    <section id="article-section-{{ $loop->iteration }}" class="article-prose__section">
                        <h2>{{ $section['heading'] }}</h2>

                        @foreach ($section['paragraphs'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach

                        @if (! empty($section['points']))
                            <ul>
                                @foreach ($section['points'] as $point)
                                    <li>{{ $point }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if (! empty($section['note']))
                            <aside class="article-note">{{ $section['note'] }}</aside>
                        @endif
                    </section>
                @endforeach

                <footer class="article-prose__closing">
                    <span aria-hidden="true"></span>
                    <p>{{ $article['closing'] }}</p>
                </footer>

                @if ($article['source_url'])
                    <p class="article-source">
                        <a href="{{ $article['source_url'] }}" target="_blank" rel="noopener noreferrer">
                            {{ __('articles.reader.source_linkedin') }}
                            <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
                        </a>
                    </p>
                @endif
            </div>
        </div>
    </article>

    <livewire:website.article-community
        :article-key="$article['key']"
        :return-path="request()->getRequestUri()"
    />

    <section class="related-writing" data-reader-secondary>
        <div class="site-container">
            <div class="related-writing__heading">
                <h2>{{ __('articles.reader.related') }}</h2>
                <a href="{{ localized_route('writing') }}" wire:navigate class="text-link">
                    {{ __('articles.index.view_all') }}
                    <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" />
                </a>
            </div>

            <div class="related-writing__list">
                @foreach ($relatedArticles as $relatedArticle)
                    <a href="{{ $relatedArticle['url'] }}" wire:navigate data-reveal>
                        <span>{{ $relatedArticle['type'] }} · {{ $relatedArticle['read_time'] }}</span>
                        <h3>{{ $relatedArticle['title'] }}</h3>
                        <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" />
                    </a>
                @endforeach
            </div>
        </div>
    </section>

</x-layouts.front>
