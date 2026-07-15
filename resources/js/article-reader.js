const READER_STORAGE_KEY = 'ibrahim-site-reader-mode';
const AUDIO_RATE_STORAGE_KEY = 'ibrahim-site-audio-rate';

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
};

const initializeReaderMode = (article) => {
    const toggle = article.querySelector('[data-reader-mode-toggle]');

    if (! toggle) {
        return;
    }

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

const formatTime = (seconds) => {
    if (! Number.isFinite(seconds) || seconds < 0) {
        return '--:--';
    }

    const rounded = Math.floor(seconds);
    const minutes = Math.floor(rounded / 60);
    const remainingSeconds = rounded % 60;

    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
};

const initializeArticleAudio = (article) => {
    const player = article.querySelector('[data-article-audio-player]');
    const audio = player?.querySelector('[data-article-audio-element]');
    const custom = player?.querySelector('[data-article-audio-custom]');
    const toggle = player?.querySelector('[data-article-audio-toggle]');
    const playIcon = player?.querySelector('[data-article-audio-play-icon]');
    const pauseIcon = player?.querySelector('[data-article-audio-pause-icon]');
    const progress = player?.querySelector('[data-article-audio-progress]');
    const current = player?.querySelector('[data-article-audio-current]');
    const duration = player?.querySelector('[data-article-audio-duration]');
    const rate = player?.querySelector('[data-article-audio-rate]');
    const status = player?.querySelector('[data-article-audio-status]');

    if (! player || ! audio || ! custom || ! toggle || ! progress || ! current || ! duration || ! rate || ! status) {
        return;
    }

    custom.removeAttribute('hidden');
    audio.controls = false;
    audio.classList.add('article-audio__element');

    const storedRate = Number.parseFloat(safeStorage.get(AUDIO_RATE_STORAGE_KEY) || '1');

    if ([0.85, 1, 1.15, 1.3].includes(storedRate)) {
        rate.value = storedRate.toString();
        audio.playbackRate = storedRate;
    }

    const setStatus = (message) => {
        status.textContent = message;
    };

    const updateToggle = () => {
        const playing = ! audio.paused && ! audio.ended;
        const label = playing ? article.dataset.labelPause : (audio.currentTime > 0 ? article.dataset.labelResume : article.dataset.labelListen);

        toggle.setAttribute('aria-label', label || '');
        toggle.setAttribute('title', label || '');
        playIcon?.toggleAttribute('hidden', playing);
        pauseIcon?.toggleAttribute('hidden', ! playing);
        player.classList.toggle('is-playing', playing);
    };

    const updateProgress = () => {
        const percentage = audio.duration > 0 ? (audio.currentTime / audio.duration) * 100 : 0;

        progress.value = percentage.toString();
        progress.style.setProperty('--audio-progress', `${percentage}%`);
        progress.setAttribute('aria-valuetext', `${formatTime(audio.currentTime)} / ${formatTime(audio.duration)}`);
        current.textContent = formatTime(audio.currentTime);
        duration.textContent = formatTime(audio.duration);
    };

    toggle.addEventListener('click', async () => {
        if (! audio.paused && ! audio.ended) {
            audio.pause();

            return;
        }

        if (audio.ended) {
            audio.currentTime = 0;
        }

        setStatus(article.dataset.statusLoading || '');

        try {
            await audio.play();
        } catch {
            setStatus(article.dataset.statusError || '');
            updateToggle();
        }
    });

    progress.addEventListener('input', () => {
        if (audio.duration > 0) {
            audio.currentTime = (Number.parseFloat(progress.value) / 100) * audio.duration;
        }
    });

    rate.addEventListener('change', () => {
        const playbackRate = Number.parseFloat(rate.value) || 1;

        audio.playbackRate = playbackRate;
        safeStorage.set(AUDIO_RATE_STORAGE_KEY, playbackRate.toString());
    });

    audio.addEventListener('loadedmetadata', updateProgress);
    audio.addEventListener('durationchange', updateProgress);
    audio.addEventListener('timeupdate', updateProgress);
    audio.addEventListener('waiting', () => {
        player.classList.add('is-buffering');
        setStatus(article.dataset.statusLoading || '');
    });
    audio.addEventListener('canplay', () => player.classList.remove('is-buffering'));
    audio.addEventListener('play', () => {
        player.classList.remove('is-buffering');
        setStatus(article.dataset.statusPlaying || '');
        updateToggle();
    });
    audio.addEventListener('pause', () => {
        if (! audio.ended) {
            setStatus(article.dataset.statusPaused || '');
        }

        updateToggle();
    });
    audio.addEventListener('ended', () => {
        setStatus(article.dataset.statusFinished || '');
        updateToggle();
        updateProgress();
    });
    audio.addEventListener('error', () => {
        player.classList.remove('is-buffering');
        setStatus(article.dataset.statusError || '');
        updateToggle();
    });

    window.addEventListener('pagehide', () => audio.pause(), { once: true });
    updateToggle();
    updateProgress();
};

const initializeArticleReaders = () => {
    document.querySelectorAll('[data-article-page]').forEach((article) => {
        initializeReaderMode(article);
        initializeArticleAudio(article);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeArticleReaders, { once: true });
} else {
    initializeArticleReaders();
}
