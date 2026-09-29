import { StarterKit, Link } from './vendor/tiptap.bundle.mjs';
import { ADMIN_BASE, getJson as httpGetJson } from './kit/http.mjs';
import { slugify } from './kit/slug.mjs';
import { attachSuggest } from './kit/suggest.mjs';
import { makeSortable } from './kit/drag.mjs';
import { mountRichText as createRichText } from './rich-text.mjs';
import { openMediaPicker } from './media-picker.mjs';
import { debounce, el } from './kit/dom.mjs';
import { createModal } from './kit/modal.mjs';

const getJson = (path) => httpGetJson(path).catch(() => []);

let refPicker = null;

export function mountFieldWidgets(root = document) {
    for (const textarea of root.querySelectorAll('textarea[data-collections-tiptap]')) {
        mountRichText(textarea);
    }
    for (const widget of root.querySelectorAll('[data-collections-media]')) {
        mountMedia(widget);
    }
    for (const widget of root.querySelectorAll('[data-collections-ref]')) {
        mountReference(widget);
    }
    for (const widget of root.querySelectorAll('[data-collections-tags]')) {
        mountTags(widget);
    }
    for (const repeater of root.querySelectorAll('[data-repeater]')) {
        mountRepeater(repeater);
    }
}

function mountTags(widget) {
    const inputName = widget.dataset.inputName;
    const pills = widget.querySelector('[data-tags-pills]');
    const inputs = widget.querySelector('[data-tags-inputs]');
    const input = widget.querySelector('[data-tags-input]');
    const suggest = widget.querySelector('[data-tags-suggest]');

    let slugs = JSON.parse(widget.dataset.initial || '[]');

    const render = () => {
        pills.replaceChildren();
        inputs.replaceChildren();
        for (const slug of slugs) {
            const pill = el('span', 'cms-tag');
            pill.append(el('span', 'cms-tag__label', slug));
            const remove = el('button', 'cms-tag__remove', '×');
            remove.type = 'button';
            remove.addEventListener('click', () => {
                slugs = slugs.filter((s) => s !== slug);
                render();
            });
            pill.append(remove);
            pills.append(pill);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = `${inputName}[]`;
            hidden.value = slug;
            inputs.append(hidden);
        }
    };

    const addTag = (raw) => {
        const slug = slugify(raw);
        if (slug !== '' && !slugs.includes(slug)) {
            slugs.push(slug);
            render();
        }
        input.value = '';
    };

    attachSuggest(input, suggest, {
        fetchItems: async (term) =>
            (await getJson(`/tags?q=${encodeURIComponent(term)}`)).filter((tag) => !slugs.includes(tag.slug)),
        onPick: (tag) => addTag(tag.slug),
        onCreate: () => addTag(input.value),
        activateFirst: false,
    });

    render();
}

function mountReference(widget) {
    const { target, cardinality, inputName } = widget.dataset;
    const many = cardinality === 'many';
    const pills = widget.querySelector('[data-ref-pills]');
    const inputs = widget.querySelector('[data-ref-inputs]');
    const chooseButton = widget.querySelector('[data-ref-choose]');

    let selected = JSON.parse(widget.dataset.initial || '[]').map((id) => ({ id, title: `#${id}` }));

    const render = () => {
        pills.replaceChildren();
        inputs.replaceChildren();
        for (const item of selected) {
            const pill = el('span', 'cms-ref__pill');
            pill.append(document.createTextNode(item.title));
            const remove = el('button', 'cms-ref__pill-remove', '×');
            remove.type = 'button';
            remove.addEventListener('click', () => {
                selected = selected.filter((s) => s.id !== item.id);
                render();
            });
            pill.append(remove);
            pills.append(pill);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = many ? `${inputName}[]` : inputName;
            input.value = String(item.id);
            inputs.append(input);
        }
        chooseButton.textContent = !many && selected.length > 0 ? '↻ Change entry' : '＋ Link entry';
    };

    const add = (entry) => {
        if (many) {
            if (!selected.some((s) => s.id === entry.id)) {
                selected.push(entry);
            }
        } else {
            selected = [entry];
        }
        render();
    };

    if (selected.length > 0) {
        getJson(`/collections/entry-resolve?ids=${selected.map((s) => s.id).join(',')}`).then((list) => {
            const byId = new Map(list.map((e) => [e.id, e.title]));
            selected = selected.map((s) => ({ id: s.id, title: byId.get(s.id) ?? s.title }));
            render();
        });
    }

    chooseButton.addEventListener('click', () => openReferencePicker(target, many, add));
    render();
}

function openReferencePicker(targetSlug, many, onPick) {
    refPicker ??= createModal();

    const search = el('input', 'cms-ref-search');
    search.type = 'search';
    search.placeholder = 'Search entries by title…';

    const results = el('div', 'cms-ref-results');

    const renderResults = (entries) => {
        results.replaceChildren();
        if (entries.length === 0) {
            results.append(el('p', 'cms-ref-results__empty', 'No matching entries.'));

            return;
        }
        for (const entry of entries) {
            const row = el('button', 'cms-ref-result');
            row.type = 'button';
            row.textContent = entry.title;
            row.addEventListener('click', () => {
                onPick(entry);
                if (!many) {
                    refPicker.close();
                }
            });
            results.append(row);
        }
    };

    const runSearch = () => {
        getJson(
            `/collections/entry-search?collection=${encodeURIComponent(targetSlug)}&q=${encodeURIComponent(search.value)}`,
        ).then(renderResults);
    };

    search.addEventListener('input', debounce(runSearch, 200));

    const body = el('div', 'cms-ref-picker');
    body.append(search, results);

    const footer = [];
    if (many) {
        const done = el('button', 'cms-btn cms-btn--primary', 'Done');
        done.type = 'button';
        done.addEventListener('click', () => refPicker.close());
        const group = el('div', 'cms-modal__footer-group');
        group.append(done);
        footer.push(group);
    }

    refPicker.open({ title: 'Link an entry', body, footer });
    runSearch();
    search.focus();
}

