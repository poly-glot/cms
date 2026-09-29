import { ADMIN_BASE, getJson } from './kit/http.mjs';
import { el } from './kit/dom.mjs';
import { openMediaPicker } from './media-picker.mjs';

const thumbUrl = (item) => item.thumbUrl ?? `${ADMIN_BASE}/media/serve/${item.id}?rendition=thumb`;

for (const slot of document.querySelectorAll('[data-media-slot]')) {
    const kind = slot.dataset.kind ?? 'all';
    const input = slot.querySelector('[data-slot-input]');
    const thumb = slot.querySelector('[data-slot-thumb]');
    const nameEl = slot.querySelector('[data-slot-name]');
    const subEl = slot.querySelector('[data-slot-sub]');
    const chooseButton = slot.querySelector('[data-slot-choose]');

    const fill = (item) => {
        thumb.replaceChildren();
        if (item.isImage) {
            const img = el('img');
            img.src = thumbUrl(item);
            img.alt = item.name;
            thumb.appendChild(img);
        } else {
            thumb.textContent = (item.extension ?? '').toUpperCase();
        }
        nameEl.textContent = item.name;
        subEl.textContent =
            item.isImage && item.width && item.height
                ? `${item.width}×${item.height}`
                : (item.extension ?? '').toUpperCase();
        if (chooseButton) {
            chooseButton.textContent = kind === 'image' ? 'Change image' : 'Change file';
        }
    };

    const current = input?.value.trim() ?? '';
    if (current !== '') {
        getJson(`/media/detail/${current}`)
            .then(fill)
            .catch(() => {});
    }

    chooseButton?.addEventListener('click', async () => {
        const picked = await openMediaPicker({ kind });
        if (picked === null) {
            return;
        }
        input.value = String(picked.id);
        fill(picked);
        document.dispatchEvent(new CustomEvent('cms:block-media', { detail: picked }));
    });
}
