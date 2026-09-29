import { getJson } from './kit/http.mjs';
import { attachSuggest } from './kit/suggest.mjs';

const selectedSlugs = (chips) => Array.from(chips.querySelectorAll('[data-tag-chip]')).map((chip) => chip.dataset.slug);

const buildChip = (slug, label) => {
    const chip = document.createElement('span');
    chip.className = 'cms-chip';
    chip.dataset.tagChip = '';
    chip.dataset.slug = slug;

    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'tags[]';
    hidden.value = slug;

    const text = document.createElement('span');
    text.className = 'cms-chip__label';
    text.textContent = label;

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'cms-chip__remove';
    remove.dataset.tagRemove = '';
    remove.setAttribute('aria-label', 'Remove tag');
    remove.textContent = '×';

    chip.append(hidden, text, remove);

    return chip;
};

const initTagFilter = (root) => {
    const source = root.dataset.tagSource;
    const input = root.querySelector('[data-tag-input]');
    const chips = root.querySelector('[data-tag-chips]');
    const suggest = root.querySelector('[data-tag-suggest]');
    const mode = root.parentElement?.querySelector('[data-tag-mode]');

    if (!source || !input || !chips || !suggest) {
        return;
    }

    const syncMode = () => {
        if (mode) {
            mode.hidden = selectedSlugs(chips).length < 2;
        }
    };

    const addTag = (slug, label) => {
        if (selectedSlugs(chips).includes(slug)) {
            return;
        }

        chips.append(buildChip(slug, label));
        input.value = '';
        syncMode();
        input.focus();
    };

    attachSuggest(input, suggest, {
        fetchItems: async (term) => {
            const chosen = selectedSlugs(chips);

            return (await getJson(`${source}?q=${encodeURIComponent(term)}`).catch(() => [])).filter(
                (tag) => !chosen.includes(tag.slug),
            );
        },
        onPick: (tag) => addTag(tag.slug, tag.label),
    });

    chips.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-tag-remove]');

        if (remove) {
            remove.closest('[data-tag-chip]').remove();
            syncMode();
        }
    });

    syncMode();
};

document.querySelectorAll('[data-tag-filter]').forEach(initTagFilter);
