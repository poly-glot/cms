import { postForm } from './kit/http.mjs';
import { showToast } from './kit/dom.mjs';
import { makeSortable } from './kit/drag.mjs';

const tree = document.querySelector('[data-pages-tree]');
const bulkbar = document.querySelector('[data-bulkbar]');

const checks = () => [...document.querySelectorAll('[data-tree-check]')];

function refreshBulk() {
    const selected = checks().filter((box) => box.checked).length;

    const countEl = document.querySelector('[data-bulk-count]');
    if (countEl) {
        countEl.textContent = String(selected);
    }

    if (bulkbar) {
        bulkbar.hidden = selected === 0;
    }

    const all = document.querySelector('[data-select-all]');
    if (all) {
        const allSelected = selected > 0 && selected === checks().length;
        all.checked = allSelected;
    }
}

document.addEventListener('change', (event) => {
    if (event.target instanceof HTMLElement && event.target.matches('[data-tree-check]')) {
        refreshBulk();
    }
});
document.querySelector('[data-select-all]')?.addEventListener('change', (event) => {
    const on = event.target.checked;
    for (const box of checks()) {
        box.checked = on;
    }
    refreshBulk();
});
document.querySelector('[data-bulk-clear]')?.addEventListener('click', () => {
    for (const box of checks()) {
        box.checked = false;
    }
    refreshBulk();
});
refreshBulk();

if (tree) {
    makeSortable(tree, {
        itemSelector: '[data-page-id]',
        draggingClass: 'cms-tree__node--dragging',
        beforeClass: 'cms-tree__node--before',
        afterClass: 'cms-tree__node--after',
        intoClass: 'cms-tree__node--into',
        zoneWithin: ':scope > [data-tree-row]',
        onDrop: ({ item, target, zone }) => {
            if (zone === 'into') {
                target.querySelector(':scope > [data-tree-children]').appendChild(item);
            } else if (zone === 'before') {
                target.parentNode.insertBefore(item, target);
            } else {
                target.parentNode.insertBefore(item, target.nextSibling);
            }

            persistOrder();
        },
    });
}

function serializeOrder() {
    const order = [];
    const walk = (list, parentId) => {
        let position = 0;
        for (const node of list.children) {
            if (!node.matches('[data-page-id]')) {
                continue;
            }

            const id = Number(node.dataset.pageId);
            order.push({ id, parentId, position });
            node.dataset.parentId = parentId === null ? '' : String(parentId);
            position++;

            const children = node.querySelector(':scope > [data-tree-children]');
            if (children) {
                walk(children, id);
            }
        }
    };
    walk(tree, null);

    return order;
}

async function persistOrder() {
    try {
        await postForm(tree.dataset.reorderUrl, { order: JSON.stringify(serializeOrder()) });
    } catch {
        showToast('Could not save the new order — refresh and try again.');
    }
}
