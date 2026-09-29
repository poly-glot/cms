import { slugify } from './kit/slug.mjs';

export function bindInlineTitle() {
    const heading = document.querySelector('[data-title-input]');
    const targetName = heading?.dataset.titleTarget ?? 'title';
    const field = document.querySelector(`form input[name="${targetName}"]`);
    if (!heading || !field) {
        return;
    }

    const slugField = document.querySelector('form input[name="slug"]');
    let slugFollowsTitle = slugField !== null && slugField.value === '';

    const sync = () => {
        field.value = heading.textContent.trim();
        if (slugField && slugFollowsTitle) {
            slugField.value = slugify(heading.textContent);
        }
    };
    sync();

    slugField?.addEventListener('input', () => {
        slugFollowsTitle = slugField.value === '';
    });

    heading.addEventListener('input', sync);
    heading.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            heading.blur();
        }
    });
    heading.addEventListener('paste', (event) => {
        event.preventDefault();
        const text = event.clipboardData?.getData('text/plain') ?? '';
        document.execCommand('insertText', false, text.replace(/\s+/g, ' ').trim());
    });

    const focusAtEnd = () => {
        heading.focus();
        const range = document.createRange();
        range.selectNodeContents(heading);
        range.collapse(false);
        const selection = window.getSelection();
        selection?.removeAllRanges();
        selection?.addRange(range);
    };

    const editToggle = document.querySelector('[data-title-edit]');
    if (editToggle) {
        heading.addEventListener('focus', () => {
            editToggle.textContent = 'Save';
        });
        heading.addEventListener('blur', () => {
            editToggle.textContent = 'Edit';
        });
        editToggle.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });
        editToggle.addEventListener('click', () => {
            if (document.activeElement === heading) {
                heading.blur();
            } else {
                focusAtEnd();
            }
        });
    }
}
