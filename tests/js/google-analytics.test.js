import assert from 'node:assert/strict';
import test from 'node:test';

let moduleVersion = 0;
const currentDateNow = Date.now;

Date.now = () => 1_786_500_000_000;

test.after(() => {
    Date.now = currentDateNow;
});

class FakeEventTarget {
    listeners = new Map();

    addEventListener(type, listener, options = {}) {
        const listeners = this.listeners.get(type) ?? [];

        listeners.push({ listener, once: Boolean(options?.once) });
        this.listeners.set(type, listeners);
    }

    dispatchEvent(event) {
        const listeners = [...(this.listeners.get(event.type) ?? [])];

        listeners.forEach(({ listener, once }) => {
            listener.call(this, event);

            if (once) {
                this.listeners.set(event.type, (this.listeners.get(event.type) ?? []).filter((entry) => entry.listener !== listener));
            }
        });

        return true;
    }
}

class FakeDocument extends FakeEventTarget {
    cookieValues = new Map();
    cookieWrites = [];
    googleAnalyticsScript = null;

    constructor({ measurementId, analyticsContext, consent }) {
        super();

        this.measurementId = measurementId;
        this.analyticsContext = analyticsContext;
        this.readyState = 'complete';
        this.head = {
            append: (element) => {
                this.googleAnalyticsScript = element;
            },
        };

        if (consent) {
            this.setConsent(consent);
        }
    }

    get cookie() {
        return [...this.cookieValues.entries()]
            .map(([name, value]) => `${name}=${value}`)
            .join('; ');
    }

    set cookie(value) {
        this.cookieWrites.push(value);

        const [nameValue, ...attributes] = value.split(';').map((part) => part.trim());
        const separatorIndex = nameValue.indexOf('=');

        if (separatorIndex === -1) {
            return;
        }

        const name = nameValue.slice(0, separatorIndex);
        const cookieValue = nameValue.slice(separatorIndex + 1);
        const expiresImmediately = attributes.some((attribute) => attribute.toLowerCase() === 'max-age=0');

        if (expiresImmediately) {
            this.cookieValues.delete(name);

            return;
        }

        this.cookieValues.set(name, cookieValue);
    }

    querySelector(selector) {
        if (selector === 'meta[name="google-analytics-id"]') {
            return this.metaElement(this.measurementId);
        }

        if (selector === 'meta[name="analytics-context"]') {
            return this.metaElement(this.analyticsContext ? JSON.stringify(this.analyticsContext) : null);
        }

        if (selector === 'meta[name="cookie-consent-version"]') {
            return this.metaElement('v1');
        }

        if (selector === 'script[data-google-analytics]') {
            return this.googleAnalyticsScript;
        }

        return null;
    }

    createElement() {
        return {
            async: false,
            dataset: {},
            src: '',
        };
    }

    setConsent(status) {
        this.cookieValues.set('ibrahimhasan_analytics_consent', `${status}.v1.${Date.now()}`);
    }

    metaElement(content) {
        if (content === null) {
            return null;
        }

        return {
            getAttribute: (name) => name === 'content' ? content : null,
        };
    }
}

const createBrowserEnvironment = ({
    consent = null,
    measurementId = 'G-TEST123',
    analyticsContext = {
        locale: 'en',
        page_type: 'contact',
        route_key: 'contact',
    },
} = {}) => {
    const document = new FakeDocument({ measurementId, analyticsContext, consent });
    const calls = [];
    const timers = [];
    const window = new FakeEventTarget();

    window.location = {
        hostname: 'ibrahimhasan.net',
        pathname: '/contact',
        protocol: 'https:',
    };
    window.gtag = (...argumentsList) => calls.push(argumentsList);
    window.setTimeout = (callback) => {
        timers.push(callback);

        return timers.length;
    };
    window.dataLayer = [];

    document.cookie = '_ga=test-cookie';

    Object.assign(globalThis, {
        CustomEvent: class CustomEvent {
            constructor(type, options = {}) {
                this.type = type;
                this.detail = options.detail;
            }
        },
        Element: class Element {},
        document,
        window,
    });

    return {
        calls,
        document,
        flushTimers: () => {
            while (timers.length > 0) {
                timers.shift()();
            }
        },
        window,
    };
};

