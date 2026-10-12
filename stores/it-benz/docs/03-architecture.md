# 3. Technology stack and store structure

## Stack

| Layer | Choice | Why |
|---|---|---|
| CMS / commerce | WordPress 6.x + **WooCommerce 11.2.1** | Open source, a dashboard non-technical staff can use, a huge ecosystem, and scriptable with WP-CLI and Claude Code |
| Business logic | `wp-content/plugins/itbenz-commerce` (custom, GPL) | Everything Algeria-specific lives here, separate from the design |
| Design | `wp-content/themes/itbenz` (custom classic theme) | Light editorial layout from the approved reference, no page builder, ~40 KB CSS |
| Checkout | WooCommerce **classic** checkout (`[woocommerce_checkout]`) | Full server-side control of fields and validation (wilaya / commune / phone) |
| Payment | WooCommerce **Cash on delivery** (default); Chargily Pay later | See `06-chargily.md` |
| Fonts | Crimson Pro (display) + Inter (UI), self-hosted woff2, SIL OFL | Inter replaces Google Sans from the design reference, which is not openly licensed |
| Geo data | 69 wilayas / 1 541 communes, FR + AR | `geoalgeria` 2.3.0 (MIT compilation), following Law 26-06 of 4 April 2026 |
| Staging | Docker Compose: MariaDB 11, WordPress (PHP 8.3), Mailpit | Same code as production |
| Tests | Playwright E2E (10 tests) + Lighthouse script | `scripts/test.sh` |

Third-party plugins: **WooCommerce only.** On Hostinger, LiteSpeed Cache (free) is recommended for page caching (see deployment).

## URL structure (French, as requested)

| URL | Page |
|---|---|
| `/` | Homepage (editorial collections) |
| `/boutique/` | All products (`?promo=1` = products on sale) |
| `/categorie/<slug>/` | Category |
| `/produit/<slug>/` | Product |
| `/panier/` | Cart |
| `/commande/` | Checkout |
| `/commande/commande-recue/<id>/` | Order confirmation |
| `/mon-compte/` (+ `commandes/`, `adresses/`, `compte/`…) | Customer account (optional; guest checkout is on) |
| `/livraison/`, `/garantie/`, `/retours-echanges/`, `/conditions-generales-de-vente/`, `/contact/` | Policy pages: created as **drafts**, to be written by IT-BENZ |

## What the plugin does (`itbenz-commerce`)

| File | Responsibility |
|---|---|
| `includes/geo.php` | Replaces WooCommerce's 58-wilaya list with the 69 current wilayas. Commune lookup. Algerian mobile normalization (`05/06/07` + 8 digits; accepts `+213` / `00213`) |
| `includes/checkout.php` | Fields: full name, mobile, optional e-mail, wilaya, commune (dropdown filtered by wilaya), address, note. **Server-side validation** of everything. Algeria only. Phone saved as `0XXXXXXXXX`. Order numbers `ITB-1234`. **Duplicate guard**: same phone + same cart within 3 min is rejected. **Rate limit**: 5 orders per IP per hour (filter `itbenz_orders_per_ip_per_hour`). Currency shown as "DA" |
| `includes/shipping.php` | "Livraison par wilaya" method: home and stop-desk price per wilaya, plus an optional free-delivery threshold. A wilaya with no price cannot order online, so you never ship at an unknown cost. Admin table under WooCommerce → Livraison IT-BENZ |
| `includes/order-statuses.php` | Nouvelle (processing) → Confirmée → En préparation → Expédiée → Livrée (completed). Annulée restocks (core). **Retournée restocks** (custom). Bulk actions, colours |
| `includes/product-fields.php` | Model/reference and warranty fields. `itbenz_product_specs()` builds the spec table (brand, model, SKU, attributes, warranty) |
| `includes/banners.php` | "Bannières" post type (headline, eyebrow, subtitle, button, link, colour, photo) |
| `includes/collections.php` | Category options: "Afficher sur l'accueil", collection photo, highlight text |
| `includes/settings.php` | "IT-BENZ → Infos boutique": phone, WhatsApp, e-mail, address, hours, delivery/warranty/returns/support texts, social links. **Empty fields are never displayed** |
| `includes/admin.php` | Simplified back-office for the *Gestionnaire de boutique* role, a quick-access dashboard widget, a wilaya/phone column in the orders list, and a "Confirmer par WhatsApp" button on each order |
| `includes/security.php` | XML-RPC off, no user enumeration, security headers, generic login errors, login throttling (5 failures per IP / 15 min), emoji/oEmbed removed |
| `includes/mail.php` | SMTP from `wp-config.php` constants (never stored in the DB). Merchant e-mail subject `[IT-BENZ] Nouvelle commande ITB-… — wilaya — total` |

