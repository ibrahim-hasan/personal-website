export const initializeHeroVideos = (signal, {
    documentObject = document,
    navigatorObject = navigator,
    reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)'),
    windowObject = window,
} = {}) => {
    const connection = navigatorObject.connection;
    const shouldRemainStill = reducedMotion.matches || connection?.saveData === true;
    const hasSlowConnection = ['slow-2g', '2g', '3g'].includes(connection?.effectiveType);
    const hasHighQualityViewport = windowObject.matchMedia('(min-width: 64rem), (min-width: 48rem) and (min-resolution: 1.5dppx)').matches;
    const shouldUseHighQualityVideo = ! hasSlowConnection && ! connection?.saveData && hasHighQualityViewport;
    const guestSeenKey = 'ibrahim.hero-video.seen.v1';

    documentObject.querySelectorAll('[data-hero-video]').forEach((video) => {
        const stage = video.closest('.precision-stage__media');
        const finale = stage?.querySelector('[data-hero-video-finale]');
        const playback = stage?.querySelector('[data-hero-video-toggle]');
        const playbackLabel = playback?.querySelector('[data-hero-video-toggle-label]');
        const playIcon = playback?.querySelector('[data-hero-video-play-icon]');
        const pauseIcon = playback?.querySelector('[data-hero-video-pause-icon]');
        const replay = stage?.querySelector('[data-hero-video-replay]');
        let restartFrame = null;
        let sourceLoaded = Boolean(video.currentSrc || video.getAttribute('src'));

        const updatePlaybackState = (isPlaying) => {
            const label = isPlaying ? playback?.dataset.labelPause : playback?.dataset.labelPlay;

            playback?.setAttribute('aria-pressed', String(isPlaying));

            if (label) {
                playback.setAttribute('aria-label', label);

                if (playbackLabel) {
                    playbackLabel.textContent = label;
                }
            }

            if (playIcon) {
                playIcon.hidden = isPlaying;
            }

            if (pauseIcon) {
                pauseIcon.hidden = ! isPlaying;
            }
        };

        const loadVideoSource = () => {
            if (sourceLoaded) {
                return;
            }

            const supportsWebm = video.canPlayType('video/webm; codecs="vp9"') !== '';
            const source = supportsWebm
                ? (shouldUseHighQualityVideo ? video.dataset.webmSrcHigh : video.dataset.webmSrcCompact)
                : (shouldUseHighQualityVideo ? video.dataset.mp4SrcHigh : video.dataset.mp4SrcCompact);

            if (! source) {
                return;
            }

            video.src = source;
            video.load();
            sourceLoaded = true;
        };

        const playVideo = () => {
            loadVideoSource();

            return video.play();
        };

        const hasGuestSeenVideo = () => {
            try {
                return windowObject.sessionStorage.getItem(guestSeenKey) === 'true';
            } catch {
                return false;
            }
        };

        const markVideoSeen = () => {
            if (! video.dataset.viewedUrl) {
                try {
                    windowObject.sessionStorage.setItem(guestSeenKey, 'true');
                } catch {}

                return;
            }

            const csrfToken = documentObject.querySelector('meta[name="csrf-token"]')?.content;

            windowObject.fetch(video.dataset.viewedUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
            }).catch(() => {});
        };

        const showFinale = () => {
            stage?.classList.add('is-complete');
            playback?.setAttribute('hidden', '');
            finale?.setAttribute('aria-hidden', 'false');
            finale?.removeAttribute('inert');
        };

        const hideFinale = () => {
            stage?.classList.remove('is-complete');
            playback?.removeAttribute('hidden');
            finale?.setAttribute('aria-hidden', 'true');
            finale?.setAttribute('inert', '');
        };

        video.muted = true;
        video.loop = false;
        updatePlaybackState(false);

        video.addEventListener('playing', () => {
            stage?.classList.add('is-playing');
            updatePlaybackState(true);
        }, { signal });

        video.addEventListener('pause', () => {
            updatePlaybackState(false);
        }, { signal });

        video.addEventListener('ended', () => {
            updatePlaybackState(false);
            showFinale();
            markVideoSeen();
        }, { signal });

        playback?.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (! video.paused && ! video.ended) {
                video.pause();

                return;
            }

            if (video.ended) {
                video.currentTime = 0;
            }

            hideFinale();

            try {
                await playVideo();
            } catch {
                updatePlaybackState(false);
            }
        }, { signal });

        replay?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (! stage || ! finale) {
                return;
            }

            stage.classList.add('is-restarting');
            hideFinale();
            stage.classList.remove('is-playing');
            video.pause();
            video.currentTime = 0;

            restartFrame = windowObject.requestAnimationFrame(async () => {
                restartFrame = null;

                try {
                    await playVideo();
                } catch {
                    showFinale();
                } finally {
                    stage.classList.remove('is-restarting');
                }
            });
        }, { signal });

        if (shouldRemainStill) {
            video.pause();
            showFinale();

            return;
        }

        const hasSeenVideo = video.dataset.viewed === 'true'
            || (! video.dataset.viewedUrl && hasGuestSeenVideo());

        if (hasSeenVideo) {
            video.pause();
            showFinale();

            return;
        }

        playback?.removeAttribute('hidden');

        signal.addEventListener('abort', () => {
            video.pause();

            if (restartFrame !== null) {
                windowObject.cancelAnimationFrame(restartFrame);
            }
        }, { once: true });
    });
};
