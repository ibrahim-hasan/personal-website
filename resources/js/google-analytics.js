import { consentCookieMaxAge, currentConsent } from './cookie-consent';

const measurementId = document
    .querySelector('meta[name="google-analytics-id"]')
    ?.getAttribute('content')
    ?.trim();
const analyticsCookieLifetime = consentCookieMaxAge;
let analyticsLoadScheduled = false;
let analyticsLoaded = false;
let lastTrackedLocation = null;

const hasAnalyticsConsent = () => currentConsent() === 'accepted';
const sanitizedLocation = () => `${window.location.origin}${window.location.pathname}`;
const sanitizedReferrer = () => {
    if (! document.referrer) {
        return '';
    }

    try {
        return new URL(document.referrer).origin;
    } catch {
        return '';
    }
};

const initializeGoogleConsent = () => {
    if (! measurementId) {
        return;
    }

    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function gtag() {
        window.dataLayer.push(arguments);
    };
    window[`ga-disable-${measurementId}`] = ! hasAnalyticsConsent();
    window.gtag('consent', 'default', {
        analytics_storage: 'denied',
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        security_storage: 'granted',
    });
};

const trackPageView = () => {
    const pageLocation = sanitizedLocation();

    if (! analyticsLoaded || ! hasAnalyticsConsent() || pageLocation === lastTrackedLocation) {
        return;
    }

    lastTrackedLocation = pageLocation;
    window.gtag('event', 'page_view', {
        page_location: pageLocation,
        page_referrer: sanitizedReferrer(),
        page_title: document.title,
    });
};

const loadGoogleAnalytics = () => {
    analyticsLoadScheduled = false;

    if (! measurementId || ! hasAnalyticsConsent()) {
        return;
    }

    window[`ga-disable-${measurementId}`] = false;
    window.gtag('consent', 'update', {
        analytics_storage: 'granted',
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
    });
    window.gtag('js', new Date());
    window.gtag('config', measurementId, {
        allow_google_signals: false,
        allow_ad_personalization_signals: false,
        cookie_expires: analyticsCookieLifetime,
        send_page_view: false,
    });

    if (! document.querySelector('script[data-google-analytics]')) {
        const googleAnalyticsScript = document.createElement('script');

        googleAnalyticsScript.async = true;
        googleAnalyticsScript.dataset.googleAnalytics = '';
        googleAnalyticsScript.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;
        document.head.append(googleAnalyticsScript);
    }

    analyticsLoaded = true;
    trackPageView();
};

const scheduleGoogleAnalytics = () => {
    if (! measurementId || ! hasAnalyticsConsent() || analyticsLoadScheduled || analyticsLoaded) {
        return;
    }

    analyticsLoadScheduled = true;

    const scheduleIdleLoad = () => {
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(loadGoogleAnalytics, { timeout: 2500 });

            return;
        }

        window.setTimeout(loadGoogleAnalytics, 1);
    };

    if (document.readyState === 'complete') {
        scheduleIdleLoad();
    } else {
        window.addEventListener('load', scheduleIdleLoad, { once: true });
    }
};

const analyticsCookieDomains = () => {
    const labels = window.location.hostname.split('.');
    const registrableDomain = labels.length > 1 ? `.${labels.slice(-2).join('.')}` : null;

    return [...new Set([null, window.location.hostname, registrableDomain].filter(Boolean))];
};

const clearAnalyticsCookies = () => {
    document.cookie
        .split('; ')
        .map((cookie) => cookie.split('=')[0])
        .filter((name) => name === '_ga' || name.startsWith('_ga_'))
        .forEach((name) => {
            [null, ...analyticsCookieDomains()].forEach((domain) => {
                const domainAttribute = domain ? `; Domain=${domain}` : '';
                const secureAttribute = window.location.protocol === 'https:' ? '; Secure' : '';

                document.cookie = `${name}=; Max-Age=0; Path=/${domainAttribute}; SameSite=Lax${secureAttribute}`;
            });
        });
};

const revokeGoogleAnalytics = () => {
    clearAnalyticsCookies();

    if (! measurementId) {
        return;
    }

    window[`ga-disable-${measurementId}`] = true;
    window.gtag('consent', 'update', {
        analytics_storage: 'denied',
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
    });
    lastTrackedLocation = null;
};

initializeGoogleConsent();
scheduleGoogleAnalytics();

window.addEventListener('analytics-consent-updated', (event) => {
    if (event.detail?.status === 'accepted') {
        if (analyticsLoaded) {
            loadGoogleAnalytics();
        } else {
            scheduleGoogleAnalytics();
        }

        return;
    }

    revokeGoogleAnalytics();
});

document.addEventListener('livewire:navigated', trackPageView);
