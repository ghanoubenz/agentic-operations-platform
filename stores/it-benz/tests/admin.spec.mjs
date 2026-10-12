// Shop-manager back-office: simplified menu, quick-access widget, delivery prices, banners, statuses.
// Uses the staging-only "gestion" account created by scripts/test.sh.
import { test, expect } from '@playwright/test';

const shots = new URL('../docs/screenshots/', import.meta.url).pathname;

test.beforeEach(async ({ page }) => {
  await page.goto('/wp-login.php');
  await page.fill('#user_login', 'gestion');
  await page.fill('#user_pass', process.env.SHOP_MANAGER_PASS || 'staging-gestion-pass');
  await page.click('#wp-submit');
  await page.waitForURL(/wp-admin/);
});

test('shop manager sees a simplified dashboard', async ({ page }) => {
  await page.goto('/wp-admin/');
  await expect(page.locator('#itbenz_quick')).toContainText('Nouveau produit');
  const menu = page.locator('#adminmenu');
  await expect(menu).toContainText('Bannières');
  await expect(menu).toContainText('IT-BENZ');
  await expect(menu).not.toContainText('Plugins');
  await expect(menu).not.toContainText('Extensions');
  await expect(page.locator('#menu-posts')).toHaveCount(0);
  await page.screenshot({ path: shots + 'admin-dashboard.png' });
});

test('delivery prices can be edited', async ({ page }) => {
  await page.goto('/wp-admin/admin.php?page=itbenz-delivery');
  await expect(page.locator('table tbody tr')).toHaveCount(69);
  const field = page.locator('input[name="rates[DZ-16][desk]"]');
  const before = await field.inputValue();
  await field.fill('450');
  await Promise.all([page.waitForURL(/updated=1/), page.click('#submit')]);
  await expect(page.locator('.notice-success')).toBeVisible();
  await expect(page.locator('input[name="rates[DZ-16][desk]"]')).toHaveValue('450');
  await page.locator('input[name="rates[DZ-16][desk]"]').fill(before);
  await Promise.all([page.waitForURL(/updated=1/), page.click('#submit')]);
  await page.screenshot({ path: shots + 'admin-delivery.png' });
});

test('orders list shows Algerian workflow statuses and wilaya column', async ({ page }) => {
  await page.goto('/wp-admin/admin.php?page=wc-orders');
  await expect(page.locator('.subsubsub')).toContainText('Nouvelle');
  await expect(page.locator('#bulk-action-selector-top')).toContainText('Expédiée');
  await expect(page.locator('th#itbenz_dest')).toBeVisible();
  await page.screenshot({ path: shots + 'admin-orders.png' });
});

test('shop manager cannot reach plugin management', async ({ page }) => {
  const res = await page.goto('/wp-admin/plugins.php');
  expect(res.status()).toBe(403);
});
