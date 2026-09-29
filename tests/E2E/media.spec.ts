import { test, expect } from '@playwright/test';
import { adminUrl, loginAs } from './pages/auth';

test('upload an image and open its detail modal', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto(adminUrl('/media'));

  await page.setInputFiles('[data-upload-input]', 'tests/Fixture/files/sample.png');

  const card = page.locator('.cms-media-card', { hasText: 'sample.png' }).first();
  await expect(card).toBeVisible({ timeout: 15000 });

  await card.click();
  await expect(page.locator('.cms-media-detail')).toBeVisible();
  await expect(page.locator('.cms-renditions')).toBeVisible();
});
