(() => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) return;
        button.hidden = false;
        input.parentElement.classList.add('has-toggle');
        button.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.textContent = visible ? 'Hide' : 'Show';
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${button.dataset.fieldLabel}`);
        });
    });
    document.querySelectorAll('[data-auth-form]').forEach((form) => {
        const button = form.querySelector('button[type="submit"]');
        const label = button?.querySelector('[data-submit-label]');
        if (!button || !label) return;
        const originalLabel = label.textContent;
        form.addEventListener('submit', () => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            label.textContent = 'Please wait…';
        });
        window.addEventListener('pageshow', () => {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            label.textContent = originalLabel;
        });
    });
})();
