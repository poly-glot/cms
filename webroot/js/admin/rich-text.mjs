import { Editor } from './vendor/tiptap.bundle.mjs';
import { el, svgIcon } from './kit/dom.mjs';

export function mountRichText(textarea, { extensions, groups, classes }) {
    const toolbar = el('div', classes.toolbar);
    const surface = el('div', classes.surface);

    const wrapper = el('div', classes.wrapper);
    wrapper.append(toolbar, surface);
    textarea.style.display = 'none';
    textarea.parentNode.insertBefore(wrapper, textarea);

    const editor = new Editor({ element: surface, extensions, content: textarea.value || '' });

    const syncers = [];
    const makeButton = (command) => {
        const button = el('button', command.cls ? `${classes.button} ${command.cls}` : classes.button);
        button.type = 'button';
        if (command.title) {
            button.title = command.title;
            button.setAttribute('aria-label', command.title);
        }
        if (command.icon !== undefined) {
            button.append(svgIcon(command.icon));
        } else {
            button.textContent = command.label;
        }
        button.addEventListener('click', command.run);
        syncers.push(() => button.classList.toggle(classes.active, command.active()));

        return button;
    };

    for (const commands of groups(editor)) {
        const parent = classes.group ? toolbar.appendChild(el('div', classes.group)) : toolbar;
        parent.append(...commands.map(makeButton));
    }

    const sync = () => {
        for (const syncer of syncers) {
            syncer();
        }
    };
    editor.on('selectionUpdate', sync);
    editor.on('transaction', sync);

    return { editor, wrapper, toolbar, surface, syncers, sync };
}
