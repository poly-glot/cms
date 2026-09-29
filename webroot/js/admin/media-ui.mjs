import { el, formatBytes } from './kit/dom.mjs';

export function mediaCardNode(item, { selected = false } = {}) {
    const article = el('article', 'cms-media-card' + (selected ? ' cms-media-card--selected' : ''));
    article.dataset.mediaId = String(item.id);
    article.dataset.kind = item.kind;
    article.dataset.name = (item.name ?? '').toLowerCase();
    article.tabIndex = 0;

    const thumb = el('div', 'cms-media-card__thumb');
    if (item.isImage) {
        const img = el('img');
        img.loading = 'lazy';
        img.alt = item.name;
        img.src = item.thumbUrl;
        thumb.appendChild(img);
    } else {
        const doc = el('div', 'cms-media-card__doc-icon');
        doc.dataset.ext = (item.extension ?? '').toUpperCase();
        thumb.appendChild(doc);
    }
    thumb.appendChild(el('span', 'cms-media-card__badge', item.kind));

    const meta = el('div', 'cms-media-card__meta');
    meta.appendChild(el('div', 'cms-media-card__name', item.name));
    const sub = el('div', 'cms-media-card__sub');
    const primary =
        item.isImage && item.width && item.height
            ? `${item.width}×${item.height}`
            : (item.extension ?? '').toUpperCase();
    sub.append(el('span', null, primary), el('span', null, formatBytes(item.size)));
    meta.appendChild(sub);

    article.append(thumb, meta);

    return article;
}
