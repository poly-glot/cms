import { test, expect } from '@playwright/test';
import {
    adminPost,
    adminUrl,
    ALL_ROLES,
    createDraftPost,
    deleteDraftPost,
    EDITORIAL_ROLES,
    loginAs,
    NON_ADMIN_ROLES,
    NON_EDITORIAL_ROLES,
    restoreSeedContent,
    restoreSeedRoles,
    SEED_USERS,
    sql,
} from './pages/auth';

test.describe.configure({ mode: 'serial' });

let postId = 0;

test.beforeAll(() => {
    postId = createDraftPost();
});

test.afterAll(() => {
    restoreSeedRoles();
    restoreSeedContent();
    deleteDraftPost();
});

for (const role of ALL_ROLES) {
    test(`${role} can open their own account page`, async ({ page }) => {
        await loginAs(page, role);

        const response = await page.goto(adminUrl('/account'));

        expect(response?.status()).toBe(200);
        await expect(page.locator('.cms-account')).toBeVisible();
    });
}

test('admin can open the Users & Roles roster', async ({ page }) => {
    await loginAs(page, 'admin');

    const response = await page.goto(adminUrl('/users'));

    expect(response?.status()).toBe(200);
    await expect(page.locator('.cms-roster .cms-member').first()).toBeVisible();
});

test('admin can open the invite screen', async ({ page }) => {
    await loginAs(page, 'admin');

    const response = await page.goto(adminUrl('/users/invite'));

    expect(response?.status()).toBe(200);
    await expect(page.getByRole('heading', { name: 'Invite a user' })).toBeVisible();
});

for (const role of NON_ADMIN_ROLES) {
    test(`${role} is denied the Users & Roles roster`, async ({ page }) => {
        await loginAs(page, role);

        const response = await page.goto(adminUrl('/users'));

        expect(response?.status()).toBe(403);
        await expect(page.locator('.cms-roster')).toHaveCount(0);
    });

    test(`${role} is denied the invite screen`, async ({ page }) => {
        await loginAs(page, role);

        const response = await page.goto(adminUrl('/users/invite'));

        expect(response?.status()).toBe(403);
    });

    test(`${role} cannot change another member's role`, async ({ page }) => {
        await loginAs(page, role);

        const response = await adminPost(page, `/users/edit-role/${SEED_USERS.editor.membershipId}`, {
            role: 'admin',
        });

        expect(response.status()).toBe(403);
    });

    test(`${role} cannot send a member a password reset`, async ({ page }) => {
        await loginAs(page, role);

        const response = await adminPost(page, `/users/send-reset/${SEED_USERS.editor.membershipId}`);

        expect(response.status()).toBe(403);
    });
}

for (const role of EDITORIAL_ROLES) {
    test(`${role} can open the editor for any page`, async ({ page }) => {
        await loginAs(page, role);

        const response = await page.goto(adminUrl('/pages/edit/1'));

        expect(response?.status()).toBe(200);
        await expect(page.locator('.cms-main')).toBeVisible();
    });
}

for (const role of NON_EDITORIAL_ROLES) {
    test(`${role} cannot edit a page they do not own`, async ({ page }) => {
        await loginAs(page, role);

        const response = await page.goto(adminUrl('/pages/edit/1'));

        expect(response?.status()).toBe(403);
    });

    test(`${role} cannot delete a page they do not own`, async ({ page }) => {
        await loginAs(page, role);

        const response = await adminPost(page, '/pages/delete/1');

        expect(response.status()).toBe(403);
    });

    test(`${role} cannot publish a page`, async ({ page }) => {
        await loginAs(page, role);

        const response = await adminPost(page, '/pages/publish/3');

        expect(response.status()).toBe(403);
    });
}

for (const role of ALL_ROLES) {
    test(`${role} can open the new-page screen`, async ({ page }) => {
        await loginAs(page, role);

        const response = await page.goto(adminUrl('/pages/add'));

        expect(response?.status()).toBe(200);
        await expect(page.locator('.cms-main')).toBeVisible();
    });
}

test('editor can open the editor for any post', async ({ page }) => {
    await loginAs(page, 'editor');

    const response = await page.goto(adminUrl(`/posts/edit/${postId}`));

    expect(response?.status()).toBe(200);
    await expect(page.locator('[data-title-input]')).toBeVisible();
});

for (const role of NON_EDITORIAL_ROLES) {
    test(`${role} cannot edit a post they do not own`, async ({ page }) => {
        await loginAs(page, role);

        const response = await page.goto(adminUrl(`/posts/edit/${postId}`));

        expect(response?.status()).toBe(403);
    });

    test(`${role} cannot publish a post`, async ({ page }) => {
        await loginAs(page, role);

        const response = await adminPost(page, `/posts/publish/${postId}`);

        expect(response.status()).toBe(403);
    });
}

test('admin is authorized to send a password reset', async ({ page }) => {
    await loginAs(page, 'admin');

    const response = await adminPost(page, '/users/send-reset/999999');

    expect(response.status()).toBe(404);
});

test("admin can change a member's role", async ({ page }) => {
    const target = SEED_USERS.author.membershipId;
    await loginAs(page, 'admin');

    const response = await adminPost(page, `/users/edit-role/${target}`, { role: 'editor' });

    try {
        expect(response.status()).toBe(302);
        expect(sql(`SELECT role FROM memberships WHERE id = ${target}`)).toBe('editor');
    } finally {
        restoreSeedRoles();
    }

    expect(sql(`SELECT role FROM memberships WHERE id = ${target}`)).toBe('author');
});

test('an editor can publish a page', async ({ page }) => {
    await loginAs(page, 'editor');

    const response = await adminPost(page, '/pages/publish/3');

    try {
        expect(response.status()).toBe(302);
        expect(sql('SELECT status FROM pages WHERE id = 3')).toBe('live');
    } finally {
        restoreSeedContent();
    }

    expect(sql('SELECT status FROM pages WHERE id = 3')).toBe('draft');
});

test('an editor can publish a post', async ({ page }) => {
    await loginAs(page, 'editor');

    const response = await adminPost(page, `/posts/publish/${postId}`);

    try {
        expect(response.status()).toBe(302);
        expect(sql(`SELECT status FROM posts WHERE id = ${postId}`)).toBe('live');
    } finally {
        restoreSeedContent();
    }

    expect(sql(`SELECT status FROM posts WHERE id = ${postId}`)).toBe('draft');
});
