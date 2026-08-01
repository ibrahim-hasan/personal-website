import './cookie-consent';
import './google-analytics';
import './article-reader';

const moveCompositeFocus = (event) => {
    const supportedKeys = ['ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'End', 'Home'];

    if (! supportedKeys.includes(event.key)) {
        return;
    }

    const group = event.currentTarget.closest('[role="group"], [role="tablist"], [role="toolbar"]');
    const controls = [...(group?.querySelectorAll('button:not([disabled])') ?? [])];

    if (controls.length === 0) {
        return;
    }

    const currentIndex = controls.indexOf(event.currentTarget);

    if (currentIndex === -1) {
        return;
    }

    event.preventDefault();

    let nextIndex;

    if (event.key === 'Home') {
        nextIndex = 0;
    } else if (event.key === 'End') {
        nextIndex = controls.length - 1;
    } else {
        const isRtl = window.getComputedStyle(group).direction === 'rtl';
        const isPrevious = event.key === 'ArrowUp'
            || (event.key === 'ArrowLeft' && ! isRtl)
            || (event.key === 'ArrowRight' && isRtl);
        const offset = isPrevious ? -1 : 1;

        nextIndex = (currentIndex + offset + controls.length) % controls.length;
    }

    controls[nextIndex].focus();
    controls[nextIndex].click();
};

const arabicCharacterPattern = /[\u0600-\u06ff\u0750-\u077f\u08a0-\u08ff\ufb50-\ufdff\ufe70-\ufeff]/u;
const letterPattern = /\p{Letter}/u;

const pageDirection = () => {
    const documentDirection = document.documentElement.getAttribute('dir');

    if (documentDirection === 'rtl' || documentDirection === 'ltr') {
        return documentDirection;
    }

    return document.documentElement.lang.toLowerCase().startsWith('ar') ? 'rtl' : 'ltr';
};

const detectTextDirection = (value, fallback) => {
    for (const character of value) {
        if (arabicCharacterPattern.test(character)) {
            return 'rtl';
        }

        if (letterPattern.test(character)) {
            return 'ltr';
        }
    }

    return fallback;
};

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.data('layout', () => ({
        show: false,
        scrolled: false,
        init() {
            const checkScroll = () => {
                this.scrolled = window.scrollY > 24;
            };

            checkScroll();
            window.addEventListener('scroll', checkScroll, { passive: true });
            this.$watch('show', (isOpen) => {
                document.documentElement.classList.toggle('menu-open', isOpen);
            });
        },
        toggle() {
            if (this.show) {
                this.close();

                return;
            }

            this.show = true;
            this.$nextTick(() => this.$refs.mobileMenu?.querySelector('a[href]')?.focus());
        },
        close(restoreFocus = true) {
            if (! this.show) {
                return;
            }

            this.show = false;

            if (restoreFocus) {
                this.$nextTick(() => this.$refs.menuToggle?.focus());
            }
        },
        trapFocus(event) {
            const focusable = [...(this.$refs.mobileMenu?.querySelectorAll('a[href], button:not([disabled])') ?? [])]
                .filter((element) => element.offsetParent !== null);

            if (focusable.length === 0) {
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },
    }));

    Alpine.data('atharProof', ({ count }) => ({
        count,
        active: 0,
        paused: false,
        hoverPaused: false,
        timer: null,
        init() {
            if (this.count > 1 && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.startAutoplay();
            }
        },
        displayPosition() {
            return `${this.active + 1} / ${this.count}`;
        },
        next() {
            this.active = (this.active + 1) % this.count;
        },
        previous() {
            this.active = (this.active - 1 + this.count) % this.count;
        },
        startAutoplay() {
            this.stopAutoplay();
            this.timer = window.setInterval(() => {
                if (! this.paused && ! this.hoverPaused) {
                    this.next();
                }
            }, 7000);
        },
        stopAutoplay() {
            if (this.timer !== null) {
                window.clearInterval(this.timer);
                this.timer = null;
            }
        },
        pauseForHover() {
            this.hoverPaused = true;
        },
        resumeAfterHover() {
            this.hoverPaused = false;
        },
        toggleAutoplay() {
            this.paused = ! this.paused;
        },
    }));

    Alpine.data('atharReflection', ({ max, messages, initial = '', identityDisplay = 'anonymous', displayName = '', displayPosition = '' }) => ({
        max,
        messages,
        text: initial,
        pageDirection: pageDirection(),
        textDirection: pageDirection(),
        identityDisplay,
        displayName,
        displayPosition,
        identityMenuOpen: false,
        identityMenuReady: false,
        count: 0,
        progress: 0,
        message: messages.start,
        init() {
            this.update(this.$refs.field?.value || this.text);
        },
        update(value) {
            this.text = value;
            this.textDirection = detectTextDirection(value, this.pageDirection);
            this.count = [...value].length;
            this.progress = Math.min(100, (this.count / this.max) * 100);
            this.message = this.getMessage();
        },
        getMessage() {
            const ranges = this.max <= 350
                ? [60, 120, 200, 280]
                : [120, 300, 600, 900];

            if (this.count === 0) {
                return this.messages.start;
            }

            if (this.count < ranges[0]) {
                return this.messages.beginning;
            }

            if (this.count < ranges[1]) {
                return this.messages.growing;
            }

            if (this.count < ranges[2]) {
                return this.messages.rich;
            }

            if (this.count < ranges[3]) {
                return this.messages.deep;
            }

            return this.messages.complete;
        },
        formattedCount() {
            return new Intl.NumberFormat(document.documentElement.lang || undefined).format(this.count);
        },
        formattedMax() {
            return new Intl.NumberFormat(document.documentElement.lang || undefined).format(this.max);
        },
        attribution() {
            if (this.identityDisplay === 'anonymous') {
                return '';
            }

            const name = this.identityDisplay === 'first_name'
                ? (this.displayName.trim().split(/\s+/u)[0] ?? '')
                : this.displayName.trim();
            const position = this.displayPosition.trim();

            return [name, position].filter(Boolean).join(' | ');
        },
        enhanceIdentityMenu() {
            this.identityMenuReady = true;
            this.$nextTick(() => this.$refs.identitySelect?.classList.add('is-enhanced'));
        },
        toggleIdentityMenu() {
            this.identityMenuOpen = ! this.identityMenuOpen;
        },
        selectIdentity(value) {
            this.identityDisplay = value;
            this.identityMenuOpen = false;
            this.$nextTick(() => this.$refs.identityTrigger?.focus());
        },
        focusIdentityOption(value) {
            const refs = {
                full_name: 'identityOptionFullName',
                first_name: 'identityOptionFirstName',
                anonymous: 'identityOptionAnonymous',
            };

            this.$nextTick(() => this.$refs[refs[value]]?.focus());
        },
    }));

    Alpine.data('atharAccessFlow', ({ codeSent = false, expiresAt = 0, resendAvailableAt = 0, attemptsRemaining = 0, messages }) => ({
        codeSent: Boolean(codeSent),
        expiresAt: Number(expiresAt),
        resendAvailableAt: Number(resendAvailableAt),
        attemptsRemaining: Number(attemptsRemaining),
        messages,
        requestPending: false,
        notice: { type: '', message: '' },
        fieldErrors: {},
        now: Math.floor(Date.now() / 1000),
        timer: null,
        init() {
            this.tick();
            if (this.codeSent) {
                this.$nextTick(() => this.focusFirstEmpty());
            }
            this.timer = window.setInterval(() => this.tick(), 1000);
        },
        destroy() {
            if (this.timer !== null) {
                window.clearInterval(this.timer);
            }
        },
        tick() {
            this.now = Math.floor(Date.now() / 1000);
        },
        inputs() {
            return Array.from(this.$root.querySelectorAll('[data-athar-code-digit]'));
        },
        normalizeDigits(value) {
            const arabicIndic = '٠١٢٣٤٥٦٧٨٩';
            const easternArabic = '۰۱۲۳۴۵۶۷۸۹';

            return String(value)
                .replace(/[٠-٩]/g, (digit) => String(arabicIndic.indexOf(digit)))
                .replace(/[۰-۹]/g, (digit) => String(easternArabic.indexOf(digit)))
                .replace(/[^0-9]/g, '');
        },
        focusFirstEmpty() {
            const input = this.inputs().find((field) => field.value === '');
            input?.focus();
        },
        distributeDigits(value, startIndex) {
            const digits = this.normalizeDigits(value);
            const inputs = this.inputs();
            if (digits === '' || inputs.length === 0) {
                return;
            }

            digits.slice(0, inputs.length - startIndex).split('').forEach((digit, offset) => {
                inputs[startIndex + offset].value = digit;
            });

            inputs[Math.min(startIndex + digits.length, inputs.length - 1)]?.focus();
        },
        handleInput(event, index) {
            const digits = this.normalizeDigits(event.target.value);
            if (digits.length > 1) {
                this.distributeDigits(digits, index);

                return;
            }

            event.target.value = digits;
            if (digits !== '') {
                this.inputs()[index + 1]?.focus();
            }
        },
        handleKeydown(event, index) {
            const inputs = this.inputs();
            if (event.key === 'Backspace' && event.target.value === '' && index > 0) {
                event.preventDefault();
                inputs[index - 1].value = '';
                inputs[index - 1].focus();
            }
            if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                inputs[index - 1].focus();
            }
            if (event.key === 'ArrowRight' && index < inputs.length - 1) {
                event.preventDefault();
                inputs[index + 1].focus();
            }
        },
        handlePaste(event, index) {
            event.preventDefault();
            this.distributeDigits(event.clipboardData?.getData('text') ?? '', index);
        },
        fieldError(field) {
            const errors = this.fieldErrors[field];

            return Array.isArray(errors) ? (errors[0] ?? '') : (errors ?? '');
        },
        clearFieldErrors() {
            this.fieldErrors = {};
        },
        showNotice(type, message) {
            this.notice = { type, message };
        },
        handleFailure(data) {
            this.fieldErrors = data?.errors ?? {};
            const firstError = Object.values(this.fieldErrors)
                .flatMap((error) => Array.isArray(error) ? error : [error])
                .find((error) => typeof error === 'string' && error !== '');

            this.showNotice('error', data?.message ?? firstError ?? this.messages.request_failed);
        },
        resetCodeInputs() {
            this.inputs().forEach((input) => {
                input.value = '';
            });
        },
        applyChallenge(data) {
            this.codeSent = Boolean(data.code_sent);
            this.expiresAt = Number(data.code_expires_at ?? 0);
            this.resendAvailableAt = Number(data.resend_available_at ?? 0);
            this.attemptsRemaining = Number(data.attempts_remaining ?? 0);
        },
        async requestCode(event) {
            if (this.requestPending) {
                return;
            }

            const form = event.currentTarget;
            this.requestPending = true;
            this.clearFieldErrors();
            this.notice = { type: '', message: '' };

            try {
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (! response.ok) {
                    this.handleFailure(data);

                    return;
                }

                this.applyChallenge(data);
                this.resetCodeInputs();
                this.showNotice('success', data.message ?? this.messages.code_sent);
                this.$nextTick(() => this.focusFirstEmpty());
            } catch {
                this.showNotice('error', this.messages.request_failed);
            } finally {
                this.requestPending = false;
            }
        },
        secondsUntil(timestamp) {
            return Math.max(0, timestamp - this.now);
        },
        formattedDuration(seconds) {
            const minutes = Math.floor(seconds / 60);
            const remainder = String(seconds % 60).padStart(2, '0');

            return `${minutes}:${remainder}`;
        },
        replace(message, token, value) {
            return message.replace(token, value);
        },
        isExpired() {
            return this.expiresAt === 0 || this.secondsUntil(this.expiresAt) === 0;
        },
        isLocked() {
            return this.attemptsRemaining === 0;
        },
        resendIsLocked() {
            return this.secondsUntil(this.resendAvailableAt) > 0;
        },
        verifyIsDisabled() {
            return this.requestPending || this.isExpired() || this.isLocked();
        },
        statusMessage() {
            const status = [];
            const statusMessages = this.messages.code_status;
            const formattedAttempts = new Intl.NumberFormat(document.documentElement.lang || undefined).format(this.attemptsRemaining);

            if (this.isLocked()) {
                status.push(statusMessages.attempts_exhausted);
            } else {
                status.push(this.replace(statusMessages.attempts_remaining, ':count', formattedAttempts));
            }

            status.push(this.isExpired()
                ? statusMessages.expired
                : this.replace(statusMessages.expires_in, ':time', this.formattedDuration(this.secondsUntil(this.expiresAt))));

            if (this.resendIsLocked()) {
                status.push(this.replace(statusMessages.resend_in, ':time', this.formattedDuration(this.secondsUntil(this.resendAvailableAt))));
            }

            return [...status, this.messages.code_help].join(' ');
        },
        resendLabel() {
            if (this.requestPending) {
                return this.messages.resending;
            }

            return this.resendIsLocked()
                ? this.replace(this.messages.code_status.resend_in, ':time', this.formattedDuration(this.secondsUntil(this.resendAvailableAt)))
                : this.messages.resend;
        },
    }));

    Alpine.data('serviceTabs', ({ services }) => ({
        services,
        active: services[0]?.id ?? null,
        init() {
            const requestedService = window.location.hash.slice(1);

            if (this.services.some((service) => service.id === requestedService)) {
                this.active = requestedService;
            }
        },
        activate(id) {
            this.active = id;
            window.history.replaceState(null, '', `#${id}`);
        },
        navigate(event) {
            moveCompositeFocus(event);
        },
        current() {
            return this.services.find((service) => service.id === this.active) ?? this.services[0];
        },
    }));

    Alpine.data('projectFilter', ({ projects }) => ({
        projects,
        lens: 'all',
        lensCursor: 0,
        lensCount: 1,
        init() {
            this.$nextTick(() => {
                this.lensCount = this.$refs.lenses?.children.length ?? 1;
            });
        },
        select(lens) {
            this.lens = lens;
        },
        scrollLenses(direction) {
            const lenses = this.$refs.lenses;

            if (! lenses) {
                return;
            }

            this.lensCursor = Math.min(Math.max(this.lensCursor + direction, 0), this.lensCount - 1);
            const scrollDirection = getComputedStyle(lenses).direction === 'rtl' ? -direction : direction;

            lenses.scrollBy({
                left: scrollDirection * lenses.clientWidth * 0.68,
                behavior: reducedMotion.matches ? 'auto' : 'smooth',
            });
        },
        navigate(event) {
            moveCompositeFocus(event);
        },
        matches(projectLens) {
            return this.lens === 'all' || projectLens === this.lens;
        },
    }));

    Alpine.data('articleLibrary', () => ({
        active: 'all',
        topicCursor: 0,
        topicCount: 1,
        init() {
            this.$nextTick(() => {
                this.topicCount = this.$refs.topics?.children.length ?? 1;
            });
        },
        select(topic) {
            this.active = topic;
        },
        scrollTopics(direction) {
            const topics = this.$refs.topics;

            if (! topics) {
                return;
            }

            this.topicCursor = Math.min(Math.max(this.topicCursor + direction, 0), this.topicCount - 1);
            const scrollDirection = getComputedStyle(topics).direction === 'rtl' ? -direction : direction;

            topics.scrollBy({
                left: scrollDirection * topics.clientWidth * 0.68,
                behavior: reducedMotion.matches ? 'auto' : 'smooth',
            });
        },
        navigate(event) {
            moveCompositeFocus(event);
        },
        matches(topics) {
            return this.active === 'all' || topics.includes(this.active);
        },
    }));
});

