import { Editor, Node, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import TextAlign from '@tiptap/extension-text-align';
import Table from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import TableHeader from '@tiptap/extension-table-header';
import TableCell from '@tiptap/extension-table-cell';

const ICON_GRIP = '<svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true"><circle cx="3.5" cy="2.5" r="1.1"/><circle cx="8.5" cy="2.5" r="1.1"/><circle cx="3.5" cy="6" r="1.1"/><circle cx="8.5" cy="6" r="1.1"/><circle cx="3.5" cy="9.5" r="1.1"/><circle cx="8.5" cy="9.5" r="1.1"/></svg>';

const barButton = (label, title, onClick) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'cms-img__btn';
    button.textContent = label;
    button.title = title;
    button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        onClick();
    });

    return button;
};

const deleteNode = (editor, getPos, node) => {
    if (typeof getPos === 'function') {
        editor.chain().focus().deleteRange({ from: getPos(), to: getPos() + node.nodeSize }).run();
    }
};

const BlockReference = Node.create({
    name: 'blockReference',
    group: 'block',
    atom: true,
    selectable: true,
    draggable: true,

    addOptions() {
        return { editBase: '/admin' };
    },

    addAttributes() {
        return {
            blockId: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-block'),
                renderHTML: (attributes) =>
                    attributes.blockId ? { 'data-block': attributes.blockId } : {},
            },
        };
    },

    parseHTML() {
        return [{ tag: 'div[data-block]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes)];
    },

    addNodeView() {
        const editBase = this.options.editBase;

        return ({ node, getPos, editor }) => {
            const id = node.attrs.blockId ?? '';
            const dom = document.createElement('div');
            dom.className = 'cms-block-ref';
            dom.contentEditable = 'false';

            const grip = document.createElement('span');
            grip.className = 'cms-block-ref__grip';
            grip.title = 'Drag to reorder';
            grip.innerHTML = ICON_GRIP;

            const type = document.createElement('span');
            type.className = 'cms-block-ref__type';
            type.textContent = 'block';

            const name = document.createElement('span');
            name.className = 'cms-block-ref__name';
            name.textContent = `Block #${id}`;

            const edit = document.createElement('a');
            edit.className = 'cms-block-ref__edit';
            edit.href = `${editBase}/blocks/edit/${id}`;
            edit.target = '_blank';
            edit.rel = 'noopener';
            edit.textContent = 'edit ↗';

            const remove = document.createElement('button');
            remove.className = 'cms-block-ref__delete';
            remove.type = 'button';
            remove.title = 'Remove block';
            remove.setAttribute('aria-label', 'Remove block');
            remove.textContent = '×';
            remove.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                deleteNode(editor, getPos, node);
            });

            dom.append(grip, type, name, edit, remove);

            return {
                dom,
                stopEvent: (event) => event.target.closest('a, button') !== null,
            };
        };
    },
});

const Image = Node.create({
    name: 'image',
    group: 'block',
    draggable: true,

    addOptions() {
        return { onEdit: null };
    },

    addAttributes() {
        return {
            src: { default: null },
            alt: { default: '' },
            href: { default: null, rendered: false },
        };
    },

    parseHTML() {
        return [{
            tag: 'img[src]',
            getAttrs: (img) => {
                const parent = img.parentElement;

                return { href: parent && parent.tagName === 'A' ? parent.getAttribute('href') : null };
            },
        }];
    },

    renderHTML({ node, HTMLAttributes }) {
        const img = ['img', mergeAttributes(HTMLAttributes)];

        return node.attrs.href ? ['a', { href: node.attrs.href }, img] : img;
    },

    addNodeView() {
        const onEdit = this.options.onEdit;

        return ({ node, getPos, editor }) => {
            const update = (attrs) => {
                if (typeof getPos === 'function') {
                    editor.view.dispatch(editor.state.tr.setNodeMarkup(getPos(), undefined, { ...node.attrs, ...attrs }));
                }
            };

            const figure = document.createElement('figure');
            figure.className = node.attrs.href ? 'cms-img cms-img--linked' : 'cms-img';
            figure.contentEditable = 'false';

            const img = document.createElement('img');
            img.src = node.attrs.src ?? '';
            img.alt = node.attrs.alt ?? '';
            img.loading = 'lazy';

            const bar = document.createElement('div');
            bar.className = 'cms-img__bar';

            const editButton = barButton('Edit', 'Image settings', () => {
                if (typeof onEdit === 'function') {
                    onEdit({ attrs: { ...node.attrs }, apply: update });
                }
            });

            const deleteButton = barButton('×', 'Remove image', () => deleteNode(editor, getPos, node));
            deleteButton.classList.add('cms-img__btn--del');

            bar.append(editButton, deleteButton);
            figure.append(img, bar);

            return {
                dom: figure,
                stopEvent: (event) => bar.contains(event.target),
            };
        };
    },
});

export { Editor, StarterKit, BlockReference, Image, Link, TextAlign, Table, TableRow, TableHeader, TableCell };
