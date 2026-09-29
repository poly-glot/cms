export function slugify(text) {
    return text
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export function machineName(text) {
    const base = slugify(text).replaceAll('-', '_');
    if (base === '') {
        return '';
    }

    return (/^[a-z]/.test(base) ? base : 'f_' + base).slice(0, 50);
}

export function bindSlugFollow(sourceInput, slugInput) {
    let follows = slugInput.value === '' || slugInput.value === slugify(sourceInput.value);

    slugInput.addEventListener('input', () => {
        follows = slugInput.value === '';
    });

    sourceInput.addEventListener('input', () => {
        if (follows) {
            slugInput.value = slugify(sourceInput.value);
        }
    });
}