const startStandaloneAlpine = async () => {
    if (document.documentElement.dataset.usesLivewire === 'true' || window.Livewire || window.Alpine) {
        return;
    }

    const { default: Alpine } = await import('alpinejs');

    if (window.Livewire || window.Alpine) {
        return;
    }

    window.Alpine = Alpine;
    Alpine.start();
};

void startStandaloneAlpine();

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const initializeHeroVideos = (signal) => {
    const connection = navigator.connection;
    const shouldRemainStill = reducedMotion.matches || connection?.saveData === true;
    const hasSlowConnection = ['slow-2g', '2g', '3g'].includes(connection?.effectiveType);
    const hasHighQualityViewport = window.matchMedia('(min-width: 64rem), (min-width: 48rem) and (min-resolution: 1.5dppx)').matches;
    const shouldUseHighQualityVideo = ! hasSlowConnection && ! connection?.saveData && hasHighQualityViewport;
    const guestSeenKey = 'ibrahim.hero-video.seen.v1';

    document.querySelectorAll('[data-hero-video]').forEach((video) => {
        const stage = video.closest('.precision-stage__media');
        const finale = stage?.querySelector('[data-hero-video-finale]');
        const replay = stage?.querySelector('[data-hero-video-replay]');
        let restartFrame = null;
        let sourceLoaded = false;
        let isVisible = false;
        let autoplayReady = false;
        let autoplayDelay = null;
        let idleCallback = null;

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
                return window.sessionStorage.getItem(guestSeenKey) === 'true';
            } catch {
                return false;
            }
        };

        const markVideoSeen = () => {
            if (! video.dataset.viewedUrl) {
                try {
                    window.sessionStorage.setItem(guestSeenKey, 'true');
                } catch {}

                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            window.fetch(video.dataset.viewedUrl, {
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
            finale?.setAttribute('aria-hidden', 'false');
            finale?.removeAttribute('inert');
        };

        const hideFinale = () => {
            stage?.classList.remove('is-complete');
            finale?.setAttribute('aria-hidden', 'true');
            finale?.setAttribute('inert', '');
        };

        video.muted = true;
        video.loop = false;

        video.addEventListener('playing', () => {
            stage?.classList.add('is-playing');
        }, { signal });

        video.addEventListener('ended', () => {
            showFinale();
            markVideoSeen();
        }, { signal });
        replay?.addEventListener('click', async (event) => {
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

            restartFrame = window.requestAnimationFrame(async () => {
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

        const visibilityObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                isVisible = entry.isIntersecting;

                if (entry.isIntersecting) {
                    if (autoplayReady && ! video.ended && ! stage?.classList.contains('is-complete')) {
                        playVideo().catch(() => {});
                    }

                    return;
                }

                video.pause();
            });
        }, { threshold: 0.25 });

        visibilityObserver.observe(video);

        const allowAutoplay = () => {
            if (signal.aborted) {
                return;
            }

            autoplayReady = true;

            if (isVisible && ! video.ended && ! stage?.classList.contains('is-complete')) {
                playVideo().catch(() => {});
            }
        };
        const scheduleIdlePlayback = () => {
            autoplayDelay = window.setTimeout(() => {
                autoplayDelay = null;

                if ('requestIdleCallback' in window) {
                    idleCallback = window.requestIdleCallback(allowAutoplay, { timeout: 2000 });

                    return;
                }

                allowAutoplay();
            }, 2500);
        };

        if (document.readyState === 'complete') {
            scheduleIdlePlayback();
        } else {
            window.addEventListener('load', scheduleIdlePlayback, { once: true, signal });
        }

        signal.addEventListener('abort', () => {
            visibilityObserver.disconnect();
            video.pause();

            if (restartFrame !== null) {
                window.cancelAnimationFrame(restartFrame);
            }

            if (autoplayDelay !== null) {
                window.clearTimeout(autoplayDelay);
            }

            if (idleCallback !== null && 'cancelIdleCallback' in window) {
                window.cancelIdleCallback(idleCallback);
            }
        }, { once: true });
    });
};

const updateScrollProgress = () => {
    const scrollable = document.documentElement.scrollHeight - window.innerHeight;
    const progress = scrollable > 0 ? Math.min(window.scrollY / scrollable, 1) : 0;

    document.documentElement.style.setProperty('--scroll-progress', progress.toFixed(4));
};

const initializeScrollProgress = (signal) => {
    let scrollProgressFrame = null;

    const queueScrollProgress = () => {
        if (scrollProgressFrame !== null) {
            return;
        }

        scrollProgressFrame = window.requestAnimationFrame(() => {
            scrollProgressFrame = null;
            updateScrollProgress();
        });
    };

    updateScrollProgress();
    window.addEventListener('scroll', queueScrollProgress, { passive: true, signal });
    window.addEventListener('resize', queueScrollProgress, { passive: true, signal });
    signal.addEventListener('abort', () => {
        if (scrollProgressFrame !== null) {
            window.cancelAnimationFrame(scrollProgressFrame);
        }
    }, { once: true });
};

let activeDialogLocks = 0;

const focusableDialogElements = (dialog) => [...dialog.querySelectorAll([
    'a[href]:not([tabindex="-1"])',
    'button:not([disabled]):not([tabindex="-1"])',
    'input:not([disabled]):not([type="hidden"]):not([tabindex="-1"])',
    'select:not([disabled]):not([tabindex="-1"])',
    'textarea:not([disabled]):not([tabindex="-1"])',
    '[tabindex]:not([tabindex="-1"])',
].join(','))].filter((element) => !element.hidden && element.getClientRects().length > 0);

const lockDialogScroll = () => {
    activeDialogLocks += 1;

    if (activeDialogLocks !== 1) {
        return;
    }

    const root = document.documentElement;
    const scrollbarWidth = Math.max(0, window.innerWidth - root.clientWidth);

    root.style.setProperty('--scrollbar-compensation', `${scrollbarWidth}px`);
    root.classList.add('dialog-open');
};

const unlockDialogScroll = () => {
    activeDialogLocks = Math.max(0, activeDialogLocks - 1);

    if (activeDialogLocks !== 0) {
        return;
    }

    const root = document.documentElement;

    root.classList.remove('dialog-open');

    if (!root.classList.contains('menu-open')) {
        root.style.removeProperty('--scrollbar-compensation');
    }
};

const initializeAccessibleDialogs = (signal) => {
    document.querySelectorAll('[data-accessible-dialog]').forEach((dialog) => {
        if (!(dialog instanceof HTMLDialogElement)) {
            return;
        }

        let hasScrollLock = false;
        let returnFocus = null;
        const dialogName = dialog.dataset.dialogName || '';

        const restoreFocus = () => {
            const fallbackTrigger = [...document.querySelectorAll('[data-dialog-trigger]')].find((trigger) => (
                trigger.dataset.dialogTrigger === dialogName
                && trigger.dataset.dialogTriggerKey === returnFocus?.key
            ));
            const trigger = returnFocus?.element?.isConnected
                ? returnFocus.element
                : fallbackTrigger;

            returnFocus = null;
            window.requestAnimationFrame(() => trigger?.focus());
        };

        const finishClose = (shouldRestoreFocus = true) => {
            if (!hasScrollLock) {
                return;
            }

            hasScrollLock = false;
            unlockDialogScroll();

            if (shouldRestoreFocus) {
                restoreFocus();
            } else {
                returnFocus = null;
            }
        };

        const requestServerClose = () => {
            const method = dialog.dataset.dialogCloseMethod;
            const componentId = dialog.closest('[wire\\:id]')?.getAttribute('wire:id');
            const action = method && componentId
                ? window.Livewire?.find(componentId)?.[method]
                : null;

            if (typeof action !== 'function') {
                return;
            }

            try {
                void action().catch(() => {});
            } catch {
                // The native dialog has already been closed for the visitor.
            }
        };

        const close = ({ notifyServer = false } = {}) => {
            if (dialog.open) {
                dialog.close();
            } else {
                finishClose();
            }

            if (notifyServer) {
                requestServerClose();
            }
        };

        const open = (trigger = null) => {
            if (trigger instanceof HTMLElement) {
                returnFocus = {
                    element: trigger,
                    key: trigger.dataset.dialogTriggerKey || '',
                };
            }

            if (!dialog.open) {
                lockDialogScroll();
                hasScrollLock = true;

                try {
                    dialog.showModal();
                } catch {
                    dialog.setAttribute('open', '');
                }
            }

            window.requestAnimationFrame(() => {
                const initialFocus = dialog.querySelector('[data-dialog-initial-focus]')
                    || focusableDialogElements(dialog)[0]
                    || dialog;

                initialFocus.focus();
            });
        };

        const closeEvent = dialog.dataset.dialogCloseEvent;
        const openEvent = dialog.dataset.dialogOpenEvent;
        const onNativeClose = () => finishClose();

        dialog.addEventListener('close', onNativeClose);
        dialog.addEventListener('cancel', (event) => {
            event.preventDefault();
            close({ notifyServer: true });
        }, { signal });
        dialog.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) {
                return;
            }

            if (event.target === dialog) {
                close({ notifyServer: true });

                return;
            }

            if (event.target.closest('[data-dialog-close]')) {
                event.preventDefault();
                close({ notifyServer: true });
            }
        }, { signal });
        dialog.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab' || !dialog.open) {
                return;
            }

            const focusable = focusableDialogElements(dialog);

            if (focusable.length === 0) {
                event.preventDefault();
                dialog.focus();

                return;
            }

            const first = focusable[0];
            const last = focusable.at(-1);
            const activeElement = document.activeElement;

            if (event.shiftKey && (activeElement === first || activeElement === dialog)) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && (activeElement === last || activeElement === dialog)) {
                event.preventDefault();
                first.focus();
            }
        }, { signal });

        if (openEvent) {
            window.addEventListener(openEvent, (event) => {
                const detail = event instanceof CustomEvent ? event.detail : {};
                const commentId = String(detail?.commentId ?? '');
                const trigger = [...document.querySelectorAll('[data-dialog-trigger]')].find((candidate) => (
                    candidate.dataset.dialogTrigger === dialogName
                    && candidate.dataset.dialogTriggerKey === commentId
                ));

                open(trigger);
            }, { signal });
        }

        if (closeEvent) {
            window.addEventListener(closeEvent, () => close(), { signal });
        }

        signal.addEventListener('abort', () => {
            dialog.removeEventListener('close', onNativeClose);

            if (dialog.open) {
                dialog.close();
            }

            finishClose(false);
        }, { once: true });
    });
};

