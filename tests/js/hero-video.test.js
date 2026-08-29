import assert from 'node:assert/strict';
import test from 'node:test';
import { initializeHeroVideos } from '../../resources/js/hero-video.js';

class FakeClassList {
    values = new Set();

    add(...values) {
        values.forEach((value) => this.values.add(value));
    }

    remove(...values) {
        values.forEach((value) => this.values.delete(value));
    }

    contains(value) {
        return this.values.has(value);
    }
}

class FakeEventTarget {
    listeners = new Map();

    addEventListener(type, listener) {
        const listeners = this.listeners.get(type) ?? [];

        listeners.push(listener);
        this.listeners.set(type, listeners);
    }

    dispatch(type) {
        (this.listeners.get(type) ?? []).forEach((listener) => listener({ type }));
    }

    async emit(type) {
        const event = {
            type,
            preventDefault() {},
            stopPropagation() {},
        };

        for (const listener of this.listeners.get(type) ?? []) {
            await listener(event);
        }
    }
}

class FakeElement extends FakeEventTarget {
    attributes = new Map();
    classList = new FakeClassList();
    dataset = {};
    hidden = false;
    selectors = new Map();
    textContent = '';

    getAttribute(name) {
        return this.attributes.get(name) ?? null;
    }

    hasAttribute(name) {
        return this.attributes.has(name);
    }

    querySelector(selector) {
        return this.selectors.get(selector) ?? null;
    }

    removeAttribute(name) {
        this.attributes.delete(name);
    }

    setAttribute(name, value) {
        this.attributes.set(name, String(value));
    }
}

class FakeVideo extends FakeElement {
    currentSrc = '';
    currentTime = 0;
    ended = false;
    loadCalls = 0;
    loop = true;
    muted = false;
    pauseCalls = 0;
    paused = true;
    playCalls = 0;
    src = '';
    stage = null;

    canPlayType() {
        return 'probably';
    }

    closest() {
        return this.stage;
    }

    load() {
        this.loadCalls += 1;
        this.currentSrc = this.src;
        this.setAttribute('src', this.src);
    }

    pause() {
        if (this.paused) {
            return;
        }

        this.pauseCalls += 1;
        this.paused = true;
        this.dispatch('pause');
    }

    async play() {
        this.playCalls += 1;
        this.ended = false;
        this.paused = false;
        await this.emit('playing');
    }
}

const createFixture = ({ prefersReducedMotion = false } = {}) => {
    const stage = new FakeElement();
    const finale = new FakeElement();
    const playback = new FakeElement();
    const playbackLabel = new FakeElement();
    const playIcon = new FakeElement();
    const pauseIcon = new FakeElement();
    const replay = new FakeElement();
    const video = new FakeVideo();
    const animationFrames = [];
    const sessionValues = new Map();

    playback.dataset.labelPlay = 'Play video';
    playback.dataset.labelPause = 'Pause video';
    playback.setAttribute('hidden', '');
    playback.selectors.set('[data-hero-video-toggle-label]', playbackLabel);
    playback.selectors.set('[data-hero-video-play-icon]', playIcon);
    playback.selectors.set('[data-hero-video-pause-icon]', pauseIcon);
    stage.selectors.set('[data-hero-video-finale]', finale);
    stage.selectors.set('[data-hero-video-toggle]', playback);
    stage.selectors.set('[data-hero-video-replay]', replay);
    video.stage = stage;
    video.dataset.viewed = 'false';
    video.dataset.webmSrcHigh = '/videos/hero-full.webm';
    video.dataset.mp4SrcHigh = '/videos/hero-full.mp4';
    video.dataset.webmSrcCompact = '/videos/hero-compact.webm';
    video.dataset.mp4SrcCompact = '/videos/hero-compact.mp4';

    const documentObject = {
        querySelector: () => null,
        querySelectorAll: () => [video],
    };
    const windowObject = {
        cancelAnimationFrame() {},
        fetch: () => Promise.resolve(),
        matchMedia: () => ({ matches: true }),
        requestAnimationFrame: (callback) => {
            animationFrames.push(callback);

            return animationFrames.length;
        },
        sessionStorage: {
            getItem: (key) => sessionValues.get(key) ?? null,
            setItem: (key, value) => sessionValues.set(key, value),
        },
    };
    const abortController = new AbortController();

    initializeHeroVideos(abortController.signal, {
        documentObject,
        navigatorObject: { connection: { effectiveType: '4g', saveData: false } },
        reducedMotion: { matches: prefersReducedMotion },
        windowObject,
    });

    return {
        finale,
        flushAnimationFrames: async () => {
            while (animationFrames.length > 0) {
                await animationFrames.shift()();
            }
        },
        playback,
        playbackLabel,
        replay,
        sessionValues,
        stage,
        video,
    };
};

test('the hero remains poster-first and loads its full video only after the playback control is used', async () => {
    const fixture = createFixture();

    assert.equal(fixture.video.src, '');
    assert.equal(fixture.video.loadCalls, 0);
    assert.equal(fixture.video.playCalls, 0);
    assert.equal(fixture.playbackLabel.textContent, 'Play video');
    assert.equal(fixture.playback.hasAttribute('hidden'), false);

    await fixture.playback.emit('click');

    assert.equal(fixture.video.src, '/videos/hero-full.webm');
    assert.equal(fixture.video.loadCalls, 1);
    assert.equal(fixture.video.playCalls, 1);
    assert.equal(fixture.stage.classList.contains('is-playing'), true);
    assert.equal(fixture.playback.getAttribute('aria-pressed'), 'true');
    assert.equal(fixture.playbackLabel.textContent, 'Pause video');

    await fixture.playback.emit('click');

    assert.equal(fixture.video.pauseCalls, 1);
    assert.equal(fixture.video.loadCalls, 1);
    assert.equal(fixture.playback.getAttribute('aria-pressed'), 'false');
    assert.equal(fixture.playbackLabel.textContent, 'Play video');
});

test('the end state, replay, and reduced-motion path never fetch video before an explicit replay', async () => {
    const fixture = createFixture({ prefersReducedMotion: true });

    assert.equal(fixture.video.loadCalls, 0);
    assert.equal(fixture.video.playCalls, 0);
    assert.equal(fixture.stage.classList.contains('is-complete'), true);
    assert.equal(fixture.finale.getAttribute('aria-hidden'), 'false');
    assert.equal(fixture.playback.hasAttribute('hidden'), true);

    await fixture.replay.emit('click');
    await fixture.flushAnimationFrames();

    assert.equal(fixture.video.loadCalls, 1);
    assert.equal(fixture.video.playCalls, 1);
    assert.equal(fixture.stage.classList.contains('is-complete'), false);
    assert.equal(fixture.stage.classList.contains('is-playing'), true);

    fixture.video.ended = true;
    fixture.video.paused = true;
    await fixture.video.emit('ended');

    assert.equal(fixture.stage.classList.contains('is-complete'), true);
    assert.equal(fixture.finale.getAttribute('aria-hidden'), 'false');
    assert.equal(fixture.sessionValues.get('ibrahim.hero-video.seen.v1'), 'true');

    await fixture.replay.emit('click');
    await fixture.flushAnimationFrames();

    assert.equal(fixture.video.loadCalls, 1);
    assert.equal(fixture.video.playCalls, 2);
    assert.equal(fixture.video.currentTime, 0);
});
