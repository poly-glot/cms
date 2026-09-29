import { el } from './kit/dom.mjs';
import { bindInlineTitle } from './inline-title.mjs';
import { getJson } from './kit/http.mjs';
import { machineName } from './kit/slug.mjs';
import { makeSortable } from './kit/drag.mjs';

const BLANK_FIELD = Object.freeze({
    name: '',
    label: '',
    type: 'text',
    required: false,
    target: '',
    cardinality: 'one',
    nameTouched: false,
});

bindInlineTitle();

const mount = document.querySelector('[data-schema-builder]');
if (mount) {
    initBuilder(mount);
}

function parseFields(rows) {
    return rows.map((row) => ({
        ...BLANK_FIELD,
        ...row,
        options: row.options ?? [],
        fields: parseFields(row.fields ?? []),
        nameTouched: true,
    }));
}

function serialiseFields(fields) {
    return fields.map((field) => {
        const row = { name: field.name, label: field.label, type: field.type, required: field.required };
        if (field.type === 'select') {
            row.options = field.options.map((option) => ({ value: option.value, label: option.label }));
        }
        if (field.type === 'repeater') {
            row.fields = serialiseFields(field.fields ?? []);
        }
        if (field.type === 'reference') {
            row.target = field.target;
            row.cardinality = field.cardinality;
        }

        return row;
    });
}

function initBuilder(root) {
    const form = root.closest('[data-schema-form]');
    const list = root.querySelector('[data-field-list]');
    const emptyHint = root.querySelector('[data-empty-hint]');
    const hidden = form.querySelector('[data-schema-input]');

    const fields = parseFields(JSON.parse(root.dataset.schema));
    const ctx = {
        allTypes: JSON.parse(root.dataset.fieldTypes),
        usage: JSON.parse(root.dataset.fieldsInUse),
        collections: [],
    };

    const top = mountList(fields, list, 0, ctx, emptyHint);

    getJson('/collections/list')
        .then((collections) => {
            ctx.collections = collections;
            top.render();
        })
        .catch(() => {
            top.render();
        });

    root.querySelector('[data-add-field]').addEventListener('click', () => top.addField());
    form.addEventListener('submit', () => {
        hidden.value = JSON.stringify(serialiseFields(fields));
    });
}

