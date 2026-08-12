import { consentCookieMaxAge, currentConsent } from './cookie-consent.js';

const measurementId = document
    .querySelector('meta[name="google-analytics-id"]')
    ?.getAttribute('content')
    ?.trim();
const analyticsContextMeta = () => document.querySelector('meta[name="analytics-context"]');
const analyticsCookieLifetime = consentCookieMaxAge;
const maxPendingAnalyticsEvents = 32;
const allowedAnalyticsEvents = new Set([
    'primary_cta_click',
    'service_cta_click',
    'article_related_click',
    'direct_contact_click',
    'consultation_form_start',
    'consultation_submit_success',
    'consultation_submit_error',
    'language_switch',
    'audio_start',
    'audio_complete',
]);
const allowedAnalyticsProperties = new Set([
    'locale',
    'page_type',
    'route_key',
    'content_slug',
    'service_key',
    'topic_key',
    'ui_location',
    'destination_category',
    'contact_channel',
    'error_category',
    'build_revision',
]);
const allowedLocales = new Set(['ar', 'en']);
const allowedPageTypes = new Set([
    'home',
    'services',
    'work',
    'project',
    'writing',
    'article',
    'about',
    'contact',
]);
const allowedRouteKeys = new Set([
    'home',
    'services',
    'work',
    'work.show',
    'writing',
    'writing.show',
    'about',
    'contact',
]);
const allowedUiLocations = new Set([
    'navigation',
    'mobile_menu',
    'home_hero_primary',
    'home_hero_finale',
    'home_atlas',
    'home_services',
    'home_services_empty',
    'home_work',
    'home_work_empty',
    'work_services',
    'home_writing',
    'home_about',
    'services_hub',
    'services_hub_cta',
    'services_hub_empty',
    'work_empty',
    'writing_empty',
    'project_detail',
    'article_related',
    'article_after',
    'contact_hero',
    'contact_form',
    'contact_channels',
    'decision_room',
    'decision_room_direct',
    'decision_room_completion',
    'footer',
    'footer_cta',
    'footer_contact',
    'audio_player',
]);
const allowedDestinationCategories = new Set([
    'consultation',
    'service',
    'project',
    'article',
    'writing',
    'direct_contact',
    'reader',
]);
const allowedContactChannels = new Set(['email', 'linkedin', 'phone', 'whatsapp']);
const allowedErrorCategories = new Set([
    'validation',
    'turnstile',
    'rate_limited',
    'provider',
    'network',
    'unknown',
]);
const safeSlugPattern = /^[\p{L}\p{N}]+(?:[-_][\p{L}\p{N}]+)*$/u;
const safeRevisionPattern = /^[a-f0-9]{7,40}$/i;
let analyticsLoadScheduled = false;
let analyticsLoaded = false;
let lastTrackedPage = null;
let pendingAnalyticsEvents = [];

const hasAnalyticsConsent = () => currentConsent() === 'accepted';
const isAnalyticsConfigured = () => Boolean(measurementId && analyticsContextMeta());

const safeGtag = (...argumentsList) => {
    try {
        if (typeof window.gtag !== 'function') {
            return false;
        }

        window.gtag(...argumentsList);

        return true;
    } catch {
        return false;
    }
};

const sanitizeAnalyticsValue = (property, value) => {
    if (typeof value !== 'string') {
        return null;
    }

    const normalized = value.trim();

    if (normalized === '') {
        return null;
    }

    if (property === 'locale') {
        return allowedLocales.has(normalized) ? normalized : null;
    }

    if (property === 'page_type') {
        return allowedPageTypes.has(normalized) ? normalized : null;
    }

    if (property === 'route_key') {
        return allowedRouteKeys.has(normalized) ? normalized : null;
    }

    if (['content_slug', 'service_key', 'topic_key'].includes(property)) {
        return normalized.length <= 120 && safeSlugPattern.test(normalized) ? normalized : null;
    }

    if (property === 'ui_location') {
        return allowedUiLocations.has(normalized) ? normalized : null;
    }

    if (property === 'destination_category') {
        return allowedDestinationCategories.has(normalized) ? normalized : null;
    }

    if (property === 'contact_channel') {
        return allowedContactChannels.has(normalized) ? normalized : null;
    }

    if (property === 'error_category') {
        return allowedErrorCategories.has(normalized) ? normalized : null;
    }

    if (property === 'build_revision') {
        return safeRevisionPattern.test(normalized) ? normalized : null;
    }

    return null;
};

