import { getJson, postAction, postUpload } from './kit/http.mjs';
import { el, formatBytes, showToast } from './kit/dom.mjs';
import { createModal } from './kit/modal.mjs';
import { mediaCardNode } from './media-ui.mjs';

const grid = document.querySelector('[data-media-grid]');

if (grid) {
    const fileInput = document.querySelector('[data-upload-input]');
    const modal = createModal();

    const uploadTile = () => grid.querySelector('[data-upload-trigger]');

    const viewParams = new URLSearchParams(location.search);
    const activeKind = viewParams.get('kind') ?? 'all';
    const activeQuery = (viewParams.get('q') ?? '').trim();
    const matchesView = (item) => (activeKind === 'all' || item.kind === activeKind) && activeQuery === '';

    const bumpCount = (delta) => {
        const counter = document.querySelector('[data-media-count]');
        const match = counter?.textContent.match(/\d+/);
        if (!counter || !match) {
            return;
        }
        counter.textContent = counter.textContent.replace(/\d+/, String(Math.max(0, Number(match[0]) + delta)));
    };

    for (const trigger of document.querySelectorAll('[data-upload-trigger]')) {
        trigger.addEventListener('click', () => {
            if (fileInput) {
                fileInput.value = '';
                fileInput.click();
            }
        });
    }
    fileInput?.addEventListener('change', async () => {
        await uploadFiles(fileInput.files);
        fileInput.value = '';
    });

    let dragDepth = 0;
    grid.addEventListener('dragenter', (event) => {
        event.preventDefault();
        dragDepth++;
        uploadTile()?.classList.add('cms-upload-tile--drag');
    });
    grid.addEventListener('dragover', (event) => event.preventDefault());
    grid.addEventListener('dragleave', (event) => {
        event.preventDefault();
        dragDepth--;
        if (dragDepth <= 0) {
            dragDepth = 0;
            uploadTile()?.classList.remove('cms-upload-tile--drag');
        }
    });
    grid.addEventListener('drop', async (event) => {
        event.preventDefault();
        dragDepth = 0;
        uploadTile()?.classList.remove('cms-upload-tile--drag');
        if (event.dataTransfer?.files?.length) {
            await uploadFiles(event.dataTransfer.files);
        }
    });

    async function uploadFiles(files) {
        for (const file of files) {
            try {
                const form = new FormData();
                form.append('file', file);
                const created = await postUpload('/media/upload', form);
                if (matchesView(created)) {
                    const card = mediaCardNode(created);
                    const tile = uploadTile();
                    if (tile) {
                        tile.insertAdjacentElement('afterend', card);
                    } else {
                        grid.prepend(card);
                    }
                    bumpCount(1);
                }
                showToast(`Uploaded ${created.name}`);
            } catch {
                showToast('Upload failed');
            }
        }
    }

    grid.addEventListener('click', (event) => {
        const card = event.target instanceof HTMLElement ? event.target.closest('[data-media-id]') : null;
        if (card) {
            openDetail(Number(card.dataset.mediaId), card);
        }
    });

    async function openDetail(id, card) {
        let detail;
        try {
            detail = await getJson(`/media/detail/${id}`);
        } catch {
            showToast('Could not load media');

            return;
        }
        modal.open({ title: detail.name, ...buildDetail(detail, card) });
    }

    function buildDetail(detail, card) {
        const body = el('div', 'cms-media-detail');
        body.append(buildPreview(detail), buildSidebar(detail));
        return { body, footer: buildFooter(detail, card) };
    }

    function buildPreview(detail) {
        const preview = el('div', 'cms-media-detail__preview');
        if (detail.isImage) {
            const img = el('img');
            img.src = detail.previewUrl;
            img.alt = detail.alt ?? detail.name;
            preview.appendChild(img);
        } else {
            const doc = el('div', 'cms-media-detail__preview-doc');
            doc.appendChild(el('span', 'cms-media-detail__preview-doc-ext', (detail.extension ?? '').toUpperCase()));
            preview.appendChild(doc);
        }

        return preview;
    }

    function buildSidebar(detail) {
        const sidebar = el('div', 'cms-media-detail__sidebar');
        sidebar.append(
            el('h2', 'cms-media-detail__name', detail.name),
            el('div', 'cms-media-detail__path', detail.url),
            detailsSection(detail),
        );
        if (detail.isImage) {
            sidebar.append(altSection(detail), renditionsSection(detail));
        }

        return sidebar;
    }

    function detailsSection(detail) {
        const section = el('div', 'cms-media-detail__section');
        section.appendChild(el('div', 'cms-media-detail__section-title', 'Details'));
        const rows = [
            ['Type', detail.mime],
            ['Size', formatBytes(detail.size)],
        ];
        if (detail.isImage && detail.width && detail.height) {
            rows.splice(1, 0, ['Dimensions', `${detail.width} × ${detail.height}`]);
        }
        rows.push(['Uploaded', new Date(detail.uploaded).toLocaleString()]);
        for (const [key, value] of rows) {
            const row = el('div', 'cms-media-detail__row');
            row.append(el('span', 'cms-media-detail__key', key), el('span', 'cms-media-detail__val', value));
            section.appendChild(row);
        }

        return section;
    }

    function altSection(detail) {
        const section = el('div', 'cms-media-detail__section');
        section.appendChild(el('div', 'cms-media-detail__section-title', 'Alt text'));
        const textarea = el('textarea', 'cms-media-detail__alt');
        textarea.value = detail.alt ?? '';
        textarea.dataset.mediaAlt = '';
        section.appendChild(textarea);

        return section;
    }

    function renditionsSection(detail) {
        const section = el('div', 'cms-media-detail__section');
        section.appendChild(el('div', 'cms-media-detail__section-title', `Renditions (${detail.renditions.length})`));
        const list = el('div', 'cms-renditions');
        for (const rendition of detail.renditions) {
            list.appendChild(renditionRow(rendition));
        }
        section.appendChild(list);

        return section;
    }

    function renditionRow(rendition) {
        const row = el('div', 'cms-rendition');
        const thumb = el('div', 'cms-rendition__thumb');
        const img = el('img');
        img.src = rendition.url;
        img.alt = rendition.name;
        thumb.appendChild(img);

        const info = el('div');
        info.append(
            el('div', 'cms-rendition__name', rendition.name),
            el('div', 'cms-rendition__meta', `${rendition.width}×${rendition.height} · ${formatBytes(rendition.size)}`),
        );

        const copy = el('button', 'cms-rendition__copy', 'Copy URL');
        copy.type = 'button';
        copy.addEventListener('click', async (event) => {
            event.stopPropagation();
            try {
                await navigator.clipboard.writeText(location.origin + rendition.url);
                showToast('URL copied');
            } catch {
                showToast('Copy failed');
            }
        });

        row.append(thumb, info, copy);

        return row;
    }

    function buildFooter(detail, card) {
        const deleteButton = el('button', 'cms-btn', 'Delete');
        deleteButton.type = 'button';
        deleteButton.addEventListener('click', () => deleteMedia(detail, card));

        const closeButton = el('button', 'cms-btn', 'Close');
        closeButton.type = 'button';
        closeButton.addEventListener('click', () => modal.close());

        const group = el('div', 'cms-modal__footer-group');
        group.append(closeButton);

        if (detail.isImage) {
            const saveButton = el('button', 'cms-btn cms-btn--primary', 'Save');
            saveButton.type = 'button';
            saveButton.addEventListener('click', () => saveAlt(detail, card));
            group.append(saveButton);
        }

        return [deleteButton, group];
    }

    async function saveAlt(detail, card) {
        const textarea = document.querySelector('[data-media-alt]');
        const form = new FormData();
        form.append('alt', textarea?.value ?? '');
        try {
            const updated = await postUpload(`/media/update/${detail.id}`, form);
            const img = card.querySelector('img');
            if (img) {
                img.alt = updated.alt ?? detail.name;
            }
            showToast('Saved');
        } catch {
            showToast('Could not save');
        }
    }

    async function deleteMedia(detail, card) {
        if (!window.confirm(`Delete ${detail.name}? This cannot be undone.`)) {
            return;
        }
        if (await postAction(`/media/delete/${detail.id}`)) {
            card.remove();
            bumpCount(-1);
            modal.close();
            showToast('Deleted');
        } else {
            showToast('Could not delete');
        }
    }
}
