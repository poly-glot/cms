import { test, expect } from '@playwright/test';
import { adminUrl, loginAs, sql } from './pages/auth';

test.afterAll(() => {
  sql(`UPDATE pages SET body = '<p>Made by hand, since 1992.</p><div data-block="1"></div>' WHERE id = 1`);
  sql('DELETE FROM page_blocks WHERE page_id = 1 AND block_id <> 1');
});

test('insert a reusable block via the editor picker', async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto(adminUrl('/pages/edit/1'));
  await expect(page.locator('.cms-block-ref').first()).toBeVisible();

  await page.getByRole('button', { name: 'Insert Block' }).click();
  const founderQuote = page.locator('.cms-modal[open] .cms-picker__card', { hasText: 'Founder quote' });
  await expect(founderQuote).toBeVisible();

  const autosave = page.waitForResponse(
    (response) =>
      response.url().includes(adminUrl('/pages/autosave/1')) &&
      response.ok() &&
      (new URLSearchParams(response.request().postData() ?? '').get('body') ?? '').includes('data-block="2"'),
  );
  await founderQuote.click();
  await autosave;
});
