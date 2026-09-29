import { postAction } from './kit/http.mjs';

const container = document.querySelector('[data-comments]');

if (container) {
    const reloadIf = (ok) => {
        if (ok) {
            location.reload();
        }
    };

    container.addEventListener('click', async (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const card = target.closest('[data-comment-id]');
        const id = card instanceof HTMLElement ? card.dataset.commentId : undefined;

        const actionButton = target.closest('[data-comment-action]');
        if (actionButton instanceof HTMLElement && id) {
            const action = actionButton.dataset.commentAction;
            if (action === 'delete' && !window.confirm('Delete this comment permanently?')) {
                return;
            }
            reloadIf(await postAction(`/comments/${action}/${id}`));

            return;
        }

        if (target.closest('[data-reply-toggle]') && card) {
            const form = card.querySelector('[data-reply-form]');
            if (form) {
                form.hidden = !form.hidden;
                if (!form.hidden) {
                    form.querySelector('[data-reply-body]')?.focus();
                }
            }

            return;
        }

        if (target.closest('[data-reply-cancel]')) {
            const form = target.closest('[data-reply-form]');
            if (form) {
                form.hidden = true;
            }

            return;
        }

        if (target.closest('[data-reply-submit]') && id) {
            const body = card.querySelector('[data-reply-body]')?.value ?? '';
            if (body.trim() === '') {
                return;
            }
            const form = new FormData();
            form.append('body', body);
            reloadIf(await postAction(`/comments/reply/${id}`, form));
        }
    });

    document.querySelector('[data-comments-mark-read]')?.addEventListener('click', async () => {
        reloadIf(await postAction('/comments/mark-all-read'));
    });

    document.querySelector('[data-comments-approve-selected]')?.addEventListener('click', async () => {
        const ids = [...container.querySelectorAll('[data-comment-check]:checked')].map((checkbox) => checkbox.value);
        if (ids.length === 0) {
            return;
        }
        const form = new FormData();
        for (const id of ids) {
            form.append('ids[]', id);
        }
        reloadIf(await postAction('/comments/approve-selected', form));
    });
}
