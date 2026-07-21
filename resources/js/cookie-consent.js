const consentCookie = 'ibrahimhasan_analytics_consent';
const consentVersion = document
    .querySelector('meta[name="cookie-consent-version"]')
    ?.getAttribute('content')
    ?.trim();
export const consentCookieMaxAge = 60 * 60 * 24 * 180;
const consentMaxAgeMilliseconds = consentCookieMaxAge * 1000;
const allowedClockSkewMilliseconds = 5 * 60 * 1000;
const validConsentValues = ['accepted', 'rejected'];

const readCookie = (name) => document.cookie
    .split('; ')
    .find((cookie) => cookie.startsWith(`${name}=`))
    ?.slice(name.length + 1) ?? null;

export const currentConsent = () => {
    const parts = (readCookie(consentCookie) ?? '').split('.');

    if (parts.length !== 3) {
        return null;
    }

    const [value, version, recordedAt] = parts;
    const timestamp = Number(recordedAt);
    const now = Date.now();
    const hasValidTimestamp = Number.isInteger(timestamp)
        && timestamp > 0
        && timestamp <= now + allowedClockSkewMilliseconds
        && timestamp >= now - consentMaxAgeMilliseconds;

    return consentVersion
        && validConsentValues.includes(value)
        && version === consentVersion
        && hasValidTimestamp
        ? value
        : null;
};

const saveConsent = (value) => {
    const secureAttribute = window.location.protocol === 'https:' ? '; Secure' : '';

    document.cookie = `${consentCookie}=${value}.${consentVersion}.${Date.now()}; Max-Age=${consentCookieMaxAge}; Path=/; SameSite=Lax${secureAttribute}`;
};

const announceConsent = (status) => {
    window.dispatchEvent(new CustomEvent('analytics-consent-updated', {
        detail: { status },
    }));
};

document.addEventListener('click', (event) => {
    if (! (event.target instanceof Element)) {
        return;
    }

    const trigger = event.target.closest('[data-open-cookie-preferences]');

    if (! trigger) {
        return;
    }

    event.preventDefault();
    window.dispatchEvent(new CustomEvent('cookie-preferences-opened'));
});

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.data('cookieConsent', ({ autoOpen = false } = {}) => ({
        visible: autoOpen && currentConsent() === null,
        showSettings: false,
        savedConsent: currentConsent(),
        analyticsEnabled: currentConsent() === 'accepted',
        openListener: null,
        resizeObserver: null,
        originalBodyPadding: '',
        init() {
            this.originalBodyPadding = document.body.style.paddingBlockEnd;
            this.openListener = () => this.openSettings();
            window.addEventListener('cookie-preferences-opened', this.openListener);
            if ('ResizeObserver' in window) {
                this.resizeObserver = new ResizeObserver(() => this.updateConsentOffset());
                this.resizeObserver.observe(this.$refs.surface);
            }
            this.$watch('visible', (isVisible) => this.updateConsentState(isVisible));
            this.$watch('showSettings', () => this.$nextTick(() => this.updateConsentOffset()));
            this.updateConsentState(this.visible);
        },
        destroy() {
            window.removeEventListener('cookie-preferences-opened', this.openListener);
            this.resizeObserver?.disconnect();
            document.documentElement.classList.remove('cookie-consent-visible');
            document.body.style.paddingBlockEnd = this.originalBodyPadding;
        },
        choose(status) {
            saveConsent(status);
            this.savedConsent = status;
            this.analyticsEnabled = status === 'accepted';
            this.showSettings = false;
            this.visible = false;
            announceConsent(status);
        },
        accept() {
            this.choose('accepted');
        },
        reject() {
            this.choose('rejected');
        },
        openSettings() {
            this.savedConsent = currentConsent();
            this.analyticsEnabled = this.savedConsent === 'accepted';
            this.showSettings = true;
            this.visible = true;
        },
        showChoices() {
            this.showSettings = false;
        },
        saveSettings() {
            this.choose(this.analyticsEnabled ? 'accepted' : 'rejected');
        },
        updateConsentState(isVisible) {
            document.documentElement.classList.toggle('cookie-consent-visible', isVisible);
            this.$nextTick(() => this.updateConsentOffset());
        },
        updateConsentOffset() {
            const height = this.visible
                ? Math.ceil(this.$refs.surface?.getBoundingClientRect().height ?? 0)
                : 0;

            document.body.style.paddingBlockEnd = height > 0
                ? `${height}px`
                : this.originalBodyPadding;
        },
    }));
});
