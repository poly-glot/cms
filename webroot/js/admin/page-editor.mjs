import {
    StarterKit,
    BlockReference,
    Image,
    Link,
    TextAlign,
    Table,
    TableRow,
    TableHeader,
    TableCell,
} from './vendor/tiptap.bundle.mjs';
import { ADMIN_BASE, getJson, patchForm, postAction, postForm } from './kit/http.mjs';
import { slugify } from './kit/slug.mjs';
import { attachSuggest } from './kit/suggest.mjs';
import { mountRichText as createRichText } from './rich-text.mjs';
import { openMediaPicker } from './media-picker.mjs';
import { debounce, el, svgIcon } from './kit/dom.mjs';
import { createModal } from './kit/modal.mjs';
import { bindInlineTitle } from './inline-title.mjs';
import { mountFieldWidgets } from './field-widgets.mjs';

const RENDITIONS = [
    ['large', 'Large'],
    ['medium', 'Medium'],
    ['small', 'Small'],
    ['thumb', 'Thumbnail'],
];

const renditionOf = (src) => {
    const match = /[?&]rendition=([^&]+)/.exec(src ?? '');

    return match ? match[1] : 'large';
};

const withRendition = (src, rendition) => {
    const base = (src ?? '').replace(/([?&])rendition=[^&]*/, '$1').replace(/[?&]$/, '');

    return base + (base.includes('?') ? '&' : '?') + 'rendition=' + rendition;
};

let imageModal = null;

function openImageEditor({ attrs, apply }) {
    imageModal ??= createModal();

    const altInput = el('input');
    altInput.type = 'text';
    altInput.value = attrs.alt ?? '';
    altInput.placeholder = 'Describe the image for screen readers';
    const altField = el('label', 'cms-field');
    altField.append('Alt text', altInput);

    const linkInput = el('input');
    linkInput.type = 'url';
    linkInput.value = attrs.href ?? '';
    linkInput.placeholder = 'https://…  (leave blank for no link)';
    const linkField = el('label', 'cms-field');
    linkField.append('Link', linkInput);

    const renditionSelect = el('select');
    for (const [value, label] of RENDITIONS) {
        const option = el('option', null, label);
        option.value = value;
        option.selected = value === renditionOf(attrs.src);
        renditionSelect.appendChild(option);
    }
    const renditionField = el('label', 'cms-field');
    renditionField.append('Size', renditionSelect);

    const body = el('div', 'cms-imgedit');
    body.append(altField, linkField, renditionField);

    const cancel = el('button', 'cms-btn', 'Cancel');
    cancel.type = 'button';
    cancel.addEventListener('click', () => imageModal.close());

    const save = el('button', 'cms-btn cms-btn--primary', 'Save');
    save.type = 'button';
    save.addEventListener('click', () => {
        apply({
            alt: altInput.value,
            href: linkInput.value.trim() || null,
            src: withRendition(attrs.src, renditionSelect.value),
        });
        imageModal.close();
    });

    const group = el('div', 'cms-modal__footer-group');
    group.append(cancel, save);

    imageModal.open({ title: 'Image settings', body, footer: [group] });
    altInput.focus();
}

let linkModal = null;

function openLinkEditor(editor) {
    linkModal ??= createModal();

    const isLink = editor.isActive('link');
    const { from, to } = editor.state.selection;
    const hasSelection = from !== to;

    const input = el('input');
    input.type = 'url';
    input.value = editor.getAttributes('link').href ?? '';
    input.placeholder = 'https://…  or  mailto:…';
    const field = el('label', 'cms-field');
    field.append('Link URL', input);

    const body = el('div', 'cms-imgedit');
    body.append(field);

    const apply = () => {
        const url = input.value.trim();
        linkModal.close();

        if (url === '') {
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
        } else if (hasSelection || isLink) {
            editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        } else {
            editor
                .chain()
                .focus()
                .insertContent({ type: 'text', text: url, marks: [{ type: 'link', attrs: { href: url } }] })
                .run();
        }
    };
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            apply();
        }
    });

    const cancel = el('button', 'cms-btn', 'Cancel');
    cancel.type = 'button';
    cancel.addEventListener('click', () => linkModal.close());

    const save = el('button', 'cms-btn cms-btn--primary', 'Save');
    save.type = 'button';
    save.addEventListener('click', apply);

    const group = el('div', 'cms-modal__footer-group');
    if (isLink) {
        const remove = el('button', 'cms-btn cms-btn--danger', 'Remove');
        remove.type = 'button';
        remove.addEventListener('click', () => {
            linkModal.close();
            editor.chain().focus().extendMarkRange('link').unsetLink().run();
        });
        group.append(remove);
    }
    group.append(cancel, save);

    linkModal.open({ title: 'Link', body, footer: [group] });
    input.focus();
}

