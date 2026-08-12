export const trackConsultationStateOnce = (observedNodes, element, track) => {
    if (observedNodes.has(element)) {
        return false;
    }

    observedNodes.add(element);

    return track();
};