function mountList(fields, container, depth, ctx, emptyHint = null) {
    const allowedTypes = ctx.allTypes.filter(
        (type) => !(depth >= 1 && (type.value === 'repeater' || type.value === 'reference' || type.value === 'tags')),
    );

    const render = () => {
        container.replaceChildren(...fields.map((field, index) => rowNode(field, index)));
        if (emptyHint) {
            emptyHint.hidden = fields.length > 0;
        }
    };

    const focusHandle = (index) => {
        container.children[index]?.querySelector('[data-handle]')?.focus();
    };

    const moveTo = (from, to) => {
        if (to < 0 || to >= fields.length || from === to) {
            return;
        }
        const [moved] = fields.splice(from, 1);
        fields.splice(to, 0, moved);
        render();
        focusHandle(to);
    };

    const dropBefore = (from, beforeIndex) => {
        const [moved] = fields.splice(from, 1);
        const adjusted = beforeIndex > from ? beforeIndex - 1 : beforeIndex;
        fields.splice(adjusted, 0, moved);
        render();
    };

    const confirmDestructive = (field) => {
        const count = ctx.usage[field.name] ?? 0;
        if (count === 0) {
            return true;
        }

        const noun = count === 1 ? 'entry' : 'entries';

        return window.confirm(
            `“${field.label || field.name}” already holds data in ${count} ${noun}. Changing or removing it may drop or break those values the next time each entry is saved.`,
        );
    };

    const addField = () => {
        fields.push({ ...BLANK_FIELD, type: allowedTypes[0]?.value ?? 'text', options: [], fields: [] });
        render();
        container.lastElementChild?.querySelector('[data-label-input]')?.focus();
    };

    const typeOptionsFor = (field) => {
        const options = allowedTypes.slice();
        if (!options.some((type) => type.value === field.type)) {
            const own = ctx.allTypes.find((type) => type.value === field.type);
            if (own) {
                options.push(own);
            }
        }

        return options;
    };

    const optionsEditor = (field) => {
        const wrap = el('div', 'cms-schema-field__choices');
        wrap.append(el('span', 'cms-schema-field__choices-label', 'Choices'));

        const rows = el('div', 'cms-schema-choices');
        field.options.forEach((option, optionIndex) => {
            const row = el('div', 'cms-schema-choice');

            const value = el('input');
            value.type = 'text';
            value.placeholder = 'value';
            value.value = option.value;
            value.addEventListener('input', () => {
                option.value = value.value;
            });

            const label = el('input');
            label.type = 'text';
            label.placeholder = 'Label';
            label.value = option.label;
            label.addEventListener('input', () => {
                option.label = label.value;
            });

            const remove = el('button', 'cms-schema-choice__remove', '×');
            remove.type = 'button';
            remove.title = 'Remove choice';
            remove.addEventListener('click', () => {
                field.options.splice(optionIndex, 1);
                render();
            });

            row.append(value, label, remove);
            rows.append(row);
        });

        const add = el('button', 'cms-btn cms-btn--ghost', '+ Add choice');
        add.type = 'button';
        add.addEventListener('click', () => {
            field.options.push({ value: '', label: '' });
            render();
        });

        wrap.append(rows, add);

        return wrap;
    };

    const repeaterEditor = (field) => {
        const group = el('div', 'cms-schema-group');
        group.append(el('span', 'cms-schema-group__label', 'Fields that repeat in each row'));

        const subList = el('div', 'cms-schema-group__list');
        group.append(subList);

        field.fields = field.fields ?? [];
        const sub = mountList(field.fields, subList, depth + 1, ctx);

        const add = el('button', 'cms-btn cms-btn--ghost', '+ Add sub-field');
        add.type = 'button';
        add.addEventListener('click', () => sub.addField());
        group.append(add);

        return group;
    };

    const referenceEditor = (field) => {
        const wrap = el('div', 'cms-schema-ref');

        const targetRow = el('label', 'cms-schema-ref__row');
        targetRow.append(el('span', 'cms-schema-ref__label', 'Links to'));
        const targetSelect = el('select');
        const placeholder = el('option', null, ctx.collections.length === 0 ? 'Loading…' : '— choose collection —');
        placeholder.value = '';
        targetSelect.append(placeholder);
        for (const collection of ctx.collections) {
            const option = el('option', null, collection.name);
            option.value = collection.slug;
            option.selected = collection.slug === field.target;
            targetSelect.append(option);
        }
        targetSelect.addEventListener('change', () => {
            field.target = targetSelect.value;
        });
        targetRow.append(targetSelect);

        const cardRow = el('label', 'cms-schema-ref__row');
        cardRow.append(el('span', 'cms-schema-ref__label', 'How many'));
        const cardSelect = el('select');
        for (const [value, text] of [
            ['one', 'One entry'],
            ['many', 'Several entries'],
        ]) {
            const option = el('option', null, text);
            option.value = value;
            option.selected = value === (field.cardinality ?? 'one');
            cardSelect.append(option);
        }
        cardSelect.addEventListener('change', () => {
            field.cardinality = cardSelect.value;
        });
        cardRow.append(cardSelect);

        wrap.append(targetRow, cardRow);

        return wrap;
    };

    const rowNode = (field, index) => {
        const row = el('div', 'cms-schema-field');
        row.dataset.schemaField = '';

        const handle = el('span', 'cms-schema-field__handle');
        handle.innerHTML = '⠿';
        handle.dataset.handle = '';
        handle.tabIndex = 0;
        handle.draggable = true;
        handle.setAttribute('role', 'button');
        handle.setAttribute('aria-label', 'Drag to reorder, or use the arrow keys');
        handle.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                moveTo(index, index - 1);
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                moveTo(index, index + 1);
            }
        });

        const labelInput = el('input');
        labelInput.type = 'text';
        labelInput.placeholder = 'Label';
        labelInput.value = field.label;
        labelInput.dataset.labelInput = '';
        labelInput.addEventListener('input', () => {
            field.label = labelInput.value;
            if (!field.nameTouched) {
                field.name = machineName(labelInput.value);
                nameInput.value = field.name;
            }
        });

        const nameInput = el('input', 'cms-schema-field__name');
        nameInput.type = 'text';
        nameInput.placeholder = 'field_name';
        nameInput.value = field.name;
        nameInput.addEventListener('input', () => {
            field.nameTouched = true;
            field.name = nameInput.value;
        });

        const typeSelect = el('select');
        for (const type of typeOptionsFor(field)) {
            const option = el('option', null, type.label);
            option.value = type.value;
            option.selected = type.value === field.type;
            typeSelect.append(option);
        }
        typeSelect.addEventListener('change', () => {
            if (!confirmDestructive(field)) {
                typeSelect.value = field.type;

                return;
            }

            field.type = typeSelect.value;
            if (field.type === 'select' && field.options.length === 0) {
                field.options = [{ value: '', label: '' }];
            }
            render();
        });

        const requiredLabel = el('label', 'cms-schema-field__required');
        const requiredInput = el('input');
        requiredInput.type = 'checkbox';
        requiredInput.checked = field.required;
        requiredInput.addEventListener('change', () => {
            field.required = requiredInput.checked && confirmDestructive(field);
            requiredInput.checked = field.required;
        });
        requiredLabel.append(requiredInput, document.createTextNode('Required'));

        const moveUp = el('button', 'cms-schema-field__move', '↑');
        moveUp.type = 'button';
        moveUp.title = 'Move up';
        moveUp.addEventListener('click', () => moveTo(index, index - 1));

        const moveDown = el('button', 'cms-schema-field__move', '↓');
        moveDown.type = 'button';
        moveDown.title = 'Move down';
        moveDown.addEventListener('click', () => moveTo(index, index + 1));

        const remove = el('button', 'cms-schema-field__remove', '×');
        remove.type = 'button';
        remove.title = 'Remove field';
        remove.addEventListener('click', () => {
            if (confirmDestructive(field)) {
                fields.splice(index, 1);
                render();
            }
        });

        const main = el('div', 'cms-schema-field__main');
        main.append(labelInput, nameInput, typeSelect, requiredLabel);

        const controls = el('div', 'cms-schema-field__controls');
        controls.append(moveUp, moveDown, remove);

        const head = el('div', 'cms-schema-field__top');
        head.append(handle, main, controls);
        row.append(head);

        if (field.type === 'select') {
            row.append(optionsEditor(field));
        }
        if (field.type === 'repeater') {
            row.append(repeaterEditor(field));
        }
        if (field.type === 'reference') {
            row.append(referenceEditor(field));
        }

        return row;
    };

    makeSortable(container, {
        itemSelector: '[data-schema-field]',
        draggingClass: 'cms-schema-field--dragging',
        beforeClass: 'cms-schema-field--drop-before',
        afterClass: 'cms-schema-field--drop-after',
        onDrop: ({ item, target, zone }) => {
            const from = Array.from(container.children).indexOf(item);
            const to = Array.from(container.children).indexOf(target);
            dropBefore(from, zone === 'before' ? to : to + 1);
        },
    });

    render();

    return { addField, render };
}
