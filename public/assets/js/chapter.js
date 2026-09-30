(() => {
  const toggle = document.querySelector('.menu-button');
  const overlay = document.querySelector('.nav-overlay');
  const setNavigation = (open) => {
    document.body.classList.toggle('nav-open', open);
    toggle?.setAttribute('aria-expanded', String(open));
    toggle?.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
  };
  toggle?.addEventListener('click', () => setNavigation(!document.body.classList.contains('nav-open')));
  overlay?.addEventListener('click', () => setNavigation(false));
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') setNavigation(false); });
  document.querySelectorAll('[data-print]').forEach((button) => button.addEventListener('click', () => window.print()));
  document.querySelectorAll('[data-confirm]').forEach((button) => button.addEventListener('click', (event) => {
    if (!window.confirm(button.dataset.confirm)) event.preventDefault();
  }));
  document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', () => {
    if (!form.checkValidity()) return;
    form.setAttribute('aria-busy', 'true');
    // Keep submitter values in the request; do not disable action buttons.
  }));
})();

