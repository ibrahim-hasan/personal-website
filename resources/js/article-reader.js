const READER_STORAGE_KEY = 'ibrahim-site-reader-mode';
const AUDIO_RATE_STORAGE_KEY = 'ibrahim-site-audio-rate';
const AUDIO_STATE_STORAGE_KEY = 'ibrahim-site-audio-state';

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

        return typeof state === 'object' && state !== null && typeof state.url === 'string'
            ? state
            : null;
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

    return {
        url,
        title: element.dataset.audioTitle || '',
        articleKey: element.dataset.audioArticleKey || '',
        locale: element.dataset.audioLocale || document.documentElement.lang || '',
        durationSeconds: Number.isFinite(durationSeconds) && durationSeconds > 0 ? durationSeconds : 0,
        labels: {
            loading: element.dataset.statusLoading || '',
            playing: element.dataset.statusPlaying || '',
            paused: element.dataset.statusPaused || '',
            finished: element.dataset.statusFinished || '',
            error: element.dataset.statusError || '',
            listen: element.dataset.labelListen || '',
            resume: element.dataset.labelResume || '',
            pause: element.dataset.labelPause || '',
        },
    };
};

const initializeReaderMode = (article) => {
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
    });
};

const initializeSiteAudioPlayer = () => {
    const player = document.querySelector('[data-site-audio-player]');

    if (! player) {
        return;
    }

    if (player.dataset.audioInitialized === 'true') {
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
    const close = player.querySelector('[data-site-audio-close]');
    const progress = player.querySelector('[data-site-audio-progress]');
    const current = player.querySelector('[data-site-audio-current]');
    const duration = player.querySelector('[data-site-audio-duration]');
    const rate = player.querySelector('[data-site-audio-rate]');

    if (! audio || ! title || ! status || ! toggle || ! progress || ! current || ! duration || ! rate) {
        return;
    }

    let activeSource = null;
    let durationHint = 0;
    let pendingRestoreTime = 0;
    let lastPersistedAt = 0;

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

            if (['loading', 'playing', 'paused', 'finished', 'error'].includes(key)) {
                player.dataset[`status${capitalized}`] = value;
            } else {
                player.dataset[`label${capitalized}`] = value;
            }
        });
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
        setPlayerVisibility(playing);
    };

    const updateProgress = () => {
        const total = effectiveDuration();
        const percentage = total > 0 ? Math.min((audio.currentTime / total) * 100, 100) : 0;

        progress.value = percentage.toString();
        progress.style.setProperty('--audio-progress', `${percentage}%`);
        progress.setAttribute('aria-valuetext', `${formatTime(audio.currentTime)} / ${formatTime(total)}`);
        current.textContent = formatTime(audio.currentTime);
        duration.textContent = formatTime(total);
    };

    const persistState = (force = false) => {
        if (! activeSource) {
            return;
        }

        const now = Date.now();

        if (! force && now - lastPersistedAt < 750) {
            return;
        }

        lastPersistedAt = now;
        safeStorage.set(AUDIO_STATE_STORAGE_KEY, JSON.stringify({
            ...activeSource,
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

    const activateSource = (source, { autoplay = false, reset = false, restoreTime = 0 } = {}) => {
        if (! source) {
            return;
        }

        const isSameSource = activeSource?.url === source.url;
        activeSource = source;
        durationHint = source.durationSeconds || 0;
        applySourceLabels(source);
        title.textContent = source.title || player.dataset.playerLabel || '';

        if (! isSameSource) {
            audio.pause();
            audio.src = source.url;
            audio.load();
            audio.playbackRate = Number.parseFloat(safeStorage.get(AUDIO_RATE_STORAGE_KEY) || '1') || 1;
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
                locale: stored.locale || '',
                durationSeconds: Number(stored.durationSeconds) || 0,
                labels: stored.labels || {},
            }, { restoreTime: Number(stored.currentTime) || 0 });

            return;
        }

        if (pageSource) {
            activateSource(pageSource);
        }
    };

    toggle.addEventListener('click', () => {
        if (! activeSource) {
            hydrate();
        }

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

    close?.addEventListener('click', () => {
        audio.pause();
        audio.removeAttribute('src');
        audio.load();
        activeSource = null;
        durationHint = 0;
        pendingRestoreTime = 0;
        safeStorage.remove(AUDIO_STATE_STORAGE_KEY);
        setPlayerVisibility(false);
    });

    progress.addEventListener('input', () => {
        const total = effectiveDuration();

        if (total > 0) {
            audio.currentTime = (Number.parseFloat(progress.value) / 100) * total;
            persistState(true);
        }
    });

    rate.addEventListener('change', () => {
        const playbackRate = Number.parseFloat(rate.value) || 1;

        audio.playbackRate = playbackRate;
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
    });
    audio.addEventListener('error', () => {
        player.classList.remove('is-buffering');
        setStatus(player.dataset.statusError || '');
        updateToggle();
    });

    if (player.dataset.audioClickBound !== 'true') {
        player.dataset.audioClickBound = 'true';
        document.addEventListener('click', (event) => {
            const launch = event.target.closest('[data-article-audio-launch]');
            const sourceElement = launch?.closest('[data-article-audio-source]');

            if (! launch || ! sourceElement) {
                return;
            }

            event.preventDefault();
            const source = sourceFromElement(sourceElement);
            const shouldReset = activeSource?.url !== source?.url || audio.ended;

            activateSource(source, { autoplay: true, reset: shouldReset });
        });
    }

    window.addEventListener('pagehide', () => persistState(true));

    player.__siteAudioPlayer = { activateSource, hydrate };
    hydrate();
    updateToggle();
    updateProgress();
};

const initializeArticleReaders = () => {
    document.querySelectorAll('[data-article-page]').forEach((article) => initializeReaderMode(article));
    initializeSiteAudioPlayer();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeArticleReaders, { once: true });
} else {
    initializeArticleReaders();
}

document.addEventListener('livewire:navigated', initializeArticleReaders);
