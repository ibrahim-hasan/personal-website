@persist('article-audio-player')
    <section
        class="site-audio-player"
        data-site-audio-player
        data-status-loading="{{ __('articles.reader.loading') }}"
        data-status-playing="{{ __('articles.reader.playing') }}"
        data-status-paused="{{ __('articles.reader.paused') }}"
        data-status-finished="{{ __('articles.reader.finished') }}"
        data-status-error="{{ __('articles.reader.error') }}"
        data-label-listen="{{ __('articles.reader.listen') }}"
        data-label-resume="{{ __('articles.reader.resume') }}"
        data-label-pause="{{ __('articles.reader.pause') }}"
        data-label-close="{{ __('articles.reader.close') }}"
        data-player-label="{{ __('articles.reader.audio_player') }}"
        hidden
        aria-label="{{ __('articles.reader.audio_player') }}"
    >
        <div class="site-container">
            <div class="site-audio-player__surface">
                <div class="site-audio-player__heading">
                    <span class="site-audio-player__eyebrow">{{ __('articles.reader.now_listening') }}</span>
                    <p data-site-audio-title>{{ __('articles.reader.listen') }}</p>
                    <span data-site-audio-status aria-live="polite">{{ __('articles.reader.ready') }}</span>
                </div>

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

                <audio data-article-audio-element preload="metadata">
                    {{ __('articles.reader.unsupported') }}
                </audio>
            </div>
        </div>
    </section>
@endpersist
