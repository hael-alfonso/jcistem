(() => {
    document.querySelector('[data-dismiss-auth-feedback]')?.addEventListener('click', (event) => {
        event.currentTarget.closest('.auth-feedback')?.remove();
    });
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));
        if (!input) return;
        button.hidden = false;
        input.parentElement.classList.add('has-toggle');
        button.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            button.classList.toggle('is-revealed', visible);
            button.setAttribute('aria-pressed', String(visible));
            button.setAttribute('aria-label', `${visible ? 'Hide' : 'Show'} ${button.dataset.fieldLabel}`);
            button.title = button.getAttribute('aria-label');
        });
    });
    const otpForm = document.querySelector('[data-otp-form]');
    if (otpForm) {
        const fallback = otpForm.querySelector('.auth-otp-fallback');
        const cellGroup = otpForm.querySelector('[data-otp-cells]');
        const cells = [...cellGroup.querySelectorAll('.auth-otp-cell')];
        const initialCode = fallback.value.replace(/\D/g, '').slice(0, cells.length);
        fallback.hidden = true;
        fallback.required = false;
        cellGroup.hidden = false;
        cells.forEach((cell, index) => {
            cell.disabled = false;
            cell.value = initialCode[index] || '';
        });
        const syncCode = () => {
            fallback.value = cells.map((cell) => cell.value).join('');
        };
        const fillFrom = (start, value) => {
            const code = value.replace(/\D/g, '');
            if (code.length === cells.length) start = 0;
            const digits = code.slice(0, cells.length - start);
            if (!digits) return false;
            [...digits].forEach((digit, offset) => { cells[start + offset].value = digit; });
            syncCode();
            cells[Math.min(start + digits.length, cells.length - 1)].focus();
            return true;
        };
        cells.forEach((cell, index) => {
            cell.addEventListener('input', () => {
                const value = cell.value;
                if (value.length > 1) {
                    cell.value = '';
                    fillFrom(index, value);
                    return;
                }
                cell.value = value.replace(/\D/g, '');
                syncCode();
                if (cell.value && index < cells.length - 1) cells[index + 1].focus();
            });
            cell.addEventListener('paste', (event) => {
                const pasted = event.clipboardData?.getData('text') || '';
                if (!/\d/.test(pasted)) return;
                event.preventDefault();
                fillFrom(index, pasted);
            });
            cell.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !cell.value && index > 0) {
                    cells[index - 1].focus();
                    cells[index - 1].value = '';
                    syncCode();
                } else if (event.key === 'ArrowLeft' && index > 0) {
                    event.preventDefault();
                    cells[index - 1].focus();
                } else if (event.key === 'ArrowRight' && index < cells.length - 1) {
                    event.preventDefault();
                    cells[index + 1].focus();
                }
            });
        });
        syncCode();
    }
    document.querySelectorAll('[data-auth-form]').forEach((form) => {
        const button = form.querySelector('button[type="submit"]');
        const label = button?.querySelector('[data-submit-label]');
        if (!button || !label) return;
        const originalLabel = label.textContent;
        form.addEventListener('submit', () => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            label.textContent = form.classList.contains('auth-registration-form') ? 'Registering...' : 'Please wait...';
        });
        window.addEventListener('pageshow', () => {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            label.textContent = originalLabel;
        });
    });
})();
