const READER_STORAGE_KEY = 'ibrahim-site-reader-mode';
const AUDIO_RATE_STORAGE_KEY = 'ibrahim-site-audio-rate';
const AUDIO_STATE_STORAGE_KEY = 'ibrahim-site-audio-state';
let articleReaderController = null;

const safeStorage = {
    get(key) {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // The feature remains available for the current page without persistence.
        }
    },
    remove(key) {
        try {
            window.localStorage.removeItem(key);
        } catch {
            // The feature remains available for the current page without persistence.
        }
    },
};

const formatTime = (seconds) => {
    if (! Number.isFinite(seconds) || seconds < 0) {
        return '--:--';
    }

    const rounded = Math.floor(seconds);
    const hours = Math.floor(rounded / 3600);
    const minutes = Math.floor((rounded % 3600) / 60);
    const remainingSeconds = rounded % 60;

    if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
};

const readAudioState = () => {
    const stored = safeStorage.get(AUDIO_STATE_STORAGE_KEY);

    if (! stored) {
        return null;
    }

    try {
        const state = JSON.parse(stored);

        if (
            typeof state !== 'object'
            || state === null
            || typeof state.url !== 'string'
            || state.initiated !== true
        ) {
            safeStorage.remove(AUDIO_STATE_STORAGE_KEY);

            return null;
        }

        return state;
    } catch {
        return null;
    }
};

const sourceFromElement = (element) => {
    const url = element?.dataset.audioUrl;

    if (! url) {
        return null;
    }

    const durationSeconds = Number.parseFloat(element.dataset.audioDurationSeconds || '');

    const locale = element.dataset.audioLocale || document.documentElement.lang || '';

    return {
        url,
        title: element.dataset.audioTitle || '',
        articleKey: element.dataset.audioArticleKey || '',
        slug: element.dataset.audioArticleSlug || '',
        articleUrl: element.dataset.audioArticleUrl || '',
        locale,
        direction: element.dataset.audioDir === 'rtl' ? 'rtl' : 'ltr',
        durationSeconds: Number.isFinite(durationSeconds) && durationSeconds > 0 ? durationSeconds : 0,
        labels: {
            ready: element.dataset.statusReady || '',
            loading: element.dataset.statusLoading || '',
            playing: element.dataset.statusPlaying || '',
            paused: element.dataset.statusPaused || '',
            finished: element.dataset.statusFinished || '',
            error: element.dataset.statusError || '',
            unavailable: element.dataset.statusUnavailable || '',
            listen: element.dataset.labelListen || '',
            resume: element.dataset.labelResume || '',
            pause: element.dataset.labelPause || '',
            continueReading: element.dataset.labelContinueReading || '',
        },
    };
};

const trackAudioEvent = (eventName, source) => {
    try {
        window.IbrahimAnalytics?.track(eventName, {
            locale: source?.locale || document.documentElement.lang || '',
            page_type: 'article',
            route_key: 'writing.show',
            content_slug: source?.slug || '',
        });
    } catch {
        // Analytics must never affect listening.
    }
};

const initializeReaderMode = (article, signal) => {
    const toggle = article.querySelector('[data-reader-mode-toggle]');

    if (! toggle || toggle.dataset.readerModeInitialized === 'true') {
        return;
    }

    toggle.dataset.readerModeInitialized = 'true';
    const label = toggle.querySelector('span');
    const apply = (enabled) => {
        document.documentElement.toggleAttribute('data-reader-mode', enabled);
        toggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');

        if (label) {
            label.textContent = enabled ? toggle.dataset.exitLabel : toggle.dataset.enterLabel;
        }
    };

    apply(safeStorage.get(READER_STORAGE_KEY) === 'true');

    toggle.addEventListener('click', () => {
        const enabled = ! document.documentElement.hasAttribute('data-reader-mode');

        apply(enabled);
        safeStorage.set(READER_STORAGE_KEY, enabled ? 'true' : 'false');
    }, { signal });
};

