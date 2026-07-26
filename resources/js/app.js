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

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.data('layout', () => ({
        show: false,
        scrolled: false,
        restoreMenuFocus: true,
        scrollHandler: null,
        init() {
            this.scrollHandler = () => {
                this.scrolled = window.scrollY > 24;
            };

            this.scrollHandler();
            window.addEventListener('scroll', this.scrollHandler, { passive: true });
        },
        destroy() {
            window.removeEventListener('scroll', this.scrollHandler);
            this.close(false);
        },
        toggle() {
            if (this.show) {
                this.close();

                return;
            }

            const dialog = this.$refs.mobileMenu;

            if (! dialog) {
                return;
            }

            this.restoreMenuFocus = true;
            this.show = true;
            this.lockBackground();

            if (typeof dialog.showModal === 'function') {
                if (! dialog.open) {
                    dialog.showModal();
                }
            } else {
                dialog.setAttribute('open', '');
            }

            this.$nextTick(() => dialog.querySelector('[data-mobile-menu-initial-focus], a[href]')?.focus());
        },
        close(restoreFocus = true) {
            const dialog = this.$refs.mobileMenu;

            if (! this.show && ! dialog?.open) {
                return;
            }

            this.restoreMenuFocus = restoreFocus;

            if (dialog?.open && typeof dialog.close === 'function') {
                dialog.close();

                return;
            }

            dialog?.removeAttribute('open');
            this.handleNativeClose();
        },
        handleNativeClose() {
            this.show = false;
            this.unlockBackground();

            if (this.restoreMenuFocus) {
                this.$nextTick(() => this.$refs.menuToggle?.focus());
            }
        },
        lockBackground() {
            const root = document.documentElement;
            const scrollbarWidth = Math.max(0, window.innerWidth - root.clientWidth);

            root.style.setProperty('--scrollbar-compensation', `${scrollbarWidth}px`);
            root.classList.add('menu-open');

            document.querySelectorAll('main, .site-footer, [data-cookie-consent], [data-site-audio-player], [data-back-to-top]').forEach((element) => {
                if (! element.hasAttribute('inert')) {
                    element.dataset.menuInert = 'true';
                    element.inert = true;
                }
            });
        },
        unlockBackground() {
            const root = document.documentElement;

            root.classList.remove('menu-open');
            root.style.removeProperty('--scrollbar-compensation');
            document.querySelectorAll('[data-menu-inert="true"]').forEach((element) => {
                element.inert = false;
                delete element.dataset.menuInert;
            });
        },
    }));

    Alpine.data('accountMenu', () => ({
        open: false,
        toggle() {
            this.open = ! this.open;
        },
        close(restoreFocus = false) {
            if (! this.open) {
                return;
            }

            this.open = false;

            if (restoreFocus) {
                this.$nextTick(() => this.$refs.accountMenuTrigger?.focus());
            }
        },
        openAndFocus(position = 'first') {
            this.open = true;
            this.$nextTick(() => {
                const items = [...(this.$refs.accountMenu?.querySelectorAll('[role="menuitem"]') ?? [])];
                const item = position === 'last' ? items.at(-1) : items[0];

                item?.focus();
            });
        },
        moveFocus(event) {
            const items = [...(this.$refs.accountMenu?.querySelectorAll('[role="menuitem"]') ?? [])];
            const currentIndex = items.indexOf(document.activeElement);

            if (items.length === 0 || currentIndex === -1) {
                return;
            }

            if (event.key === 'Home') {
                event.preventDefault();
                items[0].focus();

                return;
            }

            if (event.key === 'End') {
                event.preventDefault();
                items.at(-1)?.focus();

                return;
            }

            if (! ['ArrowDown', 'ArrowUp'].includes(event.key)) {
                return;
            }

            event.preventDefault();
            const offset = event.key === 'ArrowDown' ? 1 : -1;
            items[(currentIndex + offset + items.length) % items.length].focus();
        },
    }));

    Alpine.data('atharReflection', ({ max, messages, initial = '', identityDisplay = 'anonymous', displayName = '' }) => ({
        max,
        messages,
        text: initial,
        identityDisplay,
        displayName,
        count: 0,
        progress: 0,
        message: messages.start,
        init() {
            this.update(this.$refs.field?.value || this.text);
        },
        update(value) {
            this.text = value;
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
            return this.identityDisplay === 'anonymous'
                ? ''
                : this.identityDisplay === 'first_name'
                    ? (this.displayName.trim().split(/\s+/u)[0] ?? '')
                    : this.displayName.trim();
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
        let hasOverflow = false;
        const updateControls = () => {
            hasOverflow = rail.scrollWidth > rail.clientWidth + 1;
            container.toggleAttribute('data-overflow-active', hasOverflow);
            previous.hidden = ! hasOverflow;
            next.hidden = ! hasOverflow;
            previous.disabled = ! hasOverflow || visibleEdges.start;
            next.disabled = ! hasOverflow || visibleEdges.end;
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

                updateControls();
            }, { root: rail, threshold: 1 })
            : null;
        const resizeObserver = 'ResizeObserver' in window ? new ResizeObserver(updateControls) : null;

        edgeObserver?.observe(start);
        edgeObserver?.observe(end);
        resizeObserver?.observe(rail);
        previous.addEventListener('click', () => scroll(-1), { signal });
        next.addEventListener('click', () => scroll(1), { signal });
        rail.addEventListener('scroll', updateControls, { passive: true, signal });
        updateControls();

        signal.addEventListener('abort', () => {
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
            && ! link.hasAttribute('wire:navigate')
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

const initializePageMotion = (signal) => {
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

    revealElements.forEach((element) => revealObserver.observe(element));
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
    if (!analyticsInteractionEvents.has(eventName)) {
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

const initializeFrontEnhancements = () => {
    frontEnhancementController?.abort();
    frontEnhancementController = new AbortController();

    enableInternalNavigation();
    initializePageMotion(frontEnhancementController.signal);
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

document.addEventListener('livewire:navigated', initializeFrontEnhancements);