function mountRepeater(widget) {
    const rows = widget.querySelector('[data-repeater-rows]');
    const template = widget.querySelector('[data-repeater-template]');
    const addButton = widget.querySelector('[data-repeater-add]');
    let counter = rows.querySelectorAll('[data-repeater-row]').length;

    const bindRow = (row) => {
        row.querySelector('[data-repeater-handle]').draggable = true;
        row.querySelector('[data-repeater-remove]').addEventListener('click', () => row.remove());
    };

    makeSortable(rows, {
        itemSelector: '[data-repeater-row]',
        draggingClass: 'cms-repeater__row--dragging',
        beforeClass: 'cms-repeater__row--drop-before',
        afterClass: 'cms-repeater__row--drop-after',
        onDrop: ({ item, target, zone }) => {
            rows.insertBefore(item, zone === 'before' ? target : target.nextSibling);
        },
    });

    const cloneRow = (index) => {
        const fragment = template.content.cloneNode(true);
        const withIndex = (text) => text.replaceAll('__INDEX__', String(index));

        const walker = document.createTreeWalker(fragment, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT);
        for (let node = walker.nextNode(); node !== null; node = walker.nextNode()) {
            if (node.nodeType === Node.TEXT_NODE) {
                node.nodeValue = withIndex(node.nodeValue);
                continue;
            }
            for (const attribute of node.attributes) {
                attribute.value = withIndex(attribute.value);
            }
        }

        return fragment.firstElementChild;
    };

    const addRow = () => {
        const row = cloneRow(counter++);
        if (row === null) {
            return;
        }
        rows.append(row);
        mountFieldWidgets(row);
        bindRow(row);
    };

    for (const row of rows.querySelectorAll('[data-repeater-row]')) {
        bindRow(row);
    }
    addButton.addEventListener('click', addRow);
}

function mountRichText(textarea) {
    const { editor, sync } = createRichText(textarea, {
        extensions: [StarterKit, Link.configure({ openOnClick: false, autolink: false })],
        classes: {
            wrapper: 'cms-rt',
            toolbar: 'cms-rt__toolbar',
            surface: 'cms-rt__surface',
            group: null,
            button: 'cms-rt__btn',
            active: 'cms-rt__btn--active',
        },
        groups: (editor) => [
            [
                {
                    label: 'B',
                    title: 'Bold',
                    cls: 'cms-rt__btn--bold',
                    run: () => editor.chain().focus().toggleBold().run(),
                    active: () => editor.isActive('bold'),
                },
                {
                    label: 'I',
                    title: 'Italic',
                    cls: 'cms-rt__btn--italic',
                    run: () => editor.chain().focus().toggleItalic().run(),
                    active: () => editor.isActive('italic'),
                },
                {
                    label: 'H2',
                    title: 'Heading',
                    run: () => editor.chain().focus().toggleHeading({ level: 2 }).run(),
                    active: () => editor.isActive('heading', { level: 2 }),
                },
                {
                    label: 'H3',
                    title: 'Subheading',
                    run: () => editor.chain().focus().toggleHeading({ level: 3 }).run(),
                    active: () => editor.isActive('heading', { level: 3 }),
                },
                {
                    label: 'List',
                    title: 'Bulleted list',
                    run: () => editor.chain().focus().toggleBulletList().run(),
                    active: () => editor.isActive('bulletList'),
                },
                {
                    label: '1.',
                    title: 'Numbered list',
                    run: () => editor.chain().focus().toggleOrderedList().run(),
                    active: () => editor.isActive('orderedList'),
                },
                {
                    label: 'Quote',
                    title: 'Quote',
                    run: () => editor.chain().focus().toggleBlockquote().run(),
                    active: () => editor.isActive('blockquote'),
                },
            ],
        ],
    });
    sync();

    const form = textarea.closest('form');
    const flush = () => {
        textarea.value = editor.getHTML();
    };
    form?.addEventListener('submit', flush);
    editor.on('update', () => {
        flush();
        form?.dispatchEvent(new Event('cms:autosave'));
    });
}

function mountMedia(widget) {
    const hidden = widget.querySelector('[data-media-id]');
    const preview = widget.querySelector('[data-media-preview]');
    const choose = widget.querySelector('[data-media-choose]');
    const clear = widget.querySelector('[data-media-clear]');

    const setValue = (id) => {
        hidden.value = id === null ? '' : String(id);
        preview.replaceChildren();
        if (id !== null) {
            const img = document.createElement('img');
            img.src = `${ADMIN_BASE}/media/serve/${id}`;
            img.alt = '';
            preview.append(img);
        }
        clear.hidden = id === null;
    };

    choose.addEventListener('click', async () => {
        const picked = await openMediaPicker({ kind: 'image' });
        if (picked !== null) {
            setValue(picked.id);
        }
    });
    clear.addEventListener('click', () => setValue(null));
}
