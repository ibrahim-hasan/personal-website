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
    window.dispatchEvent(new CustomEvent('cookie-preferences-opened', {
        detail: { trigger },
    }));
});

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.data('cookieConsent', ({ autoOpen = true } = {}) => ({
        visible: autoOpen && currentConsent() === null,
        savedConsent: currentConsent(),
        returnFocus: null,
        openListener: null,
        init() {
            this.openListener = (event) => this.open(event.detail?.trigger);
            window.addEventListener('cookie-preferences-opened', this.openListener);
            this.$watch('visible', (isVisible) => this.updateModalState(isVisible));
            this.updateModalState(this.visible);

            if (this.visible) {
                this.focusPrimaryAction();
            }
        },
        destroy() {
            window.removeEventListener('cookie-preferences-opened', this.openListener);
            this.updateModalState(false);
        },
        choose(status) {
            saveConsent(status);
            this.savedConsent = status;
            this.visible = false;
            announceConsent(status);
            this.restoreFocus();
        },
        accept() {
            this.choose('accepted');
        },
        reject() {
            this.choose('rejected');
        },
        open(trigger = null) {
            this.returnFocus = trigger instanceof HTMLElement ? trigger : document.activeElement;
            this.savedConsent = currentConsent();
            this.visible = true;
            this.focusPrimaryAction();
        },
        close() {
            this.visible = false;
            this.restoreFocus();
        },
        updateModalState(isVisible) {
            document.documentElement.classList.toggle('cookie-consent-open', isVisible);

            document.querySelectorAll('body > :not([data-cookie-consent]):not(script)').forEach((element) => {
                if (isVisible && ! element.hasAttribute('inert')) {
                    element.setAttribute('inert', '');
                    element.dataset.cookieConsentInerted = '';
                } else if (! isVisible && element.dataset.cookieConsentInerted !== undefined) {
                    element.removeAttribute('inert');
                    delete element.dataset.cookieConsentInerted;
                }
            });
        },
        focusPrimaryAction() {
            this.$nextTick(() => {
                const selectedAction = this.savedConsent === 'rejected'
                    ? this.$refs.reject
                    : this.$refs.accept;

                selectedAction?.focus();
            });
        },
        restoreFocus() {
            const target = this.returnFocus;

            this.$nextTick(() => {
                if (target instanceof HTMLElement && target.isConnected) {
                    target.focus();
                }
            });
        },
        trapFocus(event) {
            const focusable = [...(this.$refs.dialog?.querySelectorAll('a[href], button:not([disabled])') ?? [])]
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
});