const loadAnalytics = async (options = {}) => {
    const environment = createBrowserEnvironment(options);
    const moduleUrl = new URL('../../resources/js/google-analytics.js', import.meta.url);
    const analytics = await import(`${moduleUrl.href}?test=${moduleVersion += 1}`);

    environment.flushTimers();

    return { analytics, environment };
};

const analyticsEvents = (calls, eventName) => calls.filter(([command, name]) => (
    command === 'event' && name === eventName
));

test('the consent-safe GA tracker', async (t) => {
    await t.test('does not queue or send analytics events before consent', async () => {
        const { analytics, environment } = await loadAnalytics({ consent: null });

        assert.equal(analytics.trackAnalyticsEvent('primary_cta_click', {
            ui_location: 'home_hero_primary',
        }), false);
        assert.deepEqual(analyticsEvents(environment.calls, 'primary_cta_click'), []);
        assert.equal(environment.window['ga-disable-G-TEST123'], true);
    });

    await t.test('stops tracking and clears analytics cookies when consent is revoked', async () => {
        const { analytics, environment } = await loadAnalytics({ consent: 'accepted' });

        assert.equal(analytics.trackAnalyticsEvent('primary_cta_click', {
            ui_location: 'home_hero_primary',
        }), true);
        assert.equal(analyticsEvents(environment.calls, 'primary_cta_click').length, 1);

        environment.document.setConsent('rejected');
        environment.window.dispatchEvent({
            type: 'analytics-consent-updated',
            detail: { status: 'rejected' },
        });

        assert.equal(environment.window['ga-disable-G-TEST123'], true);
        assert.ok(environment.document.cookieWrites.some((value) => value.startsWith('_ga=; Max-Age=0;')));
        assert.ok(environment.calls.some(([command, action, payload]) => (
            command === 'consent'
            && action === 'update'
            && payload.analytics_storage === 'denied'
        )));
        assert.equal(analytics.trackAnalyticsEvent('primary_cta_click', {
            ui_location: 'home_hero_finale',
        }), false);
        assert.equal(analyticsEvents(environment.calls, 'primary_cta_click').length, 1);
    });

    await t.test('keeps only allowlisted, non-personal event properties and permits phone', async () => {
        const { analytics, environment } = await loadAnalytics({ consent: 'accepted' });

        assert.equal(analytics.trackAnalyticsEvent('contact_email_opened', {
            email: 'person@example.com',
        }), false);
        assert.deepEqual(analyticsEvents(environment.calls, 'contact_email_opened'), []);
        assert.equal(analytics.trackAnalyticsEvent('direct_contact_click', {
            build_revision: 'abc1234',
            company: 'Example Company',
            contact_channel: 'phone',
            content_slug: 'person@example.com',
            destination_category: 'direct_contact',
            email: 'person@example.com',
            message: 'Please call me on +966500000000',
            name: 'Ibrahim Hasan',
            record_id: '42',
            role: 'Founder',
            ui_location: 'contact_channels',
        }), true);

        const [[, , payload]] = analyticsEvents(environment.calls, 'direct_contact_click');

        assert.deepEqual(payload, {
            build_revision: 'abc1234',
            contact_channel: 'phone',
            destination_category: 'direct_contact',
            locale: 'en',
            page_type: 'contact',
            route_key: 'contact',
            ui_location: 'contact_channels',
        });
        [
            'company',
            'content_slug',
            'email',
            'message',
            'name',
            'record_id',
            'role',
        ].forEach((property) => assert.equal(property in payload, false));
    });

    await t.test('preserves the distinct allowlisted CTA locations and drops generic ones', async () => {
        const { analytics, environment } = await loadAnalytics({ consent: 'accepted' });
        const locations = [
            'home_hero_primary',
            'home_hero_finale',
            'footer_cta',
            'footer_contact',
            'decision_room_direct',
            'decision_room_completion',
        ];

        locations.forEach((uiLocation) => {
            assert.equal(analytics.trackAnalyticsEvent('primary_cta_click', { ui_location: uiLocation }), true);
        });

        assert.deepEqual(
            analyticsEvents(environment.calls, 'primary_cta_click').map(([, , payload]) => payload.ui_location),
            locations,
        );

        assert.equal(analytics.trackAnalyticsEvent('primary_cta_click', { ui_location: 'home_hero' }), true);

        const [, , genericPayload] = analyticsEvents(environment.calls, 'primary_cta_click').at(-1);

        assert.equal('ui_location' in genericPayload, false);
    });
});
