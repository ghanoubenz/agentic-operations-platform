# 7. Testing checklist

✅ = automated in `tests/` (run `bash scripts/test.sh` against staging with demo content).
☐ = manual check before go-live.

Last automated run on local staging: **10/10 passed** (8 desktop + 2 mobile/admin), WooCommerce 11.2.1, PHP 8.3.

## Storefront

* ✅ Homepage renders:
  * hero headline
  * 10 category tiles
  * inspiration card
  * 3 collections
  * product cards with DA prices
* ✅ Draft policy pages are not linked publicly.
* ✅ Product page shows brand, SKU, stock, the spec table (e.g. Résolution 2560 × 1440) and the COD box.
* ✅ Out-of-stock product has no add-to-cart button.
* ✅ Mobile (Pixel 7): menu opens and closes, no horizontal scroll.
* ☐ Search returns relevant products (`souris`, `clavier`, a real brand name).
* ☐ Every category page lists the right products; sorting works.
* ☐ Real product photos: sharp, square, white background, WebP generated (check the page source).
* ☐ WhatsApp buttons open the right number with a pre-filled message.
* ☐ French texts reviewed (after installing the fr_FR language pack, WooCommerce's own strings are French).

## Cart & checkout (critical path)

* ✅ Add 2 different products, change a quantity, update the cart: subtotal is correct (7 600 DA).
* ✅ Invalid phone (`0212 34 56`) is rejected server-side.
* ✅ A commune that doesn't belong to the chosen wilaya is rejected server-side, even if injected into the form.
* ✅ Wilaya → commune dropdown, home and stop-desk options and prices shown. Totals recalculate on the server (8 200 / 8 000 DA).
* ✅ Order placed with COD → confirmation page with `ITB-…` number, total and "Paiement à la livraison".
* ✅ Order saved: status *Nouvelle*, phone normalized to `0555123456`, shipping 600 DA, stock reduced.
* ✅ Merchant e-mail "[IT-BENZ] Nouvelle commande ITB-…" delivered (Mailpit). Customer e-mail sent when an address is given.
* ✅ Same phone + same cart again within 3 minutes → "déjà été enregistrée" (duplicate guard).
* ✅ A wilaya with no configured delivery price cannot place an order.
* ☐ Double-click on "Confirmer la commande" on a slow 3G connection creates only one order.
* ☐ Real delivery prices entered for all 69 wilayas, then spot-check 5 wilayas, including a new one (59–69).
* ☐ Free-delivery threshold (if used) works at, just below and just above the amount.
* ☐ Coupon: percentage and fixed amount, expiry, minimum amount.
* ☐ Production: one real COD order end-to-end, then cancel it and check the stock is restored.

## Back-office

* ✅ Shop manager: simplified menu, quick-access widget, no Plugins/Extensions screens (HTTP 403 on plugins.php).
* ✅ Delivery price table (69 wilayas) saves and reloads.
* ✅ Orders list shows *Nouvelle* etc., the "Passer en Expédiée" bulk action and the Wilaya/téléphone column.
* ✅ Status workflow Nouvelle → Confirmée → En préparation → Expédiée → Retournée; return restores stock (checked with WP-CLI).
* ✅ CSV template imports: draft status, category, brand, model and spec attributes.
* ☐ Banners: add, reorder and draft a banner; the homepage updates.
* ☐ Category "Afficher sur l'accueil" + collection photo + highlight text.
* ☐ Analytics → Orders → CSV download.

## Performance & SEO

* ✅ Lighthouse mobile on staging (no cache/CDN): performance 96–99, accessibility 98–100, best practices 100, SEO 100 when indexable.
* ☐ Re-run on production with real images: `CHROME_PATH=… node tests/lighthouse.mjs https://it-benz.dz`.
* ☐ Cart/checkout/account pages are not cached (add an item, reload the homepage from another browser: its cart must stay empty).
* ☐ `noindex` on staging; indexing enabled on production; sitemap submitted.

## Security & operations

* ☐ HTTPS forced, valid certificate, HSTS enabled.
* ☐ Admin account uses a unique username and a strong password (2FA recommended).
* ✅ 5 wrong passwords → login locked for 15 minutes (verified with curl on staging).
* ✅ `/xmlrpc.php` disabled; `/?author=1` and `/author/<login>/` redirect to home; users sitemap and `/wp-json/wp/v2/users` are not public; security headers present (verified with curl on staging).
* ☐ Host daily backup is active **and** a restore has been tested once on staging.
* ☐ `scripts/backup.sh` cron running, with a copy stored off-server.
* ☐ Existing e-mail (MX) still works after the DNS switch: send and receive a test.
