import { test, expect } from '@playwright/test';
import { adminUrl, loginAs } from './pages/auth';

test('moderation queue renders comments, badge, and reply toggle', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto(adminUrl('/comments'));

  await expect(page.locator('.cms-comment').first()).toBeVisible();
  await expect(page.locator('.cms-comment__avatar').first()).toBeVisible();
  await expect(page.locator('.cms-sidebar__badge')).toBeVisible();

  const firstComment = page.locator('.cms-comment').first();
  await expect(firstComment.locator('[data-reply-form]')).toBeHidden();
  await firstComment.locator('[data-reply-toggle]').click();
  await expect(firstComment.locator('[data-reply-form]')).toBeVisible();
});
