import assert from 'node:assert/strict';
import { createRequire } from 'node:module';
import { mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';

const require = createRequire(process.env.BROWSER_TOOLS_PACKAGE || new URL('../web/package.json', import.meta.url));
const { chromium, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;
const base = process.env.APP_URL || 'http://127.0.0.1:8080';
const output = fileURLToPath(new URL('../web/test-results/', import.meta.url));
await mkdir(output, { recursive: true });
const browser = await chromium.launch({
  headless: true,
  ...(process.platform === 'win32' ? { channel: 'chrome' } : {}),
});
const errors = [];
const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const page = await context.newPage();
page.on('pageerror', error => errors.push(error.message));
const log = message => console.log(`PASS: ${message}`);

try {
  await page.goto(base);
  const bootstrap = await (await context.request.get(`${base}/api/bootstrap`)).json();
  assert(bootstrap.demoAccounts.length >= 2, 'Need two fictional evaluation accounts');
  const future = new Date(`${bootstrap.today}T12:00:00Z`);
  future.setUTCDate(future.getUTCDate() + 21);
  while ([0, 6].includes(future.getUTCDay())) future.setUTCDate(future.getUTCDate() + 1);
  const date = future.toISOString().slice(0, 10);
  const url = `${base}/?view=times&mensaId=eupener&date=${date}`;
  await page.goto(url);
  await expect(page.getByRole('heading', { name: 'Find your lunch time' })).toBeVisible();
  await expect(page.getByText('These are submitted plans, not live crowd or queue measurements.')).toBeVisible();
  await page.locator('input[type=radio][value="12:30"]').check();
  await page.locator('input[type=radio][value="13:00"]').check();
  await expect(page.locator('input[type=radio]:checked')).toHaveCount(1);
  await expect(page.locator('input[type=radio][value="13:00"]')).toBeChecked();
  await page.locator('input[type=radio][value="13:00"]').press('ArrowDown');
  await expect(page.locator('input[type=radio][value="13:15"]')).toBeChecked();
  await page.locator('input[type=radio][value="13:15"]').press('ArrowUp');
  await expect(page.locator('input[type=radio]:checked')).toHaveCount(1);
  log('single-slot replacement; draft does not save as a guest');

  await page.getByRole('button', { name: 'Sign in to confirm', exact: true }).click();
  await page.getByRole('dialog').getByRole('combobox', { name: /Evaluation account/ }).selectOption(bootstrap.demoAccounts[0].email);
  await page.getByRole('dialog').getByRole('button', { name: 'Sign in', exact: true }).focus();
  await page.keyboard.press('Tab');
  await expect(page.getByRole('dialog').getByRole('button', { name: 'Close sign in', exact: true })).toBeFocused();
  await page.keyboard.press('Shift+Tab');
  await expect(page.getByRole('dialog').getByRole('button', { name: 'Sign in', exact: true })).toBeFocused();
  log('keyboard radio navigation and modal focus containment');
  await page.getByRole('dialog').getByRole('button', { name: 'Sign in', exact: true }).click();
  await expect(page.getByRole('dialog')).toHaveCount(0);
  await expect(page.locator('input[type=radio][value="13:00"]')).toBeChecked();
  log('draft survives authentication');

  // Clean only this account/date's evaluation plan if a previous interrupted run left it.
  const prior = await (await context.request.get(`${base}/api/plans?date=${date}`)).json();
  const submitName = prior.plan && prior.plan.status !== 'cancelled' ? 'Save changes' : 'Confirm my plan';
  await page.getByRole('button', { name: submitName, exact: true }).click();
  await expect(page.getByText('Your lunch plan is confirmed.', { exact: true })).toBeVisible();
  await page.reload();
  await expect(page.locator('input[type=radio][value="13:00"]')).toBeChecked();
  await expect(page.locator('.saved-banner')).toContainText('13:00');
  log('explicit confirmation persists after reload');

  await page.locator('input[type=radio][value="12:30"]').check();
  await page.getByRole('button', { name: 'Save changes', exact: true }).click();
  await expect(page.locator('.saved-banner')).toContainText('12:30');
  await page.reload();
  await expect(page.locator('.saved-banner')).toContainText('12:30');
  log('editing transfers the one saved arrival time');

  await page.getByRole('button', { name: 'Cancel saved plan', exact: true }).click();
  await page.getByRole('button', { name: 'Yes, cancel plan', exact: true }).click();
  await expect(page.getByText('Your lunch plan was cancelled. Its reminder has been cancelled too.', { exact: true })).toBeVisible();
  await expect(page.locator('.saved-banner')).toHaveCount(0);
  log('explicit cancellation removes the saved confirmation');

  await page.goto(`${url}&slotId=12%3A30`);
  await expect(page.locator('input[type=radio][value="12:30"]')).toBeChecked();
  const afterLink = await (await context.request.get(`${base}/api/plans?date=${date}`)).json();
  assert.equal(afterLink.plan?.status, 'cancelled', 'GET deep link must not save');
  log('contextual web link preselects one draft without saving');

  await page.getByLabel('Mensa', { exact: true }).selectOption('academica');
  await expect(page.locator('input[type=radio]:checked')).toHaveCount(0);
  log('changing mensa resets the draft');
  await page.getByLabel('Mensa', { exact: true }).selectOption('eupener');
  await expect(page.locator('input[type=radio][value="11:15"]')).toBeVisible();

  for (const [width, height] of [[1440, 900], [768, 1024], [390, 844], [320, 844]]) {
    await page.setViewportSize({ width, height });
    assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), `Overflow at ${width}`);
    const accessibility = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
    const issues = accessibility.violations.map(v => `${v.id}: ${v.description}`).join('\n');
    assert.equal(accessibility.violations.length, 0, `Accessibility at ${width}:\n${issues}`);
    await page.screenshot({ path: `${output}/campus-navy-${width}.png`, fullPage: true });
    log(`responsive layout and automated accessibility at ${width}×${height}`);
  }

  await page.setViewportSize({ width: 1440, height: 900 });
  await page.evaluate(() => { document.documentElement.style.fontSize = '200%'; });
  assert(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), 'Overflow at 200% text zoom');
  log('200% text resizing without horizontal overflow');
  await page.evaluate(() => { document.documentElement.style.fontSize = ''; });
  await page.getByRole('navigation', { name: 'Main navigation', exact: true }).getByRole('button', { name: 'Friends', exact: true }).click();
  await expect(page.getByRole('heading', { name: 'Add a friend', exact: true })).toBeVisible();
  await page.getByRole('button', { name: 'Scan a friend’s QR code', exact: true }).click();
  await expect(page.getByLabel('Or enter an invitation code', { exact: true })).toBeVisible();
  const friendAccessibility = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21aa']).analyze();
  assert.equal(friendAccessibility.violations.length, 0, friendAccessibility.violations.map(v => v.id).join(', '));
  log('friend-code fallback remains available without camera access');
  assert.deepEqual(errors, [], 'No uncaught browser errors');
  console.log('Browser integration checks passed. Screenshots remain private, ignored development artifacts.');
} catch (error) {
  console.error('Visible dialogs:', await page.getByRole('dialog').allTextContents().catch(() => []));
  await page.screenshot({ path: `${output}/failure.png`, fullPage: true, timeout: 10000 }).catch(() => {});
  console.error(error);
  process.exitCode = 1;
} finally {
  await browser.close();
}