const AUTOSAVE_DEBOUNCE_MS = 3000;

const svg = (paths, width = 15) =>
    `<svg width="${width}" height="${width}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg>`;
const ICON_BULLET = svg(
    '<line x1="9" x2="20" y1="6" y2="6"/><line x1="9" x2="20" y1="12" y2="12"/><line x1="9" x2="20" y1="18" y2="18"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>',
);
const ICON_ORDERED = svg(
    '<line x1="10" x2="20" y1="6" y2="6"/><line x1="10" x2="20" y1="12" y2="12"/><line x1="10" x2="20" y1="18" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.4-2-1"/>',
);
const ICON_QUOTE = svg(
    '<path d="M6 5v14" stroke-width="2.5"/><line x1="10" x2="20" y1="8" y2="8"/><line x1="10" x2="20" y1="13" y2="13"/><line x1="10" x2="16" y1="18" y2="18"/>',
);
const ICON_PLUS = svg('<path d="M12 5v14M5 12h14"/>', 12);
const ICON_IMAGE = svg(
    '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>',
    14,
);
const ICON_ALIGN_LEFT = svg(
    '<line x1="4" x2="14" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="12" y1="18" y2="18"/>',
);
const ICON_ALIGN_CENTER = svg(
    '<line x1="7" x2="17" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="7" x2="17" y1="18" y2="18"/>',
);
const ICON_ALIGN_RIGHT = svg(
    '<line x1="10" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="12" x2="20" y1="18" y2="18"/>',
);
const ICON_LINK = svg(
    '<path d="M10 13a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1"/>',
);
const ICON_TABLE = svg(
    '<rect x="3" y="3" width="18" height="18" rx="1.5"/><line x1="3" x2="21" y1="9" y2="9"/><line x1="3" x2="21" y1="15" y2="15"/><line x1="9" x2="9" y1="3" y2="21"/><line x1="15" x2="15" y1="3" y2="21"/>',
);

const appendAtDocumentEnd = (editor, content) =>
    editor.chain().focus('end').insertContentAt(editor.state.doc.content.size, content).run();

function createBlockPicker() {
    const modal = createModal();
    const grid = el('div', 'cms-picker__body');
    grid.append(el('p', null, 'Loading…'));

    const newBlockLink = el('a', null, '＋ New block');
    newBlockLink.href = `${ADMIN_BASE}/blocks/add`;
    newBlockLink.target = '_blank';
    newBlockLink.rel = 'noopener';

    let loaded = false;
    let activeEditor = null;

    const blockCard = (block) => {
        const card = el('button', 'cms-picker__card');
        card.type = 'button';
        card.append(el('span', 'cms-picker__card-type', block.type), el('span', 'cms-picker__card-name', block.name));
        card.addEventListener('click', () => {
            modal.close();
            appendAtDocumentEnd(activeEditor, `<div data-block="${Number(block.id)}"></div>`);
        });

        return card;
    };

    const load = async () => {
        try {
            const blocks = await getJson('/blocks/list');
            const emptyHint = el('p', null, 'No blocks yet. Use “New block” below to create one.');
            grid.replaceChildren(...(blocks.length === 0 ? [emptyHint] : blocks.map(blockCard)));
        } catch {
            grid.replaceChildren(el('p', null, 'Could not load blocks.'));
        }
    };

    return {
        open(editor) {
            activeEditor = editor;
            if (!loaded) {
                loaded = true;
                load();
            }
            modal.open({ title: 'Insert block', body: grid, footer: [newBlockLink] });
        },
    };
}

const sidebar = document.querySelector('[data-pages-sidebar]');
const pageId = sidebar?.dataset.pageId ? Number(sidebar.dataset.pageId) : 0;
const textareas = document.querySelectorAll('textarea[data-tiptap]');
const picker = textareas.length > 0 ? createBlockPicker() : null;

