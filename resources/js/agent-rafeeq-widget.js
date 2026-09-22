import { currentConsent } from './cookie-consent';

const markerSelector = '[data-agent-rafeeq-widget]';
const scriptSelector = 'script[data-agent-rafeeq-widget-script]';
const hostSelector = '.agent-rafeeq-widget';
const activeClass = 'agent-rafeeq-widget-active';

const widgetOptions = (marker) => {
    const scriptUrl = marker?.dataset.widgetUrl?.trim();
    const botKey = marker?.dataset.botKey?.trim();
    const locale = marker?.dataset.locale === 'en' ? 'en' : marker?.dataset.locale === 'ar' ? 'ar' : null;
    const history = ['memory', 'session', 'local'].includes(marker?.dataset.history)
        ? marker.dataset.history
        : 'session';

    if (! scriptUrl || ! botKey || ! locale) {
        return null;
    }

    try {
        const url = new URL(scriptUrl, window.location.href);

        if (! ['http:', 'https:'].includes(url.protocol) || url.username || url.password || url.search || url.hash
            || (window.location.protocol === 'https:' && url.protocol !== 'https:')) {
            return null;
        }

        return { scriptUrl: url.href, botKey, locale, history };
    } catch {
        return null;
    }
};

const destroyWidget = () => {
    document.querySelector(scriptSelector)?.remove();
    document.querySelectorAll(hostSelector).forEach((host) => {
        host.destroy?.();
        host.remove();
    });
    document.documentElement.classList.remove(activeClass);
};

const scriptMatches = (script, options) => (
    script?.dataset.agentRafeeqWidgetUrl === options.scriptUrl
    && script.dataset.botKey === options.botKey
    && script.dataset.locale === options.locale
    && script.dataset.history === options.history
);

export const initializeAgentRafeeqWidget = (signal) => {
    const marker = document.querySelector(markerSelector);
    const options = widgetOptions(marker);

    if (! options) {
        destroyWidget();

        return;
    }

    const mount = () => {
        if (! marker.isConnected) {
            return;
        }

        const existingScript = document.querySelector(scriptSelector);

        if (scriptMatches(existingScript, options)) {
            document.documentElement.classList.add(activeClass);

            return;
        }

        destroyWidget();

        const script = document.createElement('script');
        script.async = true;
        script.src = options.scriptUrl;
        script.dataset.agentRafeeqWidgetScript = 'true';
        script.dataset.agentRafeeqWidgetUrl = options.scriptUrl;
        script.dataset.botKey = options.botKey;
        script.dataset.locale = options.locale;
        script.dataset.history = options.history;
        script.addEventListener('error', () => {
            if (script.isConnected) {
                destroyWidget();
            }
        }, { once: true });
        document.body.append(script);
        document.documentElement.classList.add(activeClass);
    };

    const handleConsentVisibility = (event) => {
        if (event.detail?.visible) {
            destroyWidget();

            return;
        }

        if (currentConsent() !== null) {
            mount();
        }
    };

    window.addEventListener('cookie-consent-visibility-changed', handleConsentVisibility, { signal });

    if (currentConsent() === null) {
        window.addEventListener('analytics-consent-updated', mount, { once: true, signal });

        return;
    }

    mount();
};
