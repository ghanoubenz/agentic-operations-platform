// End-to-end tests for the critical path: browse → cart → COD checkout → order + e-mail.
// Requires the staging stack with demo content:  bash ../scripts/test.sh
import { test, expect } from '@playwright/test';
import fs from 'node:fs';

const MAILPIT = process.env.MAILPIT_URL || 'http://localhost:8025';
const shots = new URL('../docs/screenshots/', import.meta.url).pathname;
fs.mkdirSync(shots, { recursive: true });

async function chooseWilaya(page, state) {
  // The wilaya select is enhanced by selectWoo; set the underlying <select> and fire change.
  await page.evaluate((s) => jQuery('#billing_state').val(s).trigger('change'), state);
}
async function chooseCommune(page, name) {
  await page.evaluate((n) => jQuery('#billing_city').val(n).trigger('change'), name);
}
async function addToCart(page, slug, qty = 1) {
  await page.goto(`/produit/${slug}/`);
  await page.fill('input.qty', String(qty));
  await page.click('button.single_add_to_cart_button');
  await expect(page.locator('.woocommerce-message')).toBeVisible();
}
async function fillCheckout(page, { phone = '0555 12 34 56', state = 'DZ-16', commune = 'Bab Ezzouar' } = {}) {
  await page.goto('/commande/');
  await page.fill('#billing_first_name', 'Test Client');
  await page.fill('#billing_phone', phone);
  await page.fill('#billing_email', 'client@example.com');
  await chooseWilaya(page, state);
  if (commune) await chooseCommune(page, commune);
  await page.fill('#billing_address_1', 'Cité 100 logements, bâtiment 3');
  await page.waitForLoadState('networkidle');
}

test.describe.configure({ mode: 'serial' });

test('homepage shows hero, categories and product sections', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('.site-header .brand')).toBeVisible();
  await expect(page.locator('.hero-carousel__slide')).toHaveCount(3);
  await expect(page.locator('.collection')).toHaveCount(4);
  await expect(page.locator('.collection .full-banner')).toHaveCount(2);
  await expect(page.locator('.cat-strip .cat-tile')).toHaveCount(12);
  // Draft policy pages must not be linked publicly.
  await expect(page.locator('.site-footer a[href*="page_id="]')).toHaveCount(0);
  await expect(page.locator('ul.products li.card').first()).toBeVisible();
  await expect(page.locator('.card__price').first()).toContainText('DA');
  await page.screenshot({ path: shots + 'home-desktop.png', fullPage: true });
});

test('product page shows specs, stock, SKU and COD', async ({ page }) => {
  await page.goto('/produit/pc-portable-156-core-i5-16-go-512-go-ssd/');
  await expect(page.locator('.product_title')).toContainText('PC portable');
  await expect(page.locator('.pdp-facts')).toContainText('SKU');
  await expect(page.locator('.spec-table')).toContainText('Processeur');
  await expect(page.locator('.spec-table')).toContainText('Intel Core i5');
  await expect(page.locator('.pdp-service')).toContainText('Paiement à la livraison');
  await page.screenshot({ path: shots + 'product-desktop.png', fullPage: true });
});

test('out-of-stock product cannot be added', async ({ page }) => {
  await page.goto('/produit/manette-sans-fil-pour-pc/');
  await expect(page.locator('.pdp-facts .stock.out')).toBeVisible();
  await expect(page.locator('button.single_add_to_cart_button')).toHaveCount(0);
});

