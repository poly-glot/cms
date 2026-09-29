import { postUpload } from './kit/http.mjs';
import { el, showToast, svgIcon } from './kit/dom.mjs';
import { makeSortable } from './kit/drag.mjs';

const dataEl = document.querySelector('[data-nav-data]');

if (dataEl) {
    const idsAsStrings = (key, value) => (key === 'id' ? String(value) : value);
    const data = JSON.parse(dataEl.textContent ?? '{}', idsAsStrings);
    const menus = data.menus ?? {};
    const PAGES = data.pages ?? [];
    let activeMenuId = data.active;

    let selectedId = menus[activeMenuId]?.[0]?.id ?? null;
    const collapsed = new Set();
    let uidCounter = 0;

    const uid = () => `tmp-${++uidCounter}`;
    const ICON_PAGE =
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h9l4 4v14H6z"/><path d="M15 3v4h4"/></svg>';
    const ICON_LINK =
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14a4 4 0 0 0 5.66 0l3-3a4 4 0 1 0-5.66-5.66L11.5 7"/><path d="M14 10a4 4 0 0 0-5.66 0l-3 3a4 4 0 1 0 5.66 5.66L12.5 17"/></svg>';
    const ICON_NEW =
        '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 2H3a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V7"/><path d="M7 2h3v3M10 2 5 7"/></svg>';

    const tree = () => menus[activeMenuId] ?? [];

    function find(id, list = tree(), parent = null) {
        for (let i = 0; i < list.length; i++) {
            const item = list[i];
            if (item.id === id) {
                return { item, list, parent, index: i };
            }
            if (item.children) {
                const found = find(id, item.children, item);
                if (found) {
                    return found;
                }
            }
        }

        return null;
    }

    function removeNode(id) {
        const found = find(id);

        return found ? found.list.splice(found.index, 1)[0] : null;
    }

    function countItems(list = tree()) {
        return list.reduce((total, item) => total + 1 + (item.children ? countItems(item.children) : 0), 0);
    }

    function allIdsDeep(list = tree(), into = []) {
        for (const item of list) {
            if (item.children?.length) {
                into.push(item.id);
                allIdsDeep(item.children, into);
            }
        }

        return into;
    }

    const pageFor = (pageId) => PAGES.find((page) => page.id === String(pageId));
    const pagePath = (pageId) => pageFor(pageId)?.path ?? '—';
    const itemPath = (item) => (item.type === 'page' ? pagePath(item.pageId) : item.url || '—');

    function renderTree() {
        const treeEl = document.querySelector('[data-nav-tree]');
        if (!treeEl) {
            return;
        }
        const list = tree();
        if (list.length === 0) {
            const empty = el('div', 'cms-nav-tree__empty');
            empty.append(el('p', null, 'This menu is empty.'));

            const addButton = el('button', 'cms-btn cms-btn--primary', '+ Add first link');
            addButton.type = 'button';
            addButton.dataset.navAddPage = '';
            empty.append(addButton);

            treeEl.replaceChildren(empty);
        } else {
            treeEl.replaceChildren(...renderList(list));
        }

        const count = document.querySelector('[data-nav-count]');
        if (count) {
            const total = countItems();
            count.textContent = `${total} ${total === 1 ? 'item' : 'items'}`;
        }
    }

    function renderList(list) {
        return list.map((item) => {
            const hasChildren = item.children?.length > 0;
            const isCollapsed = collapsed.has(item.id);

            const node = el('div', 'cms-nav-item' + (item.id === selectedId ? ' cms-nav-item--selected' : ''));
            node.dataset.navId = item.id;
            node.setAttribute('role', 'treeitem');
            if (hasChildren) {
                node.setAttribute('aria-expanded', String(!isCollapsed));
            }
            node.draggable = true;

            const row = el('div', 'cms-nav-item__row');
            row.dataset.navRow = '';
            row.tabIndex = 0;

            const handle = el('span', 'cms-nav-item__handle');
            handle.dataset.navHandle = '';
            handle.setAttribute('aria-hidden', 'true');

            const caret = el(
                'button',
                'cms-nav-item__caret' +
                    (!hasChildren ? ' cms-nav-item__caret--hidden' : '') +
                    (isCollapsed ? ' cms-nav-item__caret--collapsed' : ''),
            );
            caret.type = 'button';
            caret.dataset.navToggle = item.id;
            caret.setAttribute('aria-label', 'Toggle children');
            caret.innerHTML =
                '<svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor"><path d="M2 4h6L5 8z"/></svg>';

            const icon = el('span', 'cms-nav-item__icon');
            icon.setAttribute('aria-hidden', 'true');
            icon.append(svgIcon(item.type === 'page' ? ICON_PAGE : ICON_LINK));

            const label = el('span', 'cms-nav-item__label', item.label || '(no label)');
            label.dataset.navLabel = '';
            const path = el('span', 'cms-nav-item__path', itemPath(item));
            path.dataset.navPath = '';
            const badge = el(
                'span',
                'cms-nav-item__badge' + (item.type === 'url' ? ' cms-nav-item__badge--ext' : ''),
                item.type === 'page' ? 'Page' : 'URL',
            );

            const target = el('span', 'cms-nav-item__target');
            target.innerHTML = ICON_NEW;
            if (item.target === '_blank') {
                target.title = 'Opens in new window';
            } else {
                target.setAttribute('aria-hidden', 'true');
                target.style.visibility = 'hidden';
            }

            row.append(handle, caret, icon, label, path, badge, target);
            node.append(row);

            if (hasChildren && !isCollapsed) {
                const children = el('div', 'cms-nav-item__children');
                children.append(...renderList(item.children));
                node.append(children);
            }

            return node;
        });
    }

    function renderDetails() {
        const root = document.querySelector('[data-nav-details]');
        if (!root) {
            return;
        }
        if (!selectedId) {
            const empty = el('div', 'cms-nav-details__empty');
            const emptyIcon = el('div', 'cms-nav-details__empty-icon');
            emptyIcon.innerHTML =
                '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>';
            const addHint = el('p', null, 'Or click “+ Page” or “+ Link” above to add one.');
            addHint.style.fontSize = '12px';
            empty.append(emptyIcon, el('p', null, 'Select an item to edit it.'), addHint);
            root.replaceChildren(empty);

            return;
        }
        const found = find(selectedId);
        if (!found) {
            selectedId = null;
            renderDetails();

            return;
        }
        const item = found.item;

        const formField = (tag, labelText, control) => {
            const wrap = el(tag, 'cms-form-field');
            wrap.append(el('span', 'cms-form-field__label', labelText), control);

            return wrap;
        };
        const radioOption = (field, value, checked, text, icon = null) => {
            const radio = el('input');
            radio.type = 'radio';
            radio.name = `nav-${field}`;
            radio.value = value;
            radio.checked = checked;
            radio.dataset.navField = field;

            const option = el('label', 'cms-nav-radio__option');
            option.append(radio);
            if (icon !== null) {
                option.append(svgIcon(icon));
            }
            option.append(text);

            return option;
        };
        const radioGroup = (...options) => {
            const group = el('div', 'cms-nav-radio');
            group.setAttribute('role', 'radiogroup');
            group.append(...options);

            return group;
        };

        const labelInput = el('input', 'cms-form-field__input');
        labelInput.type = 'text';
        labelInput.dataset.navField = 'label';
        labelInput.value = item.label ?? '';
        labelInput.placeholder = 'Menu label';
        labelInput.autocomplete = 'off';

        const typeGroup = radioGroup(
            radioOption('type', 'page', item.type === 'page', ' Internal page', ICON_PAGE),
            radioOption('type', 'url', item.type === 'url', ' External URL', ICON_LINK),
        );

        let linkField;
        if (item.type === 'page') {
            const select = el('select', 'cms-select__control');
            select.dataset.navField = 'pageId';
            select.style.width = '100%';
            for (const page of PAGES) {
                const option = el('option', null, `${page.title} — ${page.path}`);
                option.value = page.id;
                option.selected = String(item.pageId) === page.id;
                select.append(option);
            }
            const selectWrap = el('div', 'cms-select');
            selectWrap.style.display = 'block';
            selectWrap.style.width = '100%';
            selectWrap.append(select);

            const hint = el('div', 'cms-nav-details__hint', pagePath(item.pageId));
            hint.dataset.navHint = '';

            linkField = el('div', 'cms-form-field');
            linkField.append(el('span', 'cms-form-field__label', 'Page'), selectWrap, hint);
        } else {
            const urlInput = el('input', 'cms-form-field__input');
            urlInput.type = 'url';
            urlInput.dataset.navField = 'url';
            urlInput.value = item.url || '';
            urlInput.placeholder = 'https://example.com';
            urlInput.autocomplete = 'off';
            linkField = formField('label', 'URL', urlInput);
        }

        const targetGroup = radioGroup(
            radioOption('target', '_self', item.target !== '_blank', 'Same window'),
            radioOption('target', '_blank', item.target === '_blank', ' New window', ICON_NEW),
        );

        const nested = el(
            'div',
            'cms-nav-details__nested',
            item.children?.length
                ? `${item.children.length} nested ${item.children.length === 1 ? 'item' : 'items'}`
                : 'No nested items. Drop another item onto this one to nest it underneath.',
        );

        const deleteButton = el('button', 'cms-btn cms-btn--ghost cms-nav-details__delete', 'Delete');
        deleteButton.type = 'button';
        deleteButton.dataset.navDelete = '';

        const header = el('header', 'cms-card__header');
        header.append(el('span', null, 'Item details'), deleteButton);

        const body = el('div', 'cms-card__body');
        body.append(
            formField('label', 'Label', labelInput),
            formField('div', 'Link type', typeGroup),
            linkField,
            formField('div', 'Opens in', targetGroup),
            nested,
        );

        const section = el('section', 'cms-card');
        section.append(header, body);
        root.replaceChildren(section);

        bindDetailsEvents();
    }

    function bindDetailsEvents() {
        const root = document.querySelector('[data-nav-details]');
        if (!root) {
            return;
        }

        root.querySelectorAll('[data-nav-field]').forEach((control) => {
            control.addEventListener(control.tagName === 'SELECT' ? 'change' : 'input', () => {
                const found = find(selectedId);
                if (!found) {
                    return;
                }
                const field = control.dataset.navField;
                found.item[field] = control.value;

                if (field === 'type' || field === 'target') {
                    if (found.item.type === 'page' && !found.item.pageId) {
                        found.item.pageId = PAGES[0]?.id ?? null;
                    }
                    if (found.item.type === 'url' && found.item.url == null) {
                        found.item.url = '';
                    }
                    renderDetails();
                    renderTree();

                    return;
                }

                const itemEl = document.querySelector('[data-nav-id="' + selectedId + '"]');
                if (field === 'label') {
                    const labelEl = itemEl?.querySelector(':scope > [data-nav-row] > [data-nav-label]');
                    if (labelEl) {
                        labelEl.textContent = found.item.label || '(no label)';
                    }
                }
                if (field === 'pageId' || field === 'url') {
                    const pathEl = itemEl?.querySelector(':scope > [data-nav-row] > [data-nav-path]');
                    if (pathEl) {
                        pathEl.textContent = itemPath(found.item);
                    }
                    const hint = root.querySelector('[data-nav-hint]');
                    if (hint && field === 'pageId') {
                        hint.textContent = pagePath(found.item.pageId);
                    }
                }
            });
        });

        root.querySelector('[data-nav-delete]')?.addEventListener('click', () => {
            const found = find(selectedId);
            if (!found) {
                return;
            }
            const nested = found.item.children?.length ?? 0;
            const message = nested
                ? `Delete "${found.item.label}" and its ${nested} nested ${nested === 1 ? 'item' : 'items'}?`
                : `Delete "${found.item.label}"?`;
            if (!window.confirm(message)) {
                return;
            }
            removeNode(selectedId);
            selectedId = null;
            renderTree();
            renderDetails();
        });
    }

    function addItem(type) {
        const newItem =
            type === 'url'
                ? { id: uid(), type: 'url', label: 'New link', url: 'https://', target: '_self', children: [] }
                : {
                      id: uid(),
                      type: 'page',
                      label: 'New page',
                      pageId: PAGES[0]?.id ?? null,
                      target: '_self',
                      children: [],
                  };

        const selected = selectedId ? find(selectedId) : null;
        if (selected) {
            selected.item.children = selected.item.children ?? [];
            selected.item.children.push(newItem);
            collapsed.delete(selected.item.id);
        } else {
            tree().push(newItem);
        }
        selectedId = newItem.id;
        renderTree();
        renderDetails();
        setTimeout(() => {
            const labelInput = document.querySelector('[data-nav-field="label"]');
            if (labelInput) {
                labelInput.focus();
                labelInput.select();
            }
        }, 30);
    }

    async function saveMenu() {
        const form = new FormData();
        form.append('menu', activeMenuId);
        form.append('tree', JSON.stringify(tree()));
        try {
            const result = await postUpload('/navigation/save', form);
            showToast(result.ok ? 'Menu saved' : (result.error ?? 'Could not save menu'));
        } catch {
            showToast('Could not save menu');
        }
    }

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-nav-add-page]')) {
            addItem('page');
        } else if (event.target.closest('[data-nav-add-url]')) {
            addItem('url');
        } else if (event.target.closest('[data-nav-save]')) {
            saveMenu();
        } else if (event.target.closest('[data-nav-expand-all]')) {
            collapsed.clear();
            renderTree();
        } else if (event.target.closest('[data-nav-collapse-all]')) {
            allIdsDeep().forEach((id) => collapsed.add(id));
            renderTree();
        } else {
            const tab = event.target.closest('[data-menu-id]');
            if (tab) {
                const id = tab.getAttribute('data-menu-id');
                if (id !== activeMenuId) {
                    activeMenuId = id;
                    selectedId = menus[id]?.[0]?.id ?? null;
                    collapsed.clear();
                    document
                        .querySelectorAll('[data-nav-menus] [data-menu-id]')
                        .forEach((other) =>
                            other.classList.toggle(
                                'cms-nav-menus__tab--active',
                                other.getAttribute('data-menu-id') === id,
                            ),
                        );
                    renderTree();
                    renderDetails();
                }
            }
        }
    });

    const navTree = document.querySelector('[data-nav-tree]');
    if (navTree) {
        navTree.addEventListener('click', (event) => {
            const toggle = event.target.closest('[data-nav-toggle]');
            if (toggle) {
                const id = toggle.dataset.navToggle;
                if (!collapsed.delete(id)) {
                    collapsed.add(id);
                }
                renderTree();

                return;
            }

            const row = event.target.closest('[data-nav-row]');
            if (!row || event.target.closest('[data-nav-handle]')) {
                return;
            }
            selectedId = row.closest('[data-nav-id]').dataset.navId;
            renderTree();
            renderDetails();
        });

        makeSortable(navTree, {
            itemSelector: '[data-nav-id]',
            draggingClass: 'cms-nav-item--dragging',
            beforeClass: 'cms-nav-item--drop-before',
            afterClass: 'cms-nav-item--drop-after',
            intoClass: 'cms-nav-item--drop-into',
            zoneWithin: ':scope > [data-nav-row]',
            onDrop: ({ item, target, zone }) => {
                const dragged = removeNode(item.getAttribute('data-nav-id'));
                const found = find(target.getAttribute('data-nav-id'));
                if (!dragged || !found) {
                    return;
                }
                if (zone === 'into') {
                    found.item.children = found.item.children ?? [];
                    found.item.children.push(dragged);
                    collapsed.delete(found.item.id);
                } else if (zone === 'before') {
                    found.list.splice(found.index, 0, dragged);
                } else {
                    found.list.splice(found.index + 1, 0, dragged);
                }
                selectedId = dragged.id;
                renderTree();
                renderDetails();
            },
        });
    }

    renderTree();
    renderDetails();
}