const initializeArticleReadingProgress = (signal) => {
    const community = document.querySelector('[data-article-reading-progress]');
    const article = document.querySelector('[data-article-page]');
    const componentId = community?.getAttribute('wire:id');

    if (!community || !article || !componentId) {
        return;
    }

    let lastSaved = 0;
    let saveTimeout = null;

    const updateProgress = () => {
        const bounds = article.getBoundingClientRect();
        const articleStart = window.scrollY + bounds.top;
        const scrollableHeight = article.offsetHeight - window.innerHeight;
        const percent = scrollableHeight > 0
            ? Math.min(100, Math.max(0, Math.round(((window.scrollY - articleStart) / scrollableHeight) * 100)))
            : 100;

        if (percent < lastSaved + 10 && percent < 95) {
            return;
        }

        const action = window.Livewire?.find(componentId)?.updateProgress;

        if (typeof action !== 'function') {
            return;
        }

        lastSaved = percent;

        try {
            void action(percent).catch(() => {});
        } catch {
            // Progress is an enhancement; a navigation must remain usable if it cannot persist.
        }
    };

    const queueProgressUpdate = () => {
        if (saveTimeout !== null) {
            window.clearTimeout(saveTimeout);
        }

        saveTimeout = window.setTimeout(() => {
            saveTimeout = null;

            if (community.isConnected && article.isConnected) {
                updateProgress();
            }
        }, 500);
    };

    window.addEventListener('scroll', queueProgressUpdate, { passive: true, signal });
    signal.addEventListener('abort', () => {
        if (saveTimeout !== null) {
            window.clearTimeout(saveTimeout);
        }
    }, { once: true });
};

