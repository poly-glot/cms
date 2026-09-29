document.addEventListener('click', (event) => {
    const flash = event.target instanceof Element ? event.target.closest('[data-dismiss-flash]') : null;
    if (flash) {
        flash.remove();
    }
});
