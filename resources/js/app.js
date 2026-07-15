import './article-reader';

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
            this.show = !this.show;
        },
        close() {
            this.show = false;
        },
    }));

    Alpine.data('serviceTabs', ({ services }) => ({
        services,
        active: services[0]?.id ?? null,
        activate(id) {
            this.active = id;
        },
        current() {
            return this.services.find((service) => service.id === this.active) ?? this.services[0];
        },
    }));

    Alpine.data('projectFilter', ({ projects }) => ({
        projects,
        lens: 'all',
        select(lens) {
            this.lens = lens;
        },
        matches(projectLens) {
            return this.lens === 'all' || projectLens === this.lens;
        },
    }));

    Alpine.data('articleLibrary', () => ({
        active: 'all',
        select(topic) {
            this.active = topic;
        },
        matches(topics) {
            return this.active === 'all' || topics.includes(this.active);
        },
    }));
});

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const updateScrollProgress = () => {
    const scrollable = document.documentElement.scrollHeight - window.innerHeight;
    const progress = scrollable > 0 ? Math.min(window.scrollY / scrollable, 1) : 0;

    document.documentElement.style.setProperty('--scroll-progress', progress.toFixed(4));
};

const initializePageMotion = () => {
    updateScrollProgress();
    window.addEventListener('scroll', updateScrollProgress, { passive: true });
    window.addEventListener('resize', updateScrollProgress, { passive: true });

    if (reducedMotion.matches) {
        return;
    }

    document.documentElement.classList.add('motion-capable');

    let revealObserver;

    const revealElement = (element) => {
        if (element.classList.contains('is-revealed')) {
            return;
        }

        element.classList.add('is-revealed');
        const revealIndex = Number.parseInt(element.style.getPropertyValue('--reveal-index') || '0', 10);

        window.setTimeout(() => {
            element.classList.add('motion-complete');
        }, 1150 + (Number.isNaN(revealIndex) ? 0 : revealIndex * 75));

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
    let revealFrame = null;

    const revealVisibleElements = () => {
        revealFrame = null;

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

    const queueVisibleReveal = () => {
        if (revealFrame !== null) {
            return;
        }

        revealFrame = window.requestAnimationFrame(revealVisibleElements);
    };

    revealElements.forEach((element) => revealObserver.observe(element));
    queueVisibleReveal();
    window.addEventListener('scroll', queueVisibleReveal, { passive: true });
    window.addEventListener('resize', queueVisibleReveal, { passive: true });

    const methodSteps = document.querySelectorAll('[data-method-step]');
    const methodObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) {
                return;
            }

            methodSteps.forEach((step) => step.classList.remove('is-active'));
            entry.target.classList.add('is-active');
        });
    }, { threshold: 0.62 });

    methodSteps.forEach((step) => methodObserver.observe(step));

    if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        document.querySelectorAll('[data-magnetic]').forEach((element) => {
            element.addEventListener('pointermove', (event) => {
                const bounds = element.getBoundingClientRect();
                const x = (event.clientX - bounds.left - bounds.width / 2) * 0.12;
                const y = (event.clientY - bounds.top - bounds.height / 2) * 0.12;

                element.style.setProperty('--magnetic-x', `${x.toFixed(2)}px`);
                element.style.setProperty('--magnetic-y', `${y.toFixed(2)}px`);
            });

            element.addEventListener('pointerleave', () => {
                element.style.setProperty('--magnetic-x', '0px');
                element.style.setProperty('--magnetic-y', '0px');
            });
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
            });

            element.addEventListener('pointerleave', () => setDepth(0, 0));
        });
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePageMotion, { once: true });
} else {
    initializePageMotion();
}
