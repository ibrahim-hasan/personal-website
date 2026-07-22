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

const initializeBackToTop = (signal) => {
    const control = document.querySelector('[data-back-to-top]');

    if (! control) {
        return;
    }

    const floatingSurface = document.querySelector('[data-site-audio-player]');
    const footerSafeZone = document.querySelector('[data-back-to-top-safe-zone]');
    let visibilityFrame = null;
    let floatingResizeObserver = null;
    let floatingMutationObserver = null;

    const updateFloatingOffset = () => {
        const isVisible = floatingSurface && ! floatingSurface.hidden;
        const audioOffset = isVisible ? Math.ceil(floatingSurface.getBoundingClientRect().height) : 0;
        const footerRect = footerSafeZone?.getBoundingClientRect();
        const footerOffset = footerRect && footerRect.top < window.innerHeight
            ? Math.ceil(window.innerHeight - footerRect.top + 16)
            : 0;
        const occupiedHeight = Math.max(audioOffset, footerOffset);

        control.style.setProperty('--floating-footer-offset', `${occupiedHeight}px`);
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

    if (floatingSurface) {
        floatingResizeObserver = new ResizeObserver(updateFloatingOffset);
        floatingResizeObserver.observe(floatingSurface);
        floatingMutationObserver = new MutationObserver(updateFloatingOffset);
        floatingMutationObserver.observe(floatingSurface, {
            attributes: true,
            attributeFilter: ['hidden', 'class', 'style'],
        });
    }
    if (footerSafeZone) {
        floatingResizeObserver ??= new ResizeObserver(updateFloatingOffset);
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
        floatingMutationObserver?.disconnect();
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

const initializeFrontEnhancements = () => {
    frontEnhancementController?.abort();
    frontEnhancementController = new AbortController();

    enableInternalNavigation();
    initializeHeroVideos(frontEnhancementController.signal);
    initializePageMotion(frontEnhancementController.signal);
    initializeBackToTop(frontEnhancementController.signal);
    void initializeArticleSharing(frontEnhancementController.signal);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeFrontEnhancements, { once: true });
} else {
    initializeFrontEnhancements();
}

document.addEventListener('livewire:navigated', initializeFrontEnhancements);
