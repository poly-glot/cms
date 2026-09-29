import { formatBytes } from './kit/dom.mjs';
import { ADMIN_BASE } from './kit/http.mjs';

const form = document.querySelector('[data-block-form]');
const stage = document.querySelector('[data-block-preview]');

if (form && stage) {
    for (const node of stage.querySelectorAll('[data-preview-bind]')) {
        const input = form.querySelector(`[name="${node.dataset.previewBind}"]`);
        if (!input) {
            continue;
        }

        const apply = () => {
            const filled = input.value.trim() !== '';
            node.textContent = filled ? input.value : (node.dataset.previewFallback ?? '');

            const optional = node.closest('[data-preview-optional]');
            if (optional) {
                optional.hidden = !filled;
            }
        };

        input.addEventListener('input', apply);
        apply();
    }

    document.addEventListener('cms:block-media', (event) => {
        const item = event.detail;

        const img = stage.querySelector('[data-preview-img]');
        if (img) {
            img.src = `${ADMIN_BASE}/media/serve/${Number(item.id)}?rendition=large`;
            img.alt = form.querySelector('[name="data[alt]"]')?.value || item.name;
        }

        const ext = stage.querySelector('[data-preview-ext]');
        if (ext) {
            ext.textContent = (item.extension ?? '').toUpperCase();
        }

        const meta = stage.querySelector('[data-preview-meta]');
        if (meta) {
            meta.textContent = `${item.name} · ${formatBytes(item.size)}`;
        }

        const title = stage.querySelector('[data-preview-fallback]');
        if (title) {
            title.dataset.previewFallback = item.name;
            if ((form.querySelector('[name="data[label]"]')?.value ?? '').trim() === '') {
                title.textContent = item.name;
            }
        }

        stage.querySelector('[data-preview-root]')?.removeAttribute('data-empty');
    });
}