const initializeSiteAudioPlayer = (signal) => {
    const player = document.querySelector('[data-site-audio-player]');

    if (! player) {
        return;
    }

    if (player.dataset.audioInitialized === 'true') {
        player.__siteAudioPlayer?.bindLaunches(signal);
        player.__siteAudioPlayer?.hydrate();

        return;
    }

    player.dataset.audioInitialized = 'true';

    const audio = player.querySelector('[data-article-audio-element]');
    const title = player.querySelector('[data-site-audio-title]');
    const status = player.querySelector('[data-site-audio-status]');
    const toggle = player.querySelector('[data-site-audio-toggle]');
    const playIcon = player.querySelector('[data-site-audio-play-icon]');
    const pauseIcon = player.querySelector('[data-site-audio-pause-icon]');
    const back = player.querySelector('[data-site-audio-back]');
    const forward = player.querySelector('[data-site-audio-forward]');
    const close = player.querySelector('[data-site-audio-close]');
    const progress = player.querySelector('[data-site-audio-progress]');
    const current = player.querySelector('[data-site-audio-current]');
    const duration = player.querySelector('[data-site-audio-duration]');
    const rate = player.querySelector('[data-site-audio-rate]');
    const articleLink = player.querySelector('[data-site-audio-article-link]');
    const articleLinkLabel = player.querySelector('[data-site-audio-article-link-label]');

    if (! audio || ! title || ! status || ! toggle || ! back || ! forward || ! progress || ! current || ! duration || ! rate || ! articleLink || ! articleLinkLabel) {
        return;
    }

    let activeSource = null;
    let durationHint = 0;
    let pendingRestoreTime = 0;
    let lastPersistedAt = 0;
    let isPlayerOpen = false;
    let hasUserInitiatedPlayback = false;
    let launchController = null;
    let trackedStartUrl = null;
    let trackedCompleteUrl = null;

    const setStatus = (message) => {
        if (message) {
            status.textContent = message;
        }
    };

    const applySourceLabels = (source) => {
        Object.entries(source?.labels || {}).forEach(([key, value]) => {
            if (! value) {
                return;
            }

            const capitalized = `${key.charAt(0).toUpperCase()}${key.slice(1)}`;

            if (['ready', 'loading', 'playing', 'paused', 'finished', 'error', 'unavailable'].includes(key)) {
                player.dataset[`status${capitalized}`] = value;
            } else {
                player.dataset[`label${capitalized}`] = value;
            }
        });
    };

    const applyTrackLanguage = (element, source) => {
        const locale = source?.locale || document.documentElement.lang || '';
        const direction = source?.direction === 'rtl' ? 'rtl' : 'ltr';

        if (locale) {
            element.setAttribute('lang', locale);
        } else {
            element.removeAttribute('lang');
        }

        element.setAttribute('dir', direction);
    };

    const applySourcePresentation = (source) => {
        const sourceTitle = source?.title || player.dataset.playerLabel || '';
        const articleUrl = source?.articleUrl || '';
        const continueReading = player.dataset.labelContinueReading || '';

        title.textContent = sourceTitle;
        applyTrackLanguage(title, source);
        applyTrackLanguage(audio, source);

        articleLinkLabel.textContent = continueReading;
        articleLink.toggleAttribute('hidden', ! articleUrl);

        if (! articleUrl) {
            articleLink.removeAttribute('href');
            articleLink.removeAttribute('aria-label');

            return;
        }

        articleLink.href = articleUrl;
        articleLink.setAttribute(
            'aria-label',
            [continueReading, sourceTitle].filter(Boolean).join(': '),
        );
        applyTrackLanguage(articleLink, source);
    };

    const resetSourcePresentation = () => {
        title.textContent = player.dataset.labelListen || player.dataset.playerLabel || '';
        title.removeAttribute('lang');
        title.setAttribute('dir', 'auto');
        audio.removeAttribute('lang');
        audio.removeAttribute('dir');
        articleLink.hidden = true;
        articleLink.removeAttribute('href');
        articleLink.removeAttribute('aria-label');
        articleLink.removeAttribute('lang');
        articleLink.removeAttribute('dir');
        articleLinkLabel.textContent = player.dataset.labelContinueReading || '';
    };

    const effectiveDuration = () => {
        const nativeDuration = Number.isFinite(audio.duration) && audio.duration > 0 ? audio.duration : 0;

        return durationHint > 0 ? durationHint : nativeDuration;
    };

    const setPlayerVisibility = (visible) => {
        player.toggleAttribute('hidden', ! visible);
        document.documentElement.classList.toggle('has-site-audio-player', visible);
    };

    const updateToggle = () => {
        const playing = ! audio.paused && ! audio.ended;
        const labelText = playing
            ? player.dataset.labelPause
            : (audio.currentTime > 0 ? player.dataset.labelResume : player.dataset.labelListen);

        toggle.setAttribute('aria-label', labelText || '');
        toggle.setAttribute('title', labelText || '');
        playIcon?.toggleAttribute('hidden', playing);
        pauseIcon?.toggleAttribute('hidden', ! playing);
        player.classList.toggle('is-playing', playing);
        setPlayerVisibility(isPlayerOpen);
    };

    const updateSeekControls = () => {
        const total = effectiveDuration();
        const canSeek = isPlayerOpen && activeSource !== null && total > 0;

        back.disabled = ! canSeek || audio.currentTime <= 0;
        forward.disabled = ! canSeek || audio.currentTime >= total;
    };

    const updateProgress = () => {
        const total = effectiveDuration();
        const percentage = total > 0 ? Math.min((audio.currentTime / total) * 100, 100) : 0;

        progress.value = percentage.toString();
        progress.style.setProperty('--audio-progress', `${percentage}%`);
        progress.setAttribute('aria-valuetext', `${formatTime(audio.currentTime)} / ${formatTime(total)}`);
        progress.disabled = total <= 0;
        current.textContent = formatTime(audio.currentTime);
        duration.textContent = formatTime(total);
        updateSeekControls();
    };

    const persistState = (force = false) => {
        if (! activeSource || ! isPlayerOpen || ! hasUserInitiatedPlayback) {
            return;
        }

        const now = Date.now();

        if (! force && now - lastPersistedAt < 750) {
            return;
        }

        lastPersistedAt = now;
        safeStorage.set(AUDIO_STATE_STORAGE_KEY, JSON.stringify({
            ...activeSource,
            initiated: true,
            currentTime: Number.isFinite(audio.currentTime) ? audio.currentTime : 0,
            playbackRate: audio.playbackRate,
        }));
    };

    const applyPendingRestoreTime = () => {
        if (pendingRestoreTime <= 0 || audio.readyState < 1) {
            return;
        }

        const total = effectiveDuration();
        const maximum = total > 0 ? Math.max(total - 0.25, 0) : pendingRestoreTime;

        try {
            audio.currentTime = Math.min(pendingRestoreTime, maximum);
        } catch {
            // The browser may reject seeking until the first media frame is available.
        }

        pendingRestoreTime = 0;
    };

    const normalizedPlaybackRate = (value) => {
        const playbackRate = Number.parseFloat(value);

        return Array.from(rate.options).some((option) => Number.parseFloat(option.value) === playbackRate)
            ? playbackRate
            : 1;
    };

    const activateSource = (source, {
        autoplay = false,
        reset = false,
        restoreTime = 0,
        open = false,
        initiatedByUser = false,
    } = {}) => {
        if (! source) {
            setStatus(player.dataset.statusUnavailable || player.dataset.statusError || '');

            return;
        }

        if (initiatedByUser) {
            hasUserInitiatedPlayback = true;
        }

        if (open) {
            isPlayerOpen = true;
        }

        const isSameSource = activeSource?.url === source.url;

        if (! isSameSource) {
            audio.pause();
        }

        if (! isSameSource || reset) {
            trackedStartUrl = null;
            trackedCompleteUrl = null;
        }

        activeSource = source;
        durationHint = source.durationSeconds || 0;
        applySourceLabels(source);
        applySourcePresentation(source);

        if (! isSameSource) {
            audio.src = source.url;
            audio.load();
            const playbackRate = normalizedPlaybackRate(source.playbackRate ?? safeStorage.get(AUDIO_RATE_STORAGE_KEY) ?? '1');

            audio.playbackRate = playbackRate;
            rate.value = playbackRate.toString();
            pendingRestoreTime = restoreTime;
        } else if (reset) {
            audio.currentTime = 0;
            pendingRestoreTime = 0;
        } else if (restoreTime > 0) {
            pendingRestoreTime = restoreTime;
            applyPendingRestoreTime();
        }

        updateProgress();
        updateToggle();
        persistState(true);

        if (autoplay) {
            setStatus(player.dataset.statusLoading || '');
            audio.play().catch(() => {
                setStatus(player.dataset.statusError || '');
                updateToggle();
            });
        }
    };

    const hydrate = () => {
        const pageSource = sourceFromElement(document.querySelector('[data-article-audio-source]'));

        if (activeSource) {
            if (pageSource?.url === activeSource.url) {
                applySourceLabels(pageSource);
                applySourcePresentation(pageSource);
            }

            updateProgress();
            updateToggle();

            return;
        }

        const stored = readAudioState();

        if (stored?.url) {
            activateSource({
                url: stored.url,
                title: stored.title || '',
                articleKey: stored.articleKey || '',
                slug: stored.slug || '',
                articleUrl: stored.articleUrl || '',
                locale: stored.locale || '',
                direction: stored.direction === 'rtl' ? 'rtl' : 'ltr',
                durationSeconds: Number(stored.durationSeconds) || 0,
                playbackRate: Number(stored.playbackRate) || 1,
                labels: stored.labels || {},
            }, {
                restoreTime: Number(stored.currentTime) || 0,
                open: true,
                initiatedByUser: true,
            });

            return;
        }

        isPlayerOpen = false;
        resetSourcePresentation();
        setStatus(player.dataset.statusReady || '');
        updateProgress();
        updateToggle();
    };

    toggle.addEventListener('click', () => {
        if (! activeSource) {
            return;
        }

        if (! audio.paused && ! audio.ended) {
            audio.pause();

            return;
        }

        if (audio.ended) {
            audio.currentTime = 0;
        }

        setStatus(player.dataset.statusLoading || '');
        audio.play().catch(() => {
            setStatus(player.dataset.statusError || '');
            updateToggle();
        });
    });

    const seekBy = (seconds) => {
        const total = effectiveDuration();

        if (total <= 0) {
            return;
        }

        try {
            audio.currentTime = Math.min(Math.max(audio.currentTime + seconds, 0), total);
        } catch {
            return;
        }

        updateProgress();
        persistState(true);
    };

    back.addEventListener('click', () => seekBy(-15));
    forward.addEventListener('click', () => seekBy(15));

    close?.addEventListener('click', () => {
        isPlayerOpen = false;
        hasUserInitiatedPlayback = false;
        audio.pause();
        audio.removeAttribute('src');
        audio.load();
        activeSource = null;
        durationHint = 0;
        pendingRestoreTime = 0;
        trackedStartUrl = null;
        trackedCompleteUrl = null;
        audio.playbackRate = 1;
        rate.value = '1';
        safeStorage.remove(AUDIO_STATE_STORAGE_KEY);
        safeStorage.remove(AUDIO_RATE_STORAGE_KEY);
        resetSourcePresentation();
        setStatus(player.dataset.statusReady || '');
        updateProgress();
        updateToggle();
    });

    progress.addEventListener('input', () => {
        const total = effectiveDuration();

        if (total > 0) {
            audio.currentTime = (Number.parseFloat(progress.value) / 100) * total;
            persistState(true);
        }
    });

    rate.addEventListener('change', () => {
        const playbackRate = normalizedPlaybackRate(rate.value);

        audio.playbackRate = playbackRate;
        rate.value = playbackRate.toString();
        safeStorage.set(AUDIO_RATE_STORAGE_KEY, playbackRate.toString());
        persistState(true);
    });

    audio.addEventListener('loadedmetadata', () => {
        applyPendingRestoreTime();
        updateProgress();
    });
    audio.addEventListener('durationchange', updateProgress);
    audio.addEventListener('loadeddata', updateProgress);
    audio.addEventListener('canplay', () => {
        player.classList.remove('is-buffering');
        updateProgress();
    });
    audio.addEventListener('timeupdate', () => {
        updateProgress();
        persistState();
    });
    audio.addEventListener('waiting', () => {
        player.classList.add('is-buffering');
        setStatus(player.dataset.statusLoading || '');
    });
    audio.addEventListener('play', () => {
        player.classList.remove('is-buffering');
        setStatus(player.dataset.statusPlaying || '');
        updateToggle();
        persistState(true);

        if (activeSource && trackedStartUrl !== activeSource.url) {
            trackAudioEvent('audio_start', activeSource);
            trackedStartUrl = activeSource.url;
        }
    });
    audio.addEventListener('pause', () => {
        if (! audio.ended) {
            setStatus(player.dataset.statusPaused || '');
        }

        updateToggle();
        persistState(true);
    });
    audio.addEventListener('ended', () => {
        setStatus(player.dataset.statusFinished || '');
        updateToggle();
        updateProgress();
        persistState(true);

        if (activeSource && trackedCompleteUrl !== activeSource.url) {
            trackAudioEvent('audio_complete', activeSource);
            trackedCompleteUrl = activeSource.url;
        }
    });
    audio.addEventListener('error', () => {
        player.classList.remove('is-buffering');
        setStatus(player.dataset.statusUnavailable || player.dataset.statusError || '');
        updateToggle();
    });

    const bindLaunches = (navigationSignal) => {
        launchController?.abort();
        launchController = new AbortController();

        navigationSignal?.addEventListener('abort', () => launchController?.abort(), { once: true });
        document.addEventListener('click', (event) => {
            if (! (event.target instanceof Element)) {
                return;
            }

            const launch = event.target.closest('[data-article-audio-launch]');
            const sourceElement = launch?.closest('[data-article-audio-source]');

            if (! launch || ! sourceElement) {
                return;
            }

            event.preventDefault();
            const source = sourceFromElement(sourceElement);
            const shouldReset = activeSource?.url !== source?.url || audio.ended;

            activateSource(source, {
                autoplay: true,
                reset: shouldReset,
                open: true,
                initiatedByUser: true,
            });
        }, { signal: launchController.signal });
    };

    window.addEventListener('pagehide', () => persistState(true));

    player.__siteAudioPlayer = {
        activateSource,
        bindLaunches,
        hydrate,
        persist: () => persistState(true),
    };
    bindLaunches(signal);
    hydrate();
    updateToggle();
    updateProgress();
};

const initializeArticleReaders = () => {
    articleReaderController?.abort();
    articleReaderController = new AbortController();

    document.querySelectorAll('[data-article-page]').forEach((article) => initializeReaderMode(
        article,
        articleReaderController.signal,
    ));
    initializeSiteAudioPlayer(articleReaderController.signal);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeArticleReaders, { once: true });
} else {
    initializeArticleReaders();
}

document.addEventListener('livewire:navigating', () => {
    document.querySelector('[data-site-audio-player]')?.__siteAudioPlayer?.persist();
});

document.addEventListener('livewire:navigated', initializeArticleReaders);
