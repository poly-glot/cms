document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = button.parentElement.querySelector('.auth-field__input');
        if (!input) {
            return;
        }

        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        button.setAttribute('aria-pressed', showing ? 'false' : 'true');
        button.textContent = showing ? 'Show' : 'Hide';
        button.setAttribute('aria-label', (showing ? 'Show' : 'Hide') + ' password');
    });
});