const initializeViewportStack = (signal) => {
    const root = document.documentElement;
    const consent = document.querySelector('[data-cookie-consent]');
    const audio = document.querySelector('[data-site-audio-player]');
    const viewport = window.visualViewport;
    let frame = null;

    const visibleHeight = (surface) => {
        if (! surface || surface.hidden || window.getComputedStyle(surface).display === 'none') {
            return 0;
        }

        return Math.ceil(surface.getBoundingClientRect().height);
    };
    const update = () => {
        frame = null;

        const consentHeight = visibleHeight(consent);
        const audioHeight = visibleHeight(audio);
        const keyboardInset = viewport
            ? Math.max(0, Math.ceil(window.innerHeight - viewport.height - viewport.offsetTop))
            : 0;

        root.style.setProperty('--viewport-consent-height', `${consentHeight}px`);
        root.style.setProperty('--viewport-audio-height', `${audioHeight}px`);
        root.style.setProperty('--viewport-stack-height', `${consentHeight + audioHeight}px`);
        root.style.setProperty('--viewport-keyboard-inset', `${keyboardInset}px`);
    };
    const queueUpdate = () => {
        if (frame === null) {
            frame = window.requestAnimationFrame(update);
        }
    };
    const resizeObserver = 'ResizeObserver' in window ? new ResizeObserver(queueUpdate) : null;
    const mutationObserver = new MutationObserver(queueUpdate);

    [consent, audio].filter(Boolean).forEach((surface) => {
        resizeObserver?.observe(surface);
        mutationObserver.observe(surface, {
            attributes: true,
            attributeFilter: ['hidden', 'class', 'style'],
        });
    });

    update();
    window.addEventListener('resize', queueUpdate, { passive: true, signal });
    window.addEventListener('cookie-consent-visibility-changed', queueUpdate, { signal });
    viewport?.addEventListener('resize', queueUpdate, { passive: true, signal });
    viewport?.addEventListener('scroll', queueUpdate, { passive: true, signal });
    signal.addEventListener('abort', () => {
        if (frame !== null) {
            window.cancelAnimationFrame(frame);
        }

        resizeObserver?.disconnect();
        mutationObserver.disconnect();
    }, { once: true });
};

