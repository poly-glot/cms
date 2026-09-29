import { bindSlugFollow } from '../admin/kit/slug.mjs';

const nameInput = document.querySelector('[data-workspace-name]');
const slugInput = document.querySelector('[data-workspace-slug]');

if (nameInput && slugInput) {
    bindSlugFollow(nameInput, slugInput);
}
