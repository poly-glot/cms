import { debounce, el } from './dom.mjs';

export function attachSuggest(input, panel, { fetchItems, onPick, onCreate = null, activateFirst = true }) {
    let items = [];
    let activeIndex = -1;

    const rows = () => Array.from(panel.children);

    function hide() {
        items = [];
        activeIndex = -1;
        panel.replaceChildren();
        panel.hidden = true;
    }

    function highlight(index) {
        activeIndex = index;
        rows().forEach((row, rowIndex) => row.classList.toggle('is-active', rowIndex === activeIndex));
    }

    function pick(index) {
        const item = items[index];
        if (item === undefined) {
            return;
        }

        hide();
        onPick(item);
    }

    function show(nextItems) {
        items = nextItems;
        activeIndex = activateFirst && items.length > 0 ? 0 : -1;
        panel.replaceChildren(
            ...items.map((item, index) => {
                const row = el(
                    'button',
                    'cms-tag-suggest__item' + (index === activeIndex ? ' is-active' : ''),
                    item.label,
                );
                row.type = 'button';
                row.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    pick(index);
                });

                return row;
            }),
        );
        panel.hidden = items.length === 0;
    }

    const search = debounce(async () => {
        const term = input.value.trim();
        if (term === '') {
            hide();

            return;
        }

        show(await fetchItems(term));
    }, 150);

    input.addEventListener('input', search);
    input.addEventListener('blur', hide);
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            if (items.length === 0) {
                return;
            }

            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            highlight((activeIndex + step + items.length) % items.length);
        } else if (event.key === 'Enter') {
            if (activeIndex >= 0) {
                event.preventDefault();
                pick(activeIndex);
            } else if (onCreate) {
                event.preventDefault();
                hide();
                onCreate();
            }
        } else if (event.key === 'Escape') {
            hide();
        }
    });

    return { hide };
}
