const NEST_LOWER = 0.3;
const NEST_UPPER = 0.7;

const sortableLists = new WeakSet();

function sortableOwning(element) {
    let ancestor = element.parentElement;
    while (ancestor && !sortableLists.has(ancestor)) {
        ancestor = ancestor.parentElement;
    }

    return ancestor;
}

export function makeSortable(
    list,
    { itemSelector, draggingClass, beforeClass, afterClass, intoClass = null, zoneWithin = null, onDrop },
) {
    let dragged = null;
    let target = null;
    let zone = null;

    sortableLists.add(list);

    const markers = [beforeClass, afterClass, ...(intoClass ? [intoClass] : [])];

    function ownItem(element) {
        let item = element.closest(itemSelector);
        while (item && sortableOwning(item) !== list) {
            item = item.parentElement?.closest(itemSelector) ?? null;
        }

        return item;
    }

    function clearMarkers() {
        for (const marked of list.querySelectorAll(markers.map((cls) => `.${cls}`).join(', '))) {
            marked.classList.remove(...markers);
        }
    }

    function finish() {
        clearMarkers();
        dragged?.classList.remove(draggingClass);
        dragged = null;
        target = null;
        zone = null;
    }

    function zoneFor(item, event) {
        const geometry = zoneWithin ? (item.querySelector(zoneWithin) ?? item) : item;
        const bounds = geometry.getBoundingClientRect();
        const ratio = (event.clientY - bounds.top) / bounds.height;
        if (intoClass && ratio >= NEST_LOWER && ratio <= NEST_UPPER) {
            return 'into';
        }

        return ratio < 0.5 ? 'before' : 'after';
    }

    list.addEventListener('dragstart', (event) => {
        const isElementDrag = event.target instanceof HTMLElement && event.target.draggable;
        const item = isElementDrag ? event.target.closest(itemSelector) : null;
        dragged = item && sortableOwning(item) === list ? item : null;
        if (!dragged) {
            return;
        }

        dragged.classList.add(draggingClass);
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', '');
    });

    list.addEventListener('dragover', (event) => {
        const item = event.target instanceof HTMLElement ? ownItem(event.target) : null;
        if (!dragged || !item || item === dragged || dragged.contains(item)) {
            return;
        }

        event.preventDefault();
        clearMarkers();
        target = item;
        zone = zoneFor(item, event);
        item.classList.add(zone === 'into' ? intoClass : zone === 'before' ? beforeClass : afterClass);
    });

    list.addEventListener('drop', (event) => {
        if (!dragged || !target) {
            return;
        }

        event.preventDefault();
        const dropped = { item: dragged, target, zone };
        finish();
        onDrop(dropped);
    });

    list.addEventListener('dragend', finish);
}