const initializeOverflowRails = (signal) => {
    document.querySelectorAll('[data-overflow-rail]').forEach((container) => {
        const rail = container.querySelector('[data-overflow-rail-scroll]');
        const previous = container.querySelector('[data-overflow-rail-previous]');
        const next = container.querySelector('[data-overflow-rail-next]');
        const start = container.querySelector('[data-overflow-rail-start]');
        const end = container.querySelector('[data-overflow-rail-end]');

        if (! rail || ! previous || ! next || ! start || ! end) {
            return;
        }

        const visibleEdges = { start: true, end: false };
        let hasOverflow = null;
        let previousDisabled = null;
        let nextDisabled = null;
        let updateFrame = null;

        const updateControls = () => {
            const nextHasOverflow = rail.scrollWidth > rail.clientWidth + 1;
            const nextPreviousDisabled = ! nextHasOverflow || visibleEdges.start;
            const nextNextDisabled = ! nextHasOverflow || visibleEdges.end;

            if (hasOverflow !== nextHasOverflow) {
                hasOverflow = nextHasOverflow;

                if (previous.hidden !== ! nextHasOverflow) {
                    previous.hidden = ! nextHasOverflow;
                }

                if (next.hidden !== ! nextHasOverflow) {
                    next.hidden = ! nextHasOverflow;
                }
            }

            if (previousDisabled !== nextPreviousDisabled) {
                previousDisabled = nextPreviousDisabled;

                if (previous.disabled !== nextPreviousDisabled) {
                    previous.disabled = nextPreviousDisabled;
                }
            }

            if (nextDisabled !== nextNextDisabled) {
                nextDisabled = nextNextDisabled;

                if (next.disabled !== nextNextDisabled) {
                    next.disabled = nextNextDisabled;
                }
            }
        };
        const queueControlUpdate = () => {
            if (signal.aborted || updateFrame !== null) {
                return;
            }

            updateFrame = window.requestAnimationFrame(() => {
                updateFrame = null;

                if (! signal.aborted) {
                    updateControls();
                }
            });
        };
        const updateAfterFontsLoad = () => {
            const fontReady = document.fonts?.ready;

            if (fontReady) {
                fontReady.then(queueControlUpdate).catch(() => {});
            }
        };
        const initializeControls = () => {
            queueControlUpdate();
            updateAfterFontsLoad();
        };
        const scroll = (direction) => {
            const scrollDirection = getComputedStyle(rail).direction === 'rtl' ? -direction : direction;

            rail.scrollBy({
                left: scrollDirection * rail.clientWidth * 0.68,
                behavior: reducedMotion.matches ? 'auto' : 'smooth',
            });
        };
        const edgeObserver = 'IntersectionObserver' in window
            ? new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.target === start) {
                        visibleEdges.start = entry.isIntersecting;
                    }

                    if (entry.target === end) {
                        visibleEdges.end = entry.isIntersecting;
                    }
                });

                queueControlUpdate();
            }, { root: rail, threshold: 1 })
            : null;
        const resizeObserver = 'ResizeObserver' in window ? new ResizeObserver(queueControlUpdate) : null;

        edgeObserver?.observe(start);
        edgeObserver?.observe(end);
        resizeObserver?.observe(container);
        resizeObserver?.observe(rail);
        previous.addEventListener('click', () => scroll(-1), { signal });
        next.addEventListener('click', () => scroll(1), { signal });
        rail.addEventListener('scroll', queueControlUpdate, { passive: true, signal });

        if (document.readyState === 'complete') {
            initializeControls();
        } else {
            window.addEventListener('load', initializeControls, { once: true, signal });
        }

        signal.addEventListener('abort', () => {
            if (updateFrame !== null) {
                window.cancelAnimationFrame(updateFrame);
            }

            edgeObserver?.disconnect();
            resizeObserver?.disconnect();
        }, { once: true });
    });
};

