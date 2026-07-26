@persist('article-audio-player')
    <section
        class="site-audio-player"
        data-site-audio-player
        data-status-loading="{{ __('articles.reader.loading') }}"
        data-status-playing="{{ __('articles.reader.playing') }}"
        data-status-paused="{{ __('articles.reader.paused') }}"
        data-status-finished="{{ __('articles.reader.finished') }}"
        data-status-error="{{ __('articles.reader.error') }}"
        data-status-unavailable="{{ __('articles.reader.unavailable') }}"
        data-status-ready="{{ __('articles.reader.ready') }}"
        data-label-listen="{{ __('articles.reader.listen') }}"
        data-label-resume="{{ __('articles.reader.resume') }}"
        data-label-pause="{{ __('articles.reader.pause') }}"
        data-label-continue-reading="{{ __('articles.reader.continue_reading') }}"
        data-label-close="{{ __('articles.reader.close') }}"
        data-player-label="{{ __('articles.reader.audio_player') }}"
        hidden
        aria-label="{{ __('articles.reader.audio_player') }}"
    >
        <div class="site-container">
            <div class="site-audio-player__surface">
                <div class="site-audio-player__heading">
                    <span class="site-audio-player__eyebrow">{{ __('articles.reader.now_listening') }}</span>
                    <p data-site-audio-title dir="auto">{{ __('articles.reader.listen') }}</p>
                    <span data-site-audio-status aria-live="polite">{{ __('articles.reader.ready') }}</span>
                    <a class="site-audio-player__article-link" data-site-audio-article-link data-no-navigate hidden>
                        <span data-site-audio-article-link-label>{{ __('articles.reader.continue_reading') }}</span>
                        <x-phosphor-arrow-up-right class="h-3.5 w-3.5 rtl:-rotate-90" aria-hidden="true" />
                    </a>
                </div>

                <div class="site-audio-player__transport">
                    <button
                        type="button"
                        class="site-audio-player__seek"
                        data-site-audio-back
                        aria-label="{{ __('articles.reader.back_15') }}"
                        title="{{ __('articles.reader.back_15') }}"
                    >
                        <x-phosphor-arrow-counter-clockwise class="h-4 w-4" aria-hidden="true" />
                        <span class="site-audio-player__seek-seconds" aria-hidden="true">15</span>
                    </button>

                    <button
                        type="button"
                        class="site-audio-player__toggle"
                        data-site-audio-toggle
                        aria-label="{{ __('articles.reader.listen') }}"
                        title="{{ __('articles.reader.listen') }}"
                    >
                        <x-phosphor-play class="h-5 w-5" data-site-audio-play-icon />
                        <x-phosphor-pause class="h-5 w-5" data-site-audio-pause-icon hidden />
                    </button>

                    <button
                        type="button"
                        class="site-audio-player__seek"
                        data-site-audio-forward
                        aria-label="{{ __('articles.reader.forward_15') }}"
                        title="{{ __('articles.reader.forward_15') }}"
                    >
                        <x-phosphor-arrow-clockwise class="h-4 w-4" aria-hidden="true" />
                        <span class="site-audio-player__seek-seconds" aria-hidden="true">15</span>
                    </button>
                </div>

                <label class="site-audio-player__rate">
                    <span class="sr-only">{{ __('articles.reader.speed') }}</span>
                    <select data-site-audio-rate aria-label="{{ __('articles.reader.speed') }}">
                        <option value="0.85">0.85×</option>
                        <option value="1" selected>1×</option>
                        <option value="1.15">1.15×</option>
                        <option value="1.3">1.3×</option>
                    </select>
                </label>

                <button
                    type="button"
                    class="site-audio-player__close"
                    data-site-audio-close
                    aria-label="{{ __('articles.reader.close') }}"
                    title="{{ __('articles.reader.close') }}"
                >
                    <x-phosphor-x class="h-5 w-5" />
                </button>

                <div class="site-audio-player__timeline">
                    <input
                        type="range"
                        min="0"
                        max="100"
                        step="0.1"
                        value="0"
                        data-site-audio-progress
                        aria-label="{{ __('articles.reader.progress') }}"
                    >
                    <div class="site-audio-player__time" aria-hidden="true">
                        <bdi dir="ltr" data-site-audio-current>00:00</bdi>
                        <bdi dir="ltr" data-site-audio-duration>--:--</bdi>
                    </div>
                </div>

                <audio data-article-audio-element preload="none">
                    {{ __('articles.reader.unsupported') }}
                </audio>
            </div>
        </div>
    </section>
@endpersist
