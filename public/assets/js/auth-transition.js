(() => {
    const panel = document.querySelector('[data-auth-panel]');

    if (!panel) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let navigationTimer;

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-auth-nav]');

        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (reducedMotion.matches) return;

        event.preventDefault();
        window.clearTimeout(navigationTimer);

        const leavingClass = link.dataset.authNav === 'forward'
            ? 'is-leaving-left'
            : 'is-leaving-right';

        panel.classList.remove('is-leaving-left', 'is-leaving-right');
        panel.classList.add(leavingClass);
        panel.setAttribute('aria-hidden', 'true');
        link.setAttribute('aria-disabled', 'true');

        navigationTimer = window.setTimeout(() => window.location.assign(link.href), 300);
    });

    window.addEventListener('pageshow', () => {
        window.clearTimeout(navigationTimer);
        panel.classList.remove('is-leaving-left', 'is-leaving-right');
        panel.removeAttribute('aria-hidden');
    });
})();