const initializeBackToTop = (signal) => {
    const control = document.querySelector('[data-back-to-top]');

    if (! control) {
        return;
    }

    const footerSafeZone = document.querySelector('[data-back-to-top-safe-zone]');
    let visibilityFrame = null;
    let floatingResizeObserver = null;

    const updateFloatingOffset = () => {
        const footerRect = footerSafeZone?.getBoundingClientRect();
        const footerOffset = footerRect && footerRect.top < window.innerHeight
            ? Math.ceil(window.innerHeight - footerRect.top + 16)
            : 0;

        control.style.setProperty('--floating-footer-offset', `${footerOffset}px`);
    };

    const updateVisibility = () => {
        visibilityFrame = null;
        updateFloatingOffset();
        const shouldShow = window.scrollY > Math.max(window.innerHeight * 0.7, 520)
            && ! document.documentElement.classList.contains('cookie-consent-visible');

        control.classList.toggle('is-visible', shouldShow);
        control.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
        control.tabIndex = shouldShow ? 0 : -1;
    };

    const queueVisibilityUpdate = () => {
        if (visibilityFrame !== null) {
            return;
        }

        visibilityFrame = window.requestAnimationFrame(updateVisibility);
    };

    control.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
        });
    }, { signal });

    updateVisibility();

    if (footerSafeZone) {
        floatingResizeObserver = new ResizeObserver(updateFloatingOffset);
        floatingResizeObserver.observe(footerSafeZone);
    }
    window.addEventListener('scroll', queueVisibilityUpdate, { passive: true, signal });
    window.addEventListener('resize', queueVisibilityUpdate, { passive: true, signal });
    window.addEventListener('cookie-consent-visibility-changed', queueVisibilityUpdate, { signal });
    signal.addEventListener('abort', () => {
        if (visibilityFrame !== null) {
            window.cancelAnimationFrame(visibilityFrame);
        }

        floatingResizeObserver?.disconnect();
    }, { once: true });
};

const hasWireNavigateDirective = (link) => [...link.attributes].some(({ name }) => (
    name === 'wire:navigate' || name.startsWith('wire:navigate.')
));

const enableInternalNavigation = () => {
    document.querySelectorAll('a[href]').forEach((link) => {
        if (link.hasAttribute('download') || link.target === '_blank') {
            return;
        }

        let url;

        try {
            url = new URL(link.href, window.location.href);
        } catch {
            return;
        }

        const isHashOnly = url.origin === window.location.origin
            && url.pathname === window.location.pathname
            && url.search === window.location.search
            && url.hash !== '';
        const requiresFullNavigation = url.origin === window.location.origin
            && /^\/(?:[a-z]{2}\/)?(?:privacy|terms|cookies|reader)(?:\/|$)|^\/admin(?:\/|$)/.test(url.pathname);

        if (requiresFullNavigation) {
            link.removeAttribute('wire:navigate');
            link.dataset.noNavigate = '';

            return;
        }

        if (
            url.origin === window.location.origin
            && ! isHashOnly
            && ! hasWireNavigateDirective(link)
            && link.dataset.noNavigate === undefined
        ) {
            link.setAttribute('wire:navigate', '');
        }
    });
};

const initializeArticleSharing = async (signal) => {
    if (! document.querySelector('[data-article-share]')) {
        return;
    }

    try {
        const { initializeArticleShare } = await import('./article-share');

        if (! signal.aborted) {
            initializeArticleShare(signal);
        }
    } catch {
        // Direct share links remain usable when the optional enhancement cannot load.
    }
};

