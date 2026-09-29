import { expect, test, type Locator, type Page } from '@playwright/test';
import { adminUrl, loginAs } from './pages/auth';

const OWN_TOP = ':scope > .cms-schema-field__top';

function topRows(page: Page): Locator {
  return page.locator('[data-field-list] > .cms-schema-field');
}

function subRows(repeater: Locator): Locator {
  return repeater.locator('.cms-schema-group__list > .cms-schema-field');
}

function handle(row: Locator): Locator {
  return row.locator(`${OWN_TOP} [data-handle]`);
}

async function labels(rows: Locator): Promise<string[]> {
  return rows.locator(`${OWN_TOP} [data-label-input]`).evaluateAll((inputs) => inputs.map((input) => (input as HTMLInputElement).value));
}

async function buildSchema(page: Page): Promise<Locator> {
  for (const text of ['First', 'Rows', 'Last']) {
    await page.click('[data-add-field]');
    await topRows(page).last().locator(`${OWN_TOP} [data-label-input]`).fill(text);
  }

  await topRows(page).nth(1).locator(`${OWN_TOP} select`).selectOption('repeater');
  const repeater = topRows(page).nth(1);
  for (const text of ['Alpha', 'Beta']) {
    await repeater.getByRole('button', { name: '+ Add sub-field' }).click();
    await subRows(repeater).last().locator(`${OWN_TOP} [data-label-input]`).fill(text);
  }

  return repeater;
}

test.beforeEach(async ({ page }) => {
  await loginAs(page, 'admin');
  await page.goto(adminUrl('/content-model/pages/fields'));
});

test('dropping a repeater sub-field on a top-level field moves nothing', async ({ page }) => {
  const repeater = await buildSchema(page);

  await handle(subRows(repeater).nth(0)).dragTo(topRows(page).nth(0), { targetPosition: { x: 40, y: 4 } });

  expect(await labels(topRows(page))).toEqual(['First', 'Rows', 'Last']);
  expect(await labels(subRows(repeater))).toEqual(['Alpha', 'Beta']);
});

test('dragging a sub-field within its repeater reorders only that repeater', async ({ page }) => {
  const repeater = await buildSchema(page);

  await handle(subRows(repeater).nth(1)).dragTo(subRows(repeater).nth(0), { targetPosition: { x: 40, y: 2 } });

  expect(await labels(topRows(page))).toEqual(['First', 'Rows', 'Last']);
  expect(await labels(subRows(repeater))).toEqual(['Beta', 'Alpha']);
});
