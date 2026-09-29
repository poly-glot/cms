import { test, expect } from '@playwright/test';
import { adminUrl, loginAs } from './pages/auth';

test('the vendored GraphiQL boots inside the playground mount', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto(adminUrl('/api-playground'));

  await expect(page.locator('[data-graphiql] .graphiql-container')).toBeVisible();
});
