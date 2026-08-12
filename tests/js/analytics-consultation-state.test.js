import assert from 'node:assert/strict';
import test from 'node:test';
import { trackConsultationStateOnce } from '../../resources/js/analytics-consultation-state.js';

test('a consultation outcome observed without consent is never sent retroactively', () => {
    const observedNodes = new WeakSet();
    const outcome = {};
    let attempts = 0;

    assert.equal(trackConsultationStateOnce(observedNodes, outcome, () => {
        attempts += 1;

        return false;
    }), false);
    assert.equal(observedNodes.has(outcome), true);

    assert.equal(trackConsultationStateOnce(observedNodes, outcome, () => {
        attempts += 1;

        return true;
    }), false);
    assert.equal(attempts, 1);
});
