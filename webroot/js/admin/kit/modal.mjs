import { el } from './dom.mjs';

export function createModal() {
    const titleNode = el('span');

    const closeButton = el('button', 'cms-modal__close');
    closeButton.type = 'button';
    closeButton.setAttribute('aria-label', 'Close');

    const header = el('header', 'cms-modal__header');
    header.append(titleNode, closeButton);

    const bodyNode = el('div', 'cms-modal__body');
    const footerNode = el('footer', 'cms-modal__footer');

    const dialog = el('dialog', 'cms-modal');
    dialog.append(header, bodyNode, footerNode);
    document.body.appendChild(dialog);

    let onClose = null;
    let pressStartedOnBackdrop = false;

    closeButton.addEventListener('click', () => dialog.close());

    dialog.addEventListener('pointerdown', (event) => {
        pressStartedOnBackdrop = event.target === dialog;
    });
    dialog.addEventListener('click', (event) => {
        if (pressStartedOnBackdrop && event.target === dialog) {
            dialog.close();
        }
    });

    dialog.addEventListener('close', () => {
        const callback = onClose;
        onClose = null;
        callback?.();
    });

    return {
        open({ title = '', body = null, footer = [], onClose: callback = null } = {}) {
            titleNode.textContent = title;
            bodyNode.replaceChildren(body ?? '');
            footerNode.replaceChildren(...footer);
            footerNode.hidden = footer.length === 0;
            onClose = callback;

            if (!dialog.open) {
                dialog.showModal();
            }
        },
        close: () => dialog.close(),
    };
}