for (const textarea of textareas) {
    const { editor, wrapper, toolbar, surface, syncers, sync } = createRichText(textarea, {
        extensions: [
            StarterKit,
            BlockReference.configure({ editBase: ADMIN_BASE }),
            Image.configure({ onEdit: openImageEditor }),
            Link.configure({ openOnClick: false, autolink: false }),
            TextAlign.configure({ types: ['heading', 'paragraph'] }),
            Table.configure({ resizable: false }),
            TableRow,
            TableHeader,
            TableCell,
        ],
        classes: {
            wrapper: 'cms-editor',
            toolbar: 'cms-editor__toolbar',
            surface: 'cms-editor__surface',
            group: 'cms-toolbar__group',
            button: 'cms-toolbar__btn',
            active: 'cms-toolbar__btn--active',
        },
        groups: (editor) => [
            [
                {
                    title: 'Bold',
                    label: 'B',
                    run: () => editor.chain().focus().toggleBold().run(),
                    active: () => editor.isActive('bold'),
                },
                {
                    title: 'Italic',
                    label: 'I',
                    cls: 'cms-toolbar__btn--italic',
                    run: () => editor.chain().focus().toggleItalic().run(),
                    active: () => editor.isActive('italic'),
                },
                {
                    title: 'Strikethrough',
                    label: 'S',
                    cls: 'cms-toolbar__btn--strike',
                    run: () => editor.chain().focus().toggleStrike().run(),
                    active: () => editor.isActive('strike'),
                },
            ],
            [
                {
                    title: 'Bulleted list',
                    icon: ICON_BULLET,
                    run: () => editor.chain().focus().toggleBulletList().run(),
                    active: () => editor.isActive('bulletList'),
                },
                {
                    title: 'Numbered list',
                    icon: ICON_ORDERED,
                    run: () => editor.chain().focus().toggleOrderedList().run(),
                    active: () => editor.isActive('orderedList'),
                },
                {
                    title: 'Quote',
                    icon: ICON_QUOTE,
                    run: () => editor.chain().focus().toggleBlockquote().run(),
                    active: () => editor.isActive('blockquote'),
                },
            ],
            [
                {
                    title: 'Align left',
                    icon: ICON_ALIGN_LEFT,
                    run: () => editor.chain().focus().setTextAlign('left').run(),
                    active: () => editor.isActive({ textAlign: 'left' }),
                },
                {
                    title: 'Align center',
                    icon: ICON_ALIGN_CENTER,
                    run: () => editor.chain().focus().setTextAlign('center').run(),
                    active: () => editor.isActive({ textAlign: 'center' }),
                },
                {
                    title: 'Align right',
                    icon: ICON_ALIGN_RIGHT,
                    run: () => editor.chain().focus().setTextAlign('right').run(),
                    active: () => editor.isActive({ textAlign: 'right' }),
                },
            ],
            [
                {
                    title: 'Link',
                    icon: ICON_LINK,
                    run: () => openLinkEditor(editor),
                    active: () => editor.isActive('link'),
                },
                {
                    title: 'Table',
                    icon: ICON_TABLE,
                    run: () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
                    active: () => editor.isActive('table'),
                },
            ],
        ],
    });

    const sizeOption = (value, text) => {
        const option = el('option', null, text);
        option.value = value;

        return option;
    };
    const sizeSelect = el('select', 'cms-toolbar__size');
    sizeSelect.setAttribute('aria-label', 'Text style');
    sizeSelect.append(
        sizeOption('p', 'Normal'),
        ...[1, 2, 3, 4, 5, 6].map((level) => sizeOption(String(level), `Heading ${level}`)),
    );
    sizeSelect.addEventListener('change', () => {
        if (sizeSelect.value === 'p') {
            editor.chain().focus().setParagraph().run();

            return;
        }
        editor
            .chain()
            .focus()
            .setHeading({ level: Number(sizeSelect.value) })
            .run();
    });
    toolbar.appendChild(sizeSelect);

    const currentBlock = () => {
        for (const level of [1, 2, 3, 4, 5, 6]) {
            if (editor.isActive('heading', { level })) {
                return String(level);
            }
        }

        return 'p';
    };
    syncers.push(() => {
        sizeSelect.value = currentBlock();
    });

    const tableTools = document.createElement('div');
    tableTools.className = 'cms-editor__tabletools';
    tableTools.hidden = true;
    const tableOps = [
        { label: 'Header row', run: () => editor.chain().focus().toggleHeaderRow().run() },
        { label: '+ Row', run: () => editor.chain().focus().addRowAfter().run() },
        { label: '− Row', run: () => editor.chain().focus().deleteRow().run() },
        { label: '+ Column', run: () => editor.chain().focus().addColumnAfter().run() },
        { label: '− Column', run: () => editor.chain().focus().deleteColumn().run() },
        { label: 'Delete table', run: () => editor.chain().focus().deleteTable().run() },
    ];
    for (const op of tableOps) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'cms-tabletools__btn';
        button.textContent = op.label;
        button.addEventListener('click', op.run);
        tableTools.appendChild(button);
    }
    wrapper.insertBefore(tableTools, surface);
    syncers.push(() => {
        tableTools.hidden = !editor.isActive('table');
    });

    const actions = document.createElement('div');
    actions.className = 'cms-editor__actions';

    if (picker) {
        const insertButton = document.createElement('button');
        insertButton.type = 'button';
        insertButton.className = 'cms-toolbar__insert';
        insertButton.append(svgIcon(ICON_PLUS), el('span', null, 'Insert Block'));
        insertButton.addEventListener('click', () => picker.open(editor));
        actions.appendChild(insertButton);
    }

    const imageButton = document.createElement('button');
    imageButton.type = 'button';
    imageButton.className = 'cms-toolbar__insert';
    imageButton.append(svgIcon(ICON_IMAGE), el('span', null, 'Insert Image'));
    imageButton.addEventListener('click', async () => {
        const picked = await openMediaPicker({ kind: 'image' });
        if (picked === null) {
            return;
        }

        appendAtDocumentEnd(editor, {
            type: 'image',
            attrs: { src: picked.publicUrl, alt: picked.alt || picked.name },
        });
    });
    actions.appendChild(imageButton);
    toolbar.appendChild(actions);

    sync();

    const refreshEmpty = () => surface.classList.toggle('cms-editor__surface--empty', editor.isEmpty);
    editor.on('update', refreshEmpty);
    refreshEmpty();

    const form = textarea.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            textarea.value = editor.getHTML();
        });
    }

    if (pageId > 0 && form) {
        const slugInput = form.querySelector('input[name="slug"]');

        const autosave = async () => {
            textarea.value = editor.getHTML();

            const body = new URLSearchParams(new FormData(form));
            body.delete('_method');

            try {
                await patchForm(`/pages/autosave/${pageId}`, body);
                setAutosaveState('saved');
            } catch {
                setAutosaveState('error');
            }
        };

        const debouncedAutosave = debounce(autosave, AUTOSAVE_DEBOUNCE_MS);
        const scheduleAutosave = () => {
            setAutosaveState('saving');
            debouncedAutosave();
        };

        editor.on('update', scheduleAutosave);
        document.querySelector('[data-title-input]')?.addEventListener('input', scheduleAutosave);
        slugInput?.addEventListener('input', scheduleAutosave);
        form.addEventListener('change', scheduleAutosave);
        form.addEventListener('cms:autosave', scheduleAutosave);
    }
}