const sanitizeAnalyticsPayload = (payload) => {
    if (typeof payload !== 'object' || payload === null || Array.isArray(payload)) {
        return {};
    }

    return Object.entries(payload).reduce((sanitized, [property, value]) => {
        if (! allowedAnalyticsProperties.has(property)) {
            return sanitized;
        }

        const safeValue = sanitizeAnalyticsValue(property, value);

        if (safeValue !== null) {
            sanitized[property] = safeValue;
        }

        return sanitized;
    }, {});
};

const analyticsContext = () => {
    try {
        return sanitizeAnalyticsPayload(JSON.parse(analyticsContextMeta()?.getAttribute('content') ?? '{}'));
    } catch {
        return {};
    }
};

const eventPayload = (payload = {}) => sanitizeAnalyticsPayload({
    ...payload,
    ...analyticsContext(),
});

const pageTrackingKey = () => {
    const context = analyticsContext();

    return `${context.locale ?? ''}:${context.route_key ?? ''}:${window.location.pathname}`;
};

const sendAnalyticsEvent = (eventName, payload) => {
    if (! isAnalyticsConfigured() || ! analyticsLoaded || ! hasAnalyticsConsent()) {
        return false;
    }

    return safeGtag('event', eventName, payload);
};

const flushPendingAnalyticsEvents = () => {
    const events = pendingAnalyticsEvents;

    pendingAnalyticsEvents = [];

    events.forEach(({ eventName, payload }) => sendAnalyticsEvent(eventName, payload));
};

const queueAnalyticsEvent = (eventName, payload) => {
    pendingAnalyticsEvents = [
        ...pendingAnalyticsEvents.slice(-(maxPendingAnalyticsEvents - 1)),
        { eventName, payload },
    ];
};

export const trackAnalyticsEvent = (eventName, payload = {}) => {
    if (! allowedAnalyticsEvents.has(eventName) || ! isAnalyticsConfigured() || ! hasAnalyticsConsent()) {
        return false;
    }

    const sanitizedPayload = eventPayload(payload);

    if (analyticsLoaded) {
        return sendAnalyticsEvent(eventName, sanitizedPayload);
    }

    queueAnalyticsEvent(eventName, sanitizedPayload);
    scheduleGoogleAnalytics();

    return true;
};

const initializeGoogleConsent = () => {
    if (! isAnalyticsConfigured()) {
        return;
    }

    try {
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function gtag() {
            window.dataLayer.push(arguments);
        };
        window[`ga-disable-${measurementId}`] = ! hasAnalyticsConsent();
        safeGtag('consent', 'default', {
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            security_storage: 'granted',
        });
    } catch {
        // Analytics is optional and must never interrupt the public experience.
    }
};

const trackPageView = () => {
    const trackingKey = pageTrackingKey();

    if (! analyticsLoaded || ! hasAnalyticsConsent() || trackingKey === lastTrackedPage) {
        return;
    }

    if (sendAnalyticsEvent('page_view', eventPayload())) {
        lastTrackedPage = trackingKey;
    }
};

const loadGoogleAnalytics = () => {
    analyticsLoadScheduled = false;

    if (! isAnalyticsConfigured() || ! hasAnalyticsConsent()) {
        return;
    }

    try {
        window[`ga-disable-${measurementId}`] = false;

        if (! safeGtag('consent', 'update', {
            analytics_storage: 'granted',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
        })) {
            return;
        }

        safeGtag('js', new Date());
        safeGtag('config', measurementId, {
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
        flushPendingAnalyticsEvents();
    } catch {
        // A blocked or failed analytics request must not affect navigation or form controls.
    }
};

const scheduleGoogleAnalytics = () => {
    if (! isAnalyticsConfigured() || ! hasAnalyticsConsent() || analyticsLoadScheduled || analyticsLoaded) {
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
    try {
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
    } catch {
        // Cookie cleanup is best-effort when browser privacy controls block access.
    }
};

const revokeGoogleAnalytics = () => {
    pendingAnalyticsEvents = [];
    clearAnalyticsCookies();

    if (! isAnalyticsConfigured()) {
        return;
    }

    try {
        window[`ga-disable-${measurementId}`] = true;
        safeGtag('consent', 'update', {
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
        });
        lastTrackedPage = null;
    } catch {
        // Consent controls remain usable even if the analytics provider fails.
    }
};

if (isAnalyticsConfigured()) {
    if (! hasAnalyticsConsent()) {
        clearAnalyticsCookies();
    }

    initializeGoogleConsent();
    scheduleGoogleAnalytics();

    window.IbrahimAnalytics = Object.freeze({
        track: trackAnalyticsEvent,
    });

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
}
