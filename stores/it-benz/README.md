# IT-BENZ: WooCommerce store (Algeria, cash on delivery)

Production-ready code for the `it-benz.dz` online store. It runs on WordPress + WooCommerce, with one custom plugin (business logic) and one custom theme (design). Built and tested on a local staging stack. **The live site and DNS have not been touched.**

| Deliverable | Where |
|---|---|
| 1. Existing-site audit | [`docs/01-audit.md`](docs/01-audit.md) |
| 2. Hosting & domain recommendation · 11. Costs | [`docs/02-hosting-and-costs.md`](docs/02-hosting-and-costs.md) |
| 3. Stack · 4. Store structure & URLs · 9. Security / performance | [`docs/03-architecture.md`](docs/03-architecture.md) |
| 5. Product page template · homepage | `wp-content/themes/itbenz/` (screenshots in `docs/screenshots/`) |
| 6. Cart + COD checkout (69 wilayas, communes, delivery prices) | `wp-content/plugins/itbenz-commerce/` |
| 7. Merchant dashboard · 8. CSV import workflow (FR) | [`docs/04-guide-gestionnaire.md`](docs/04-guide-gestionnaire.md), [`data/produits-modele.csv`](data/produits-modele.csv) |
| 9. Backups | [`scripts/backup.sh`](scripts/backup.sh) |
| 10. Deployment & DNS | [`docs/05-deployment-dns.md`](docs/05-deployment-dns.md) |
| Future online payment (Chargily Pay) | [`docs/06-chargily.md`](docs/06-chargily.md) |
| 12. Testing checklist | [`docs/07-testing-checklist.md`](docs/07-testing-checklist.md) |
| Design reference | [`docs/design-reference.md`](docs/design-reference.md). The light layout follows the reference screenshot the owner approved |

## Run staging locally

Requirements: Docker. Node 20+ is only needed for the tests.

```bash
cd stores/it-benz
cp .env.example .env            # then set real passwords
mkdir -p vendor-cache           # optional: put woocommerce.zip here to install offline
docker compose up -d
docker compose --profile tools run --rm cli bash /scripts/setup.sh --demo
# Store: http://localhost:8080   Admin: /wp-admin   E-mails caught by Mailpit: http://localhost:8025
bash scripts/test.sh            # Playwright end-to-end suite
```

`--demo` adds products, banners and **test** delivery prices, all marked **EXEMPLE**. Never use it on production; the script refuses to run with `--demo` when `WP_ENVIRONMENT_TYPE=production`.

## Layout

```
docker-compose.yml          staging stack (MariaDB, WordPress/PHP 8.3, Mailpit, WP-CLI)
scripts/setup.sh            idempotent provisioning (staging or production via SSH)
scripts/demo-content.php    staging-only placeholders
scripts/backup.sh           DB + uploads + code archive with retention
scripts/test.sh             resets test state and runs Playwright
data/produits-modele.csv    product import template (verified with WooCommerce's importer)
wp-content/plugins/itbenz-commerce/   Algeria/COD business logic
wp-content/themes/itbenz/             presentation only
tests/                      Playwright E2E + Lighthouse
```

## What the owner still has to provide

1. Access to the domain's DNS panel and the current host, so the current site can be backed up first (see `docs/01-audit.md`).
2. Choice of hosting plan, and payment.
3. Real business data: products, prices and stock, photos, delivery prices per wilaya, warranty/returns policy, phone/WhatsApp, address.
4. Banner and collection photos (upload them in **Bannières** and **Produits → Catégories**).
5. Approval before the production DNS switch.