bindInlineTitle();
bindDetailsEdit();
bindAuthorPicker();
bindRevisionRestore();
mountFieldWidgets(document);

function bindDetailsEdit() {
    const card = document.querySelector('[data-details-card]');
    if (!card) {
        return;
    }

    const toggle = card.querySelector('[data-details-edit]');
    const readView = card.querySelector('[data-details-read]');
    const fields = card.querySelector('[data-details-fields]');
    if (!toggle || !readView || !fields) {
        return;
    }

    toggle.addEventListener('click', () => {
        const entering = fields.hidden;
        fields.hidden = !entering;
        readView.hidden = entering;
        toggle.setAttribute('aria-expanded', String(entering));
        toggle.textContent = entering ? 'Done' : 'Edit';
    });
}

function bindAuthorPicker() {
    const card = document.querySelector('[data-author-card]');
    if (!card) {
        return;
    }

    const picker = card.querySelector('[data-author-picker]');
    const hidden = card.querySelector('[data-author-id]');
    const avatar = card.querySelector('[data-author-avatar]');
    const nameEl = card.querySelector('[data-author-name]');
    const subEl = card.querySelector('[data-author-sub]');
    if (!picker || !hidden) {
        return;
    }

    picker.addEventListener('click', (event) => {
        const option = event.target.closest('[data-author-option]');
        if (!option) {
            return;
        }

        avatar?.setAttribute('style', option.dataset.style ?? '');
        if (nameEl) {
            nameEl.textContent = option.dataset.name ?? '';
        }
        if (subEl) {
            subEl.textContent = option.dataset.sub ?? '';
        }
        hidden.value = option.dataset.id ?? '';

        for (const item of picker.querySelectorAll('[data-author-option]')) {
            const chosen = item === option;
            item.setAttribute('aria-selected', String(chosen));

            const check = item.querySelector('[data-author-check]');
            if (check) {
                check.textContent = chosen ? '✓' : '';
            }
        }

        picker.hidePopover();
        hidden.form?.dispatchEvent(new Event('cms:autosave'));
    });
}

