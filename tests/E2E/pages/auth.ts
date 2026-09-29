import { execFileSync } from 'node:child_process';
import type { APIResponse, Page } from '@playwright/test';

export type SeedRole = 'admin' | 'editor' | 'author' | 'contributor';

export interface SeedUser {
    email: string;
    name: string;
    membershipId: number;
}

export const SEED_USERS: Record<SeedRole, SeedUser> = {
    admin: { email: 'admin@cabinet.local', name: 'Admin', membershipId: 1 },
    editor: { email: 'eleanor@cabinet.local', name: 'Eleanor Voss', membershipId: 2 },
    author: { email: 'marcus@cabinet.local', name: 'Marcus Hale', membershipId: 3 },
    contributor: { email: 'jun@cabinet.local', name: 'Jun Park', membershipId: 4 },
};

export const ALL_ROLES: SeedRole[] = ['admin', 'editor', 'author', 'contributor'];
export const EDITORIAL_ROLES: SeedRole[] = ['admin', 'editor'];
export const NON_EDITORIAL_ROLES: SeedRole[] = ['author', 'contributor'];
export const NON_ADMIN_ROLES: SeedRole[] = ['editor', 'author', 'contributor'];

const PASSWORD = 'dev-password-1';
const WORKSPACE = 'cabinet';

export function adminUrl(path = ''): string {
    return `/${WORKSPACE}/admin${path}`;
}

function resetRateLimits(): void {
    execFileSync('bin/cake', ['cache', 'clear', 'ratelimit'], { stdio: 'ignore' });
}

export async function loginAs(page: Page, role: SeedRole): Promise<Page> {
    const user = SEED_USERS[role];
    resetRateLimits();

    await page.goto('/login');
    await page.fill('input[name="email"]', user.email);
    await page.fill('input[name="password"]', PASSWORD);
    await page.click('button[type="submit"]');
    await page.waitForURL(`**/${WORKSPACE}/admin`);

    return page;
}

export async function adminPost(
    page: Page,
    path: string,
    form: Record<string, string> = {},
): Promise<APIResponse> {
    const token = await page.getAttribute('meta[name="csrf-token"]', 'content');
    if (token === null || token === '') {
        throw new Error('No CSRF token on the current admin page; call loginAs first.');
    }

    return page.request.post(adminUrl(path), {
        headers: { 'X-CSRF-Token': token },
        form,
        maxRedirects: 0,
        failOnStatusCode: false,
    });
}

const DATABASE = new URL(process.env.DATABASE_URL ?? 'mysql://cms:cms@db/cms');

export function sql(query: string): string {
    return execFileSync(
        'mysql',
        [
            '-h', DATABASE.hostname,
            '-P', DATABASE.port || '3306',
            '-u', decodeURIComponent(DATABASE.username),
            `--password=${decodeURIComponent(DATABASE.password)}`,
            DATABASE.pathname.slice(1),
            '-sN', '-e', query,
        ],
        { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] },
    ).trim();
}

export function restoreSeedRoles(): void {
    sql(
        "UPDATE memberships SET role = CASE id "
        + "WHEN 1 THEN 'admin' WHEN 2 THEN 'editor' "
        + "WHEN 3 THEN 'author' WHEN 4 THEN 'contributor' "
        + 'ELSE role END WHERE id IN (1, 2, 3, 4)',
    );
}

const DRAFT_POST_SLUG = 'e2e-draft-post';

export function createDraftPost(): number {
    sql(
        'INSERT IGNORE INTO posts (workspace_id, title, slug, author_id, created, modified) '
        + `VALUES (1, 'E2E draft post', '${DRAFT_POST_SLUG}', 1, NOW(), NOW())`,
    );

    return Number(sql(`SELECT id FROM posts WHERE workspace_id = 1 AND slug = '${DRAFT_POST_SLUG}'`));
}

export function deleteDraftPost(): void {
    sql(`DELETE FROM posts WHERE workspace_id = 1 AND slug = '${DRAFT_POST_SLUG}'`);
}

export function restoreSeedContent(): void {
    sql("UPDATE pages SET status = 'draft', published_at = NULL WHERE id = 3");
    sql('DELETE FROM page_revisions WHERE page_id = 3');
    sql(`UPDATE posts SET status = 'draft', published_at = NULL WHERE workspace_id = 1 AND slug = '${DRAFT_POST_SLUG}'`);
}
