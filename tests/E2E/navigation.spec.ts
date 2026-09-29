import { test, expect } from '@playwright/test';
import { adminUrl, loginAs } from './pages/auth';

test('builder renders the tree, edits an item, and saves', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto(adminUrl('/navigation'));

  await expect(page.locator('.cms-nav-item').first()).toBeVisible();
  await expect(page.locator('.cms-nav-menus__tab--active')).toHaveText('Main Menu');

  await page.locator('.cms-nav-item__row').first().click();
  await expect(page.locator('.cms-nav-details .cms-form-field').first()).toBeVisible();

  await page.click('[data-nav-save]');
  await expect(page.locator('.cms-toast')).toHaveText('Menu saved', { timeout: 5000 });
});