const initializePageMotion = (signal, { skipWorkFilterEntranceMotion = false } = {}) => {
    initializeScrollProgress(signal);

    if (reducedMotion.matches) {
        document.documentElement.classList.remove('motion-capable');

        return;
    }

    document.documentElement.classList.add('motion-capable');

    let revealObserver;
    let methodObserver;
    let initialRevealFrame = null;
    const motionTimeouts = new Set();

    const revealElement = (element) => {
        if (element.classList.contains('is-revealed')) {
            return;
        }

        element.classList.add('is-revealed');
        const revealIndex = Number.parseInt(element.style.getPropertyValue('--reveal-index') || '0', 10);

        const completionTimeout = window.setTimeout(() => {
            element.classList.add('motion-complete');
            motionTimeouts.delete(completionTimeout);
        }, 1150 + (Number.isNaN(revealIndex) ? 0 : revealIndex * 75));
        motionTimeouts.add(completionTimeout);

        revealObserver?.unobserve(element);
    };

    revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            revealElement(entry.target);
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -4% 0px' });

    const revealElements = [...document.querySelectorAll('[data-reveal]')];

    const revealVisibleElements = () => {
        initialRevealFrame = null;

        revealElements.forEach((element) => {
            if (element.classList.contains('is-revealed')) {
                return;
            }

            const bounds = element.getBoundingClientRect();

            if (bounds.top < window.innerHeight * 0.96 && bounds.bottom > 0) {
                revealElement(element);
            }
        });
    };

    if (skipWorkFilterEntranceMotion) {
        revealElements
            .filter((element) => element.closest('.work-archive') !== null)
            .forEach((element) => element.classList.add('is-revealed', 'motion-complete'));
    }

    revealElements
        .filter((element) => ! element.classList.contains('is-revealed'))
        .forEach((element) => revealObserver.observe(element));
    initialRevealFrame = window.requestAnimationFrame(revealVisibleElements);

    const methodSteps = document.querySelectorAll('[data-method-step]');
    methodObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            methodSteps.forEach((step) => step.classList.remove('is-active'));
            entry.target.classList.add('is-active');
        });
    }, { threshold: 0.62 });

    methodSteps.forEach((step) => methodObserver.observe(step));

    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('[data-magnetic]').forEach((element) => {
            let animationFrame = null;
            let targetX = 0;
            let targetY = 0;

            const updatePosition = () => {
                element.style.setProperty('--magnetic-x', `${targetX.toFixed(2)}px`);
                element.style.setProperty('--magnetic-y', `${targetY.toFixed(2)}px`);
                element.style.setProperty('--magnetic-icon-x', `${(targetX * 0.22).toFixed(2)}px`);
                element.style.setProperty('--magnetic-icon-y', `${(targetY * 0.22).toFixed(2)}px`);
                animationFrame = null;
            };

            const resetPosition = () => {
                targetX = 0;
                targetY = 0;

                if (animationFrame !== null) {
                    window.cancelAnimationFrame(animationFrame);
                }

                updatePosition();
            };

            element.addEventListener('pointerenter', () => {
                element.classList.add('is-magnetic-active');
            }, { signal });

            element.addEventListener('pointermove', (event) => {
                const bounds = element.getBoundingClientRect();
                const relativeX = (event.clientX - bounds.left) / bounds.width - 0.5;
                const relativeY = (event.clientY - bounds.top) / bounds.height - 0.5;
                const proximity = Math.min(1, Math.hypot(relativeX, relativeY) * 2);

                targetX = relativeX * 16 * proximity;
                targetY = relativeY * 11 * proximity;

                if (animationFrame === null) {
                    animationFrame = window.requestAnimationFrame(updatePosition);
                }
            }, { signal });

            element.addEventListener('pointerleave', () => {
                element.classList.remove('is-magnetic-active');
                resetPosition();
            }, { signal });
        });

        document.querySelectorAll('[data-depth]').forEach((element) => {
            let animationFrame = null;

            const setDepth = (x, y) => {
                if (animationFrame !== null) {
                    window.cancelAnimationFrame(animationFrame);
                }

                animationFrame = window.requestAnimationFrame(() => {
                    element.style.setProperty('--depth-x', `${x.toFixed(2)}px`);
                    element.style.setProperty('--depth-y', `${y.toFixed(2)}px`);
                    animationFrame = null;
                });
            };

            element.addEventListener('pointermove', (event) => {
                const bounds = element.getBoundingClientRect();
                const strength = element.dataset.depth === 'portrait' ? 9 : 5;
                const x = ((event.clientX - bounds.left) / bounds.width - 0.5) * strength;
                const y = ((event.clientY - bounds.top) / bounds.height - 0.5) * strength;

                setDepth(x, y);
            }, { signal });

            element.addEventListener('pointerleave', () => setDepth(0, 0), { signal });
            signal.addEventListener('abort', () => {
                if (animationFrame !== null) {
                    window.cancelAnimationFrame(animationFrame);
                }
            }, { once: true });
        });
    }

    signal.addEventListener('abort', () => {
        revealObserver?.disconnect();
        methodObserver?.disconnect();

        if (initialRevealFrame !== null) {
            window.cancelAnimationFrame(initialRevealFrame);
        }

        motionTimeouts.forEach((timeout) => window.clearTimeout(timeout));
        motionTimeouts.clear();
    }, { once: true });
};

let frontEnhancementController = null;

let workFilterNavigationPending = false;

let workFilterNavigationResetTimeout = null;

const resetWorkFilterNavigation = () => {
    workFilterNavigationPending = false;
    document.documentElement.removeAttribute('data-work-filter-navigation');

    if (workFilterNavigationResetTimeout !== null) {
        window.clearTimeout(workFilterNavigationResetTimeout);
        workFilterNavigationResetTimeout = null;
    }
};

const markWorkFilterNavigation = (event) => {
    const destination = event instanceof CustomEvent ? event.detail?.url : null;
    const isWorkFilterNavigation = destination instanceof URL
        && document.querySelector('.work-archive') !== null
        && destination.origin === window.location.origin
        && destination.pathname === window.location.pathname
        && (destination.search !== window.location.search || event.detail?.history === true);

    if (! isWorkFilterNavigation) {
        return;
    }

    workFilterNavigationPending = true;
    document.documentElement.setAttribute('data-work-filter-navigation', 'true');

    if (workFilterNavigationResetTimeout !== null) {
        window.clearTimeout(workFilterNavigationResetTimeout);
    }

    workFilterNavigationResetTimeout = window.setTimeout(resetWorkFilterNavigation, 5000);
};

const preserveWorkFilterNavigationState = (event) => {
    if (! workFilterNavigationPending || ! (event instanceof CustomEvent) || typeof event.detail?.onSwap !== 'function') {
        return;
    }

    event.detail.onSwap(() => {
        document.documentElement.setAttribute('data-work-filter-navigation', 'true');
    });
};

const consumeWorkFilterNavigation = () => {
    const skipWorkFilterEntranceMotion = workFilterNavigationPending;

    if (skipWorkFilterEntranceMotion) {
        workFilterNavigationPending = false;

        if (workFilterNavigationResetTimeout !== null) {
            window.clearTimeout(workFilterNavigationResetTimeout);
            workFilterNavigationResetTimeout = null;
        }

        window.requestAnimationFrame(() => {
            document.documentElement.removeAttribute('data-work-filter-navigation');
        });
    }

    return skipWorkFilterEntranceMotion;
};

let consultationTurnstileWidgetId = null;

const renderConsultationTurnstile = () => {
    const container = document.getElementById('consultation-turnstile');

    if (! container || ! window.turnstile || container.dataset.rendered === '1') {
        return;
    }

    // Clear any widget left over from a previous render before drawing a new one.
    if (consultationTurnstileWidgetId !== null) {
        try { window.turnstile.remove(consultationTurnstileWidgetId); } catch {}
        consultationTurnstileWidgetId = null;
    }

    consultationTurnstileWidgetId = window.turnstile.render('#consultation-turnstile', {
        sitekey: container.dataset.sitekey,
        action: 'turnstile-spin-v2',
        callback: (token) => window.Livewire?.dispatch('turnstile-resolved', { token }),
        'expired-callback': () => window.Livewire?.dispatch('turnstile-resolved', { token: '' }),
        'error-callback': () => window.Livewire?.dispatch('turnstile-resolved', { token: '' }),
    });
    container.dataset.rendered = '1';
};

