import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

let version = 0;

class Surface extends EventTarget {
    dataset = {};
    isConnected = true;
    remove() { this.isConnected = false; }
}

const environment = ({ consent = 'rejected', url = 'https://agent.example.com/widget.js', locale = 'ar' } = {}) => {
    const window = new Surface();
    window.location = { href: 'https://ibrahimhasan.net/', protocol: 'https:' };
    window.consent = consent;
    const marker = new Surface();
    Object.assign(marker.dataset, { widgetUrl: url, botKey: 'pk_live_example', locale, history: 'session' });
    const scripts = [];
    const hosts = [];
    const classes = new Set();
    const document = {
        marker,
        documentElement: { classList: { add: (name) => classes.add(name), remove: (name) => classes.delete(name) } },
        body: { append: (script) => scripts.push(script) },
        createElement: () => new Surface(),
        querySelector: (selector) => selector === '[data-agent-rafeeq-widget]'
            ? document.marker
            : scripts.find((script) => script.isConnected) ?? null,
        querySelectorAll: () => hosts.filter((host) => host.isConnected),
    };
    globalThis.window = window;
    globalThis.document = document;
    return { window, document, marker, scripts, hosts, classes, mounted: () => scripts.filter((script) => script.isConnected) };
};

const load = async () => {
    const source = readFileSync(new URL('../../resources/js/agent-rafeeq-widget.js', import.meta.url), 'utf8')
        .replace(/import \{ currentConsent \} from '.\/cookie-consent(?:\.js)?';/, 'const currentConsent = () => window.consent;');
    return import(`data:text/javascript;base64,${Buffer.from(`${source}\n// ${++version}`).toString('base64')}`);
};

const event = (type, detail) => {
    const result = new Event(type);
    result.detail = detail;
    return result;
};

test('functional assistant waits for the cookie choice and mounts once even when analytics is rejected', async () => {
    const env = environment({ consent: null });
    const { initializeAgentRafeeqWidget } = await load();
    initializeAgentRafeeqWidget(new AbortController().signal);
    assert.equal(env.mounted().length, 0);
    env.window.consent = 'rejected';
    env.window.dispatchEvent(event('analytics-consent-updated', { status: 'rejected' }));
    initializeAgentRafeeqWidget(new AbortController().signal);
    assert.equal(env.mounted().length, 1);
    assert.equal(env.mounted()[0].dataset.history, 'session');
    assert.equal(env.mounted()[0].dataset.locale, 'ar');
});

test('cookie preferences and private navigation destroy the widget and locale changes remount it', async () => {
    const env = environment();
    const { initializeAgentRafeeqWidget } = await load();
    const controller = new AbortController();
    initializeAgentRafeeqWidget(controller.signal);
    let destroyed = 0;
    const host = new Surface();
    host.destroy = () => { destroyed += 1; host.remove(); };
    env.hosts.push(host);
    env.window.dispatchEvent(event('cookie-consent-visibility-changed', { visible: true }));
    assert.equal(env.mounted().length, 0);
    assert.equal(destroyed, 1);
    env.window.dispatchEvent(event('cookie-consent-visibility-changed', { visible: false }));
    assert.equal(env.mounted().length, 1);
    controller.abort();
    env.marker.dataset.locale = 'en';
    initializeAgentRafeeqWidget(new AbortController().signal);
    assert.equal(env.mounted().length, 1);
    assert.equal(env.mounted()[0].dataset.locale, 'en');
    env.document.marker = null;
    initializeAgentRafeeqWidget(new AbortController().signal);
    assert.equal(env.mounted().length, 0);
    assert.equal(env.classes.size, 0);
});

test('unsafe script URLs cannot mount or leak URL credentials', async () => {
    const { initializeAgentRafeeqWidget } = await load();
    for (const url of ['http://agent.example.com/widget.js', 'https://user:password@agent.example.com/widget.js', 'javascript:alert(1)', 'https://agent.example.com/widget.js?token=private']) {
        const env = environment({ url });
        initializeAgentRafeeqWidget(new AbortController().signal);
        assert.equal(env.mounted().length, 0, url);
    }
});

test('a failed script removes the launcher offset and can be retried on the next initialization', async () => {
    const env = environment();
    const { initializeAgentRafeeqWidget } = await load();
    initializeAgentRafeeqWidget(new AbortController().signal);
    env.mounted()[0].dispatchEvent(new Event('error'));
    assert.equal(env.mounted().length, 0);
    assert.equal(env.classes.size, 0);
    initializeAgentRafeeqWidget(new AbortController().signal);
    assert.equal(env.mounted().length, 1);
});

test('versioned widget scripts keep a single raw hexadecimal cache-bust parameter', async () => {
    const { initializeAgentRafeeqWidget } = await load();
    for (const hash of ['a'.repeat(16), '9'.repeat(32), 'Ab09'.repeat(16)]) {
        const url = `https://agent.example.com/widget.js?v=${hash}`;
        const env = environment({ url });
        initializeAgentRafeeqWidget(new AbortController().signal);
        assert.equal(env.mounted().length, 1);
        assert.equal(env.mounted()[0].src, url);
    }
});

test('version queries reject alternate encodings, extra parameters, empty queries, and fragments', async () => {
    const { initializeAgentRafeeqWidget } = await load();
    const hash = 'a'.repeat(16);
    for (const suffix of [
        '?', '?v=', `?v=${'a'.repeat(15)}`, `?v=${'a'.repeat(65)}`, `?v=${'g'.repeat(16)}`,
        `?v=${hash}&v=${hash}`, `?v=${hash}&token=secret`, `?token=secret&v=${hash}`,
        `?v=${hash}&`, `?v=${hash};`, `?v=${hash}?`, `?v=${hash}%20`, `?v=${hash}+`,
        `?v=${hash}%0A`, `?v=${hash}\n`, `?v=${hash.slice(0, 8)}\t${hash.slice(8)}`,
        `?V=${hash}`, `?%76=${hash}`, `?v=%61${'a'.repeat(15)}`, `?v[]=${hash}`,
        `?v=${hash}#fragment`, `?v=${hash}#`, '#', '#fragment',
    ]) {
        const env = environment({ url: `https://agent.example.com/widget.js${suffix}` });
        initializeAgentRafeeqWidget(new AbortController().signal);
        assert.equal(env.mounted().length, 0, JSON.stringify(suffix));
    }
});

test('changing the version replaces the existing script without duplicate widgets', async () => {
    const env = environment({ url: `https://agent.example.com/widget.js?v=${'a'.repeat(16)}` });
    const { initializeAgentRafeeqWidget } = await load();
    initializeAgentRafeeqWidget(new AbortController().signal);
    const previous = env.mounted()[0];
    env.marker.dataset.widgetUrl = `https://agent.example.com/widget.js?v=${'b'.repeat(16)}`;
    initializeAgentRafeeqWidget(new AbortController().signal);
    assert.equal(previous.isConnected, false);
    assert.equal(env.mounted().length, 1);
    assert.equal(env.mounted()[0].src, env.marker.dataset.widgetUrl);
});