function bindRevisionRestore() {
    const card = document.querySelector('[data-revisions-card]');
    if (!card) {
        return;
    }

    const pageId = Number(card.dataset.pageId);

    card.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-restore-version]');
        if (!button) {
            return;
        }

        const version = button.dataset.restoreVersion;
        if (!window.confirm(`Restore version ${version}? Your current changes will be replaced.`)) {
            return;
        }

        if (await postAction(`/pages/restore/${pageId}/${version}`)) {
            window.location.reload();
        }
    });
}

function setAutosaveState(state) {
    const badge = document.querySelector('[data-autosave]');
    const indicator = document.querySelector('[data-saved-indicator]');
    if (!badge || !indicator) {
        return;
    }
    badge.classList.toggle('cms-autosave--saving', state === 'saving');
    badge.classList.toggle('cms-autosave--saved', state === 'saved');
    badge.classList.remove('cms-autosave--idle');
    indicator.textContent = state === 'saving' ? 'Saving…' : state === 'error' ? 'Not saved' : 'Auto-saved';
}

const tagsCard = document.querySelector('[data-tags-card]');
if (tagsCard) {
    const tagsBase = tagsCard.dataset.tagsBase;
    const list = tagsCard.querySelector('[data-tag-list]');
    const input = tagsCard.querySelector('[data-tag-input]');
    const suggest = tagsCard.querySelector('[data-tag-suggest]');
    const addButton = tagsCard.querySelector('[data-add-tag]');
    const inputWrap = tagsCard.querySelector('[data-tag-input-wrap]');

    const addedSlugs = () => new Set([...list.querySelectorAll('[data-tag-slug]')].map((li) => li.dataset.tagSlug));

    const suggester = attachSuggest(input, suggest, {
        fetchItems: async (term) => {
            const already = addedSlugs();

            return (await getJson(`/tags?q=${encodeURIComponent(term)}`).catch(() => [])).filter(
                (tag) => !already.has(tag.slug),
            );
        },
        onPick: (tag) => addTag(tag.slug),
        onCreate: () => addTag(slugify(input.value)),
        activateFirst: false,
    });

    const collapseTagInput = () => {
        inputWrap?.setAttribute('hidden', '');
        if (addButton) {
            addButton.hidden = false;
        }
    };

    const appendTagPill = (tag) => {
        const li = el('li', 'cms-tag');
        li.dataset.tagSlug = tag.slug;

        const remove = el('button', 'cms-tag__remove', '×');
        remove.type = 'button';
        remove.setAttribute('aria-label', 'Remove');
        remove.dataset.tagRemove = '';

        li.append(el('span', 'cms-tag__label', tag.label), remove);
        list.appendChild(li);
    };

    const addTag = async (slug) => {
        if (slug === '' || addedSlugs().has(slug)) {
            return;
        }
        let tag;
        try {
            tag = await postForm(`${tagsBase}/${slug}`, {});
        } catch {
            return;
        }
        appendTagPill(tag);
        input.value = '';
        suggester.hide();
        input.focus();
    };

    addButton?.addEventListener('click', () => {
        addButton.hidden = true;
        inputWrap?.removeAttribute('hidden');
        input?.focus();
    });

    input?.addEventListener('blur', () => {
        if (input.value.trim() === '') {
            collapseTagInput();
        }
    });

    input?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            input.value = '';
            collapseTagInput();
            addButton?.focus();
        } else if (event.key === ',') {
            event.preventDefault();
            addTag(slugify(input.value));
        }
    });

    list?.addEventListener('click', async (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement) || !target.matches('[data-tag-remove]')) {
            return;
        }
        const li = target.closest('[data-tag-slug]');
        const slug = li?.dataset.tagSlug;
        if (!slug) {
            return;
        }
        try {
            await postForm(`${tagsBase}/${slug}/detach`, {});
            li.remove();
        } catch {
            return;
        }
    });
}
