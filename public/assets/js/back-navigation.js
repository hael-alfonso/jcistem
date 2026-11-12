(() => {
    'use strict';

    const backLinkSelector = [
        'a.back-link',
        'a[data-history-back]',
        'a.icon-nav-button[aria-label^="Back"]',
        'a[aria-label^="Back"]:not(.auth-home):not(.auth-recovery-back):not([data-auth-nav])',
    ].join(',');

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        const link = event.target.closest(backLinkSelector);
        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

        // Follow the actual same-tab path when available so list filters,
        // pagination, and scroll position are restored by browser history.
        // Keep each link's normal destination as the safe direct-entry fallback.
        try {
            const previous = document.referrer ? new URL(document.referrer) : null;
            if (previous && previous.origin === window.location.origin && window.history.length > 1) {
                event.preventDefault();
                window.history.back();
            }
        } catch (_) {
            // Malformed or unavailable referrers simply use the link destination.
        }
    });
})();
