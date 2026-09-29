export function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) {
        node.className = className;
    }
    if (text !== undefined) {
        node.textContent = text;
    }

    return node;
}

const markupParser = new DOMParser();

export function svgIcon(markup) {
    return markupParser.parseFromString(markup, 'text/html').body.firstElementChild;
}

export function debounce(callback, waitMs) {
    let timer = 0;

    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => callback(...args), waitMs);
    };
}

export function formatBytes(bytes) {
    if (!Number.isFinite(bytes) || bytes <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / 1024 ** exponent;

    return `${exponent === 0 ? value : value.toFixed(1)} ${units[exponent]}`;
}

let toastEl = null;
let toastTimer = null;

export function showToast(message) {
    if (toastEl === null) {
        toastEl = el('div', 'cms-toast');
        toastEl.popover = 'manual';
        document.body.appendChild(toastEl);
    }

    toastEl.hidePopover();
    toastEl.showPopover();
    toastEl.textContent = message;
    requestAnimationFrame(() => toastEl.classList.add('cms-toast--show'));
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => toastEl.classList.remove('cms-toast--show'), 2400);
}
