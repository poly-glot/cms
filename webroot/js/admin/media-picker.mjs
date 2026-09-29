import { getJson } from './kit/http.mjs';
import { debounce, el } from './kit/dom.mjs';
import { createModal } from './kit/modal.mjs';
import { mediaCardNode } from './media-ui.mjs';

let modal = null;

export async function openMediaPicker({ kind = 'all' } = {}) {
    modal ??= createModal();

    return new Promise((resolve) => {
        let selectedId = null;
        let settled = false;
        let page = 1;
        let term = '';
        let inFlight = null;
        const loaded = new Map();

        const done = (result) => {
            if (settled) {
                return;
            }
            settled = true;
            resolve(result);
            modal.close();
        };

        const search = el('input', 'cms-media__search');
        search.type = 'search';
        search.placeholder = 'Search by name…';
        const searchWrap = el('div', 'cms-picker__search-wrap');
        searchWrap.appendChild(search);

        const gridEl = el('div', 'cms-picker-grid');
        const status = el('div', 'cms-picker__status');
        const moreButton = el('button', 'cms-btn cms-picker__more', 'Load more');
        moreButton.type = 'button';
        moreButton.hidden = true;

        const body = el('div');
        body.append(searchWrap, gridEl, moreButton, status);

        const cancel = el('button', 'cms-btn', 'Cancel');
        cancel.type = 'button';
        cancel.addEventListener('click', () => done(null));

        const confirm = el('button', 'cms-btn cms-btn--primary', 'Use selected');
        confirm.type = 'button';
        confirm.disabled = true;
        confirm.addEventListener('click', () => done(loaded.get(selectedId) ?? null));

        const group = el('div', 'cms-modal__footer-group');
        group.append(cancel, confirm);
        const hint = el('span', 'cms-modal__hint', 'Click to select · double-click to confirm');

        const select = (id) => {
            selectedId = id;
            confirm.disabled = false;
            for (const card of gridEl.querySelectorAll('[data-media-id]')) {
                card.classList.toggle('cms-media-card--selected', Number(card.dataset.mediaId) === id);
            }
        };

        const appendItems = (items) => {
            for (const item of items) {
                loaded.set(item.id, item);
                const card = mediaCardNode(item, { selected: item.id === selectedId });
                card.addEventListener('click', () => select(item.id));
                card.addEventListener('dblclick', () => done(item));
                gridEl.appendChild(card);
            }
        };

        const load = async (reset) => {
            inFlight?.abort();
            const request = new AbortController();
            inFlight = request;
            moreButton.disabled = true;
            if (reset) {
                page = 1;
                selectedId = null;
                confirm.disabled = true;
            }

            try {
                const params = new URLSearchParams({ kind, page: String(page) });
                if (term !== '') {
                    params.set('q', term);
                }
                const data = await getJson(`/media/library?${params}`, { signal: request.signal });

                if (reset) {
                    gridEl.replaceChildren();
                }
                appendItems(data.items ?? []);

                const total = data.total ?? 0;
                const shown = gridEl.querySelectorAll('[data-media-id]').length;
                moreButton.hidden = !data.hasMore;
                status.textContent = total === 0 ? 'No files match.' : `Showing ${shown} of ${total}`;
            } catch {
                if (request.signal.aborted) {
                    return;
                }
                status.textContent = 'Could not load media.';
            }
            moreButton.disabled = false;
        };

        moreButton.addEventListener('click', () => {
            page += 1;
            load(false);
        });

        search.addEventListener(
            'input',
            debounce(() => {
                term = search.value.trim();
                load(true);
            }, 250),
        );

        const title = kind === 'image' ? 'Choose an image' : 'Choose a file';
        modal.open({ title, body, footer: [hint, group], onClose: () => done(null) });
        load(true);
    });
}
