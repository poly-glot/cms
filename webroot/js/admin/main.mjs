document.addEventListener('change', (event) => {
    const target = event.target;
    if (target instanceof HTMLElement && target.matches('[data-autosubmit]')) {
        target.closest('form')?.submit();
    }
});