const initializeConsultationTurnstile = (signal) => {
    const container = document.getElementById('consultation-turnstile');

    if (! container || ! window.Livewire) {
        return;
    }

    // Reset the solved token when the Livewire component rejects the submission,
    // so the visitor can retry. Reset by widget id (never the global reset(),
    // which throws "Nothing to reset" when no widget is registered). Registered
    // through Livewire's JS event bus (not window), which is how component
    // dispatch() calls are surfaced in Livewire 4.
    const resetConsultationTurnstile = () => {
        if (consultationTurnstileWidgetId !== null && window.turnstile) {
            window.turnstile.reset(consultationTurnstileWidgetId);
        }
    };
    // Livewire.on returns an unsubscribe function (there is no Livewire.off).
    const unsubscribeReset = window.Livewire.on('reset-consultation-turnstile', resetConsultationTurnstile);
    signal.addEventListener('abort', () => {
        unsubscribeReset();
        if (consultationTurnstileWidgetId !== null && window.turnstile) {
            try { window.turnstile.remove(consultationTurnstileWidgetId); } catch {}
            consultationTurnstileWidgetId = null;
        }
    }, { once: true });

    // Render once api.js is ready, polling briefly as a fallback.
    if (window.turnstile) {
        renderConsultationTurnstile();
    } else {
        const stopPolling = () => clearInterval(poll);
        const poll = setInterval(() => {
            if (window.turnstile) {
                clearInterval(poll);
                renderConsultationTurnstile();
            }
        }, 150);
        signal.addEventListener('abort', stopPolling, { once: true });
        setTimeout(stopPolling, 5000);
    }
};

const analyticsInteractionEvents = new Set([
    'primary_cta_click',
    'service_cta_click',
    'article_related_click',
    'direct_contact_click',
    'consultation_form_start',
    'consultation_submit_success',
    'consultation_submit_error',
    'language_switch',
]);
const controlledConsultationErrorCategories = new Set([
    'validation',
    'turnstile',
    'rate_limited',
    'provider',
    'network',
    'unknown',
]);
const analyticsAttributeProperties = [
    ['analyticsUiLocation', 'ui_location'],
    ['analyticsDestinationCategory', 'destination_category'],
    ['analyticsServiceSlug', 'service_slug'],
    ['analyticsContentSlug', 'content_slug'],
    ['analyticsContactChannel', 'contact_channel'],
];
const analyticsStateNodes = new WeakSet();
let hasTrackedConsultationFormStart = false;

const trackAnalyticsInteraction = (eventName, payload = {}) => {
    if (! analyticsInteractionEvents.has(eventName)) {
        return false;
    }

    return window.IbrahimAnalytics?.track(eventName, payload) === true;
};

const analyticsPayloadForElement = (element) => analyticsAttributeProperties.reduce((payload, [datasetKey, property]) => {
    const value = element.dataset[datasetKey];

    if (typeof value === 'string') {
        payload[property] = value;
    }

    return payload;
}, {});

const consultationErrorCategory = (category) => (
    typeof category === 'string' && controlledConsultationErrorCategories.has(category)
        ? category
        : 'unknown'
);

const initializeAnalyticsEventTracking = (signal) => {
    const trackConsultationState = (element) => {
        if (analyticsStateNodes.has(element)) {
            return;
        }

        const isSuccess = element.hasAttribute('data-analytics-consultation-success');
        const eventName = isSuccess ? 'consultation_submit_success' : 'consultation_submit_error';
        const payload = isSuccess
            ? { ui_location: 'contact_form' }
            : {
                ui_location: 'contact_form',
                error_category: consultationErrorCategory(element.dataset.analyticsConsultationError),
            };

        if (trackAnalyticsInteraction(eventName, payload)) {
            analyticsStateNodes.add(element);
        }
    };
    const trackConsultationStates = () => {
        document.querySelectorAll('[data-analytics-consultation-success], [data-analytics-consultation-error]')
            .forEach(trackConsultationState);
    };
    const trackConsultationStart = (event) => {
        if (hasTrackedConsultationFormStart || !(event.target instanceof Element)) {
            return;
        }

        const form = event.target.closest('[data-analytics-consultation-form]');

        if (!form?.isConnected) {
            return;
        }

        hasTrackedConsultationFormStart = trackAnalyticsInteraction('consultation_form_start', {
            ui_location: 'contact_form',
        });
    };
    const trackConsultationError = (event) => {
        const detail = event instanceof CustomEvent ? event.detail : null;

        trackAnalyticsInteraction('consultation_submit_error', {
            ui_location: 'contact_form',
            error_category: consultationErrorCategory(detail?.category),
        });
    };

    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) {
            return;
        }

        const element = event.target.closest('[data-analytics-event]');

        if (element?.isConnected) {
            trackAnalyticsInteraction(element.dataset.analyticsEvent ?? '', analyticsPayloadForElement(element));
        }
    }, { signal });
    document.addEventListener('focusin', trackConsultationStart, { signal });
    document.addEventListener('input', trackConsultationStart, { signal });
    document.addEventListener('change', trackConsultationStart, { signal });
    window.addEventListener('consultation-submitted', () => {
        trackAnalyticsInteraction('consultation_submit_success', { ui_location: 'contact_form' });
    }, { signal });
    window.addEventListener('consultation-submit-error', trackConsultationError, { signal });
    window.addEventListener('analytics-consent-updated', trackConsultationStates, { signal });

    trackConsultationStates();
};

const initializeFrontEnhancements = ({ skipWorkFilterEntranceMotion = false } = {}) => {
    frontEnhancementController?.abort();
    frontEnhancementController = new AbortController();

    enableInternalNavigation();
    initializeHeroVideos(frontEnhancementController.signal);
    initializePageMotion(frontEnhancementController.signal, { skipWorkFilterEntranceMotion });
    initializeAccessibleDialogs(frontEnhancementController.signal);
    initializeArticleReadingProgress(frontEnhancementController.signal);
    initializeViewportStack(frontEnhancementController.signal);
    initializeOverflowRails(frontEnhancementController.signal);
    initializeBackToTop(frontEnhancementController.signal);
    initializeConsultationTurnstile(frontEnhancementController.signal);
    initializeAnalyticsEventTracking(frontEnhancementController.signal);
    void initializeArticleSharing(frontEnhancementController.signal);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeFrontEnhancements, { once: true });
} else {
    initializeFrontEnhancements();
}

document.addEventListener('livewire:navigate', markWorkFilterNavigation);
document.addEventListener('livewire:navigating', preserveWorkFilterNavigationState);
document.addEventListener('livewire:navigated', () => {
    initializeFrontEnhancements({ skipWorkFilterEntranceMotion: consumeWorkFilterNavigation() });
});