## Theme (`itbenz`): presentation only

* `front-page.php` assembles the homepage:
  * hero (Bannière #1)
  * category tiles
  * inspiration card (Bannière #2)
  * up to 4 collections, each a photo + colour card, 4 products, then a colour card + photo (Bannière #3+)
  * promotions
  * best sellers
  * new arrivals
  * service promises
* `woocommerce/content-product.php`: the product card (brand, name, DZD price with sale price, stock, add to cart).
* `inc/woocommerce.php`: product page with brand, stock/SKU/model facts, COD + warranty box, WhatsApp question button, "Caractéristiques techniques" tab, and related products.
* `inc/seo.php`: meta description and Open Graph. WooCommerce outputs Product JSON-LD. Cart/checkout/account are `noindex`.
* **Design tokens** are in `assets/css/main.css` (`:root`). To white-label for another client, change the tokens, the fonts and the logo. The plugin stays unchanged.

## Performance

* No page builder, no slider library, no jQuery on the homepage beyond what WooCommerce needs. About 1 KB of theme JS.
* Self-hosted variable fonts with `font-display: swap`; the two above-the-fold fonts are preloaded.
* WebP (or AVIF when the server supports it) sub-sizes are generated on upload. Responsive `sizes` are set. Below-the-fold images use `loading="lazy"`; the hero image uses `fetchpriority="high"`.
* Block-library CSS is removed, and WooCommerce's skin CSS is replaced by the theme.
* **Measured on staging** (Lighthouse 13, mobile emulation, no CDN, no page cache): performance **96–99**, accessibility **98–100**, best practices **100**, SEO **100** once indexing is enabled (`docs/lighthouse-staging.json`). Production numbers depend on hosting and real images, so re-measure after launch.

## Caching rules

* Page cache is fine for `/`, `/boutique/`, `/categorie/*`, `/produit/*`.
* **Never cache** `/panier/`, `/commande/*`, `/mon-compte/*`, `?add-to-cart=`, `wc-ajax=*`, or any request carrying the `woocommerce_items_in_cart`, `woocommerce_cart_hash` or `wp_woocommerce_session_*` cookies. LiteSpeed Cache applies these WooCommerce exclusions by default; check them after activation.
* The header cart counter refreshes through WooCommerce cart fragments, so cached pages still show the right count.

## Security summary

* HTTPS only, plus HSTS at the web-server level after go-live.
* Unique admin username. Strong passwords. Two-factor authentication for administrators (e.g. the free "Two Factor" plugin from wordpress.org, optional).
* `DISALLOW_FILE_EDIT` is on, so code can't be edited from the dashboard.
* Roles: the owner is **Administrateur**; your brother is **Gestionnaire de boutique** (shop_manager), who cannot install plugins or change users.
* Login throttling + XML-RPC off + no user enumeration (plugin), plus the host's WAF / fail2ban.
* Order data stays in WooCommerce (HPOS tables). **No card data is ever handled by the site.** Chargily uses hosted checkout.
* Backups: daily from the host + `scripts/backup.sh` (database, uploads, code), with a copy stored **off the server**.
* Updates: apply WordPress/WooCommerce updates weekly on staging first, run `scripts/test.sh`, then on production.
* If the site sits behind a CDN/proxy (e.g. Cloudflare), login throttling must read the real client IP. Configure the proxy's IP header at the server level.
