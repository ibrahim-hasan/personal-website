import collapse from '@alpinejs/collapse';
import 'flyonui/flyonui';

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.plugin(collapse);

    Alpine.data('layout', () => ({
        show: false,
        scrolled: false,
        init() {
            const checkScroll = () => {
                this.scrolled = window.scrollY > 24;
            };

            checkScroll();
            window.addEventListener('scroll', checkScroll, { passive: true });
        },
        toggle() {
            this.show = !this.show;
        },
    }));

    Alpine.data('serviceTabs', ({ services }) => ({
        services,
        active: services[0]?.id ?? null,
        current() {
            return this.services.find((service) => service.id === this.active) ?? this.services[0];
        },
    }));

    Alpine.data('projectFilter', ({ projects }) => ({
        projects,
        category: 'All',
        categories() {
            return [...new Set(this.projects.map((project) => project.category))];
        },
        filtered() {
            if (this.category === 'All') {
                return this.projects;
            }

            return this.projects.filter((project) => project.category === this.category);
        },
    }));
});
