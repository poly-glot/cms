import { showToast } from './dom.mjs';

export const ADMIN_BASE = (document.querySelector('meta[name="workspace-path"]')?.content ?? '') + '/admin';

function adminUrl(path) {
    if (path.startsWith(ADMIN_BASE)) {
        return path;
    }

    return path.startsWith('/') ? ADMIN_BASE + path : path;
}

function csrfHeader() {
    return { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content ?? '' };
}

async function send(path, options) {
    const response = await fetch(adminUrl(path), { credentials: 'same-origin', ...options });

    const sessionEnded = response.redirected && new URL(response.url).pathname.endsWith('/login');
    if (sessionEnded) {
        showToast('Your session has ended — taking you back to sign in.');
        window.location.assign(response.url);

        return null;
    }

    if (response.status === 429) {
        showToast('The server needs a moment — please try that again.');
    }

    return response;
}

async function sendJson(path, { headers = {}, ...options } = {}) {
    const response = await send(path, { ...options, headers: { Accept: 'application/json', ...headers } });
    if (response === null) {
        throw new Error(`Session ended: ${path}`);
    }

    if (response.status === 204) {
        return null;
    }

    const isJson = (response.headers.get('Content-Type') ?? '').includes('application/json');
    if (!response.ok || !isJson) {
        throw new Error(`Request failed: ${response.status} ${response.url}`);
    }

    return response.json();
}

function request(method, path, body, headers = {}) {
    return sendJson(path, { body, headers: { ...csrfHeader(), ...headers }, method });
}

export const getJson = (path, { signal } = {}) => sendJson(path, { signal });

export const postForm = (path, fields) => request('POST', path, new URLSearchParams(fields));

export const postUpload = (path, formData) => request('POST', path, formData);

export const patchForm = (path, fields) => request('PATCH', path, new URLSearchParams(fields));

export const postJson = (path, payload) =>
    request('POST', path, JSON.stringify(payload), { 'Content-Type': 'application/json' });

export async function postAction(path, body) {
    const response = await send(path, { body, headers: csrfHeader(), method: 'POST' });

    return response !== null && response.ok;
}
