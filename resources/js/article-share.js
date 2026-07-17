const copyText = async (value) => {
    if (navigator.clipboard?.writeText && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(value);

            return true;
        } catch {
            // Fall through to the selection-based copy for older or restricted browsers.
        }
    }

    const field = document.createElement('textarea');

    field.value = value;
    field.setAttribute('readonly', '');
    field.style.position = 'fixed';
    field.style.insetInlineStart = '-9999px';
    field.style.opacity = '0';
    document.body.append(field);
    field.select();

    let copied = false;

    try {
        copied = document.execCommand('copy');
    } catch {
        copied = false;
    }

    field.remove();

    return copied;
};

export const initializeArticleShare = (signal) => {
    document.querySelectorAll('[data-article-share]').forEach((share) => {
        const nativeButton = share.querySelector('[data-article-native-share]');
        const copyButton = share.querySelector('[data-article-copy-link]');
        const copyLabel = share.querySelector('[data-article-copy-label]');
        const status = share.querySelector('[data-article-share-status]');
        const canonicalUrl = share.dataset.shareUrl;
        let copyResetTimer;

        signal?.addEventListener('abort', () => window.clearTimeout(copyResetTimer), { once: true });

        if (! canonicalUrl) {
            return;
        }

        const shareData = {
            title: share.dataset.shareTitle || document.title,
            text: share.dataset.shareDescription || '',
            url: canonicalUrl,
        };

        const announce = (message) => {
            if (status) {
                status.textContent = message;
            }
        };

        if (nativeButton && typeof navigator.share === 'function') {
            nativeButton.hidden = false;
            nativeButton.addEventListener('click', async () => {
                try {
                    await navigator.share(shareData);
                } catch (error) {
                    if (error?.name !== 'AbortError') {
                        announce(status?.dataset.shareError || '');
                    }
                }
            }, { signal });
        }

        copyButton?.addEventListener('click', async () => {
            const copied = await copyText(shareData.url);

            if (! copied) {
                announce(status?.dataset.copyError || '');

                return;
            }

            copyButton.dataset.state = 'copied';

            if (copyLabel && status?.dataset.copySuccess) {
                copyLabel.textContent = status.dataset.copySuccess;
            }

            announce(status?.dataset.copySuccess || '');

            window.clearTimeout(copyResetTimer);
            copyResetTimer = window.setTimeout(() => {
                delete copyButton.dataset.state;

                if (copyLabel) {
                    copyLabel.textContent = copyLabel.dataset.defaultLabel || '';
                }

                announce('');
            }, 2000);
        }, { signal });
    });
};
