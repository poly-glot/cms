import { test, expect } from '@playwright/test';
import { adminUrl, loginAs } from './pages/auth';

test('settings view renders + toggles + saves', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto(adminUrl('/settings'));
  await expect(page.locator('.cms-settings')).toBeVisible();
  await expect(page.locator('.cms-toggle')).toHaveCount(2);

  await page.click('button[form="settings-form"]');
  await expect(page.locator('.message')).toContainText('Settings saved');

  await page.locator('.message').click();
  await expect(page.locator('.message')).toHaveCount(0);
});

test('appearance: theme cards + activate reaches the public site', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto(adminUrl('/appearance'));
  await expect(page.locator('.cms-create__tile')).toHaveCount(3);
  await expect(page.locator('.cms-theme-card__active')).toHaveText('· active');

  const paper = page.locator('.cms-create__tile', { hasText: 'Paper' });
  await paper.click();
  await expect(paper.locator('.cms-theme-card__active')).toBeVisible();
  await page.goto('/cabinet/about');
  await expect(page.locator('link[href*="paper.css"]')).toHaveCount(1);

  await page.goto(adminUrl('/appearance'));
  const heritage = page.locator('.cms-create__tile', { hasText: 'Heritage' });
  await heritage.click();
  await expect(heritage.locator('.cms-theme-card__active')).toBeVisible();
});

test('users & roles list + role select', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto(adminUrl('/users'));
  await expect(page.locator('.cms-member__name', { hasText: 'Eleanor Voss' })).toBeVisible();
  await expect(page.getByLabel('Role for Eleanor Voss')).toHaveValue('editor');
});
