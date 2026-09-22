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