test('cart: multiple products, quantity update, then COD order with wilaya delivery', async ({ page }) => {
  await addToCart(page, 'souris-sans-fil-silencieuse', 1);  // 2 200
  await addToCart(page, 'cable-usb-c-tresse-1-m', 2);       // 2 × 1 200
  await page.goto('/panier/');
  await expect(page.locator('.cart-count')).toHaveText('3');
  const qty = page.locator('tr.cart_item:has-text("Souris") input.qty');
  await qty.fill('2');
  await page.click('button[name="update_cart"]');
  await expect(page.locator('.woocommerce-message')).toContainText(/mis à jour|updated/i);
  await expect(page.locator('.cart-subtotal .amount')).toContainText('6 800');
  await page.screenshot({ path: shots + 'cart-desktop.png', fullPage: true });

  // Invalid phone is rejected server-side.
  await fillCheckout(page, { phone: '0212 34 56' });
  await page.click('#place_order');
  await expect(page.locator('.woocommerce-error')).toContainText('Numéro de téléphone invalide');

  // Commune must belong to the wilaya.
  await fillCheckout(page, { state: 'DZ-31', commune: '' });
  await page.evaluate(() => jQuery('#billing_city').append(new Option('Bab Ezzouar', 'Bab Ezzouar')).val('Bab Ezzouar'));
  await page.click('#place_order');
  await expect(page.locator('.woocommerce-error')).toContainText('commune');

  // Valid order: Alger / Bab Ezzouar, home delivery (test fee 600).
  await fillCheckout(page);
  await expect(page.locator('#shipping_method')).toContainText('domicile');
  await expect(page.locator('#shipping_method')).toContainText('stop desk');
  await expect(page.locator('.order-total .amount')).toContainText('7 400');
  await page.screenshot({ path: shots + 'checkout-desktop.png', fullPage: true });
  // Pick stop desk to check the total is recalculated server-side.
  await page.locator('#shipping_method input[value*="desk"]').check();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('.order-total .amount')).toContainText('7 200');
  await page.locator('#shipping_method input[value*="home"]').check();
  await page.waitForLoadState('networkidle');
  await expect(page.locator('.order-total .amount')).toContainText('7 400');

  await page.click('#place_order');
  await page.waitForURL(/commande-recue/);
  await expect(page.locator('.woocommerce-order-overview__order')).toContainText('ITB-');
  await expect(page.locator('.woocommerce-order-overview__total')).toContainText('7 400');
  await expect(page.locator('.woocommerce-order-overview__payment-method')).toContainText('Paiement à la livraison');
  await page.screenshot({ path: shots + 'order-received-desktop.png', fullPage: true });
  const orderNo = (await page.locator('.woocommerce-order-overview__order strong').innerText()).trim();

  // Merchant + customer notifications reached the mailbox (Mailpit on staging).
  await expect.poll(async () => {
    const r = await fetch(`${MAILPIT}/api/v1/messages`);
    const j = await r.json();
    return j.messages.map((m) => m.Subject).join(' | ');
  }, { timeout: 20_000 }).toContain(`Nouvelle commande ${orderNo}`);

  // Same phone + same cart again within 3 minutes → duplicate guard.
  await addToCart(page, 'souris-sans-fil-silencieuse', 2);
  await addToCart(page, 'cable-usb-c-tresse-1-m', 2);
  await fillCheckout(page);
  await page.click('#place_order');
  await expect(page.locator('.woocommerce-error')).toContainText('déjà été enregistrée');
});

test('wilaya without configured delivery price cannot order', async ({ page }) => {
  await addToCart(page, 'ramette-papier-a4-70-g-500-feuilles', 1);
  await fillCheckout(page, { state: 'DZ-33', commune: 'Illizi' });
  await expect(page.locator('.woocommerce-shipping-totals')).toContainText('pas encore disponible');
  await page.click('#place_order');
  await expect(page.locator('.woocommerce-error')).toBeVisible();
  await expect(page).toHaveURL(/\/commande\/$/);
  await page.goto('/panier/');
  await page.locator('.product-remove a').first().click();
});

test('mobile navigation and layout @mobile', async ({ page }) => {
  await page.goto('/');
  await expect(page.locator('.cat-nav')).toBeHidden();
  await page.locator('.mobile-nav summary').click();
  await expect(page.locator('.mobile-nav__panel')).toBeVisible();
  await page.locator('.mobile-nav summary').click();
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth);
  expect(overflow).toBe(false);
  await page.screenshot({ path: shots + 'home-mobile.png', fullPage: true });
  await page.goto('/produit/souris-sans-fil-silencieuse/');
  await page.screenshot({ path: shots + 'product-mobile.png', fullPage: true });
});
