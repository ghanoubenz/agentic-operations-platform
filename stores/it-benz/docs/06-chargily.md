# 6. Online payment later: Chargily Pay (CIB / EDAHABIA)

**Status:** not installed. Cash on delivery is the only active payment method. Do not activate live payments until the steps below are complete.

## What was verified (Oct 2026)

| Topic | Finding | Source |
|---|---|---|
| Plugin | **"Chargily Pay"** for WooCommerce, v2.5.x, GPLv2. Tested up to WordPress 6.9.x / WooCommerce 10.6.x per its README. **No wordpress.org listing was found**: it is distributed from GitHub releases | github.com/Chargily/chargily-pay-wordpress (code read directly) |
| Old plugin | `chargily-epay-woocommerce` (ePay V1) is a different, outdated plugin. Don't use it | GitHub |
| Methods | EDAHABIA (Algérie Poste) and CIB (SATIM). Visa/Mastercard announced as "coming soon" | plugin description, chargily.com |
| Checkout type | **Hosted checkout**: the customer pays on Chargily's page, so card data never touches the site | plugin code (`checkouts` API) |
| API | `https://pay.chargily.net/test/api/v2/` (test) and `https://pay.chargily.net/api/v2/` (live). A "test mode" switch selects test or live keys | plugin code |
| Webhook security | HTTP header `signature` = `HMAC-SHA256(raw body, API secret key)`, compared with `hash_equals` | plugin code (`API-v2.php`, `API-v2_webhook.php`) |
| Fees | Sources conflict. The pricing page lists plans at **1.5%** (min 15 / max 1 500 DA), **3%** (30 / 3 000) and **4.5%** (45 / 4 500), with no subscription or activation fee. The developer docs mention 0% the first month and let the merchant absorb the fee or pass it on | chargily.com pricing page, dev.chargily.com (search snippets) |
| Merchant onboarding documents | **Not verified.** Ask Chargily directly (signup: pay.chargily.com/dashboard/register; support via their WhatsApp link) | n/a |

## Why the store is already ready for it

* The checkout is WooCommerce's standard classic checkout, which the plugin targets. COD stays available alongside it.
* Order statuses: paid online orders go to WooCommerce "processing" (= **Nouvelle**) through the plugin, then follow the same workflow.
* There is no custom payment code to maintain. Using the vendor plugin keeps signature checks and API changes their responsibility.

## Activation steps (when the business is ready)

1. The owner creates the Chargily merchant account and completes onboarding.
2. On **staging**: download the latest release ZIP from the official GitHub repository, then go to *Extensions → Ajouter → Téléverser*. **Read the release notes and check the repository is the official `Chargily` organisation.**
3. In WooCommerce → Réglages → Paiements → Chargily: enable **test mode** and enter the **test** API keys. The owner pastes them directly into the dashboard. Never send keys by chat or e-mail.
4. Set the webhook URL shown by the plugin in the Chargily dashboard.
5. Test with Chargily's test cards and accounts:
   * a successful payment,
   * a failed payment,
   * an abandoned payment,
   * a webhook replay. A tampered signature must be rejected.

   In each case the order status and the stock must be correct.
6. Run `scripts/test.sh` again (the COD flow must still pass).
7. Switch to live keys on production and run one real low-value payment, then refund it.
8. Rotate the keys if they were ever exposed. Keep them only in the WordPress database (plugin settings) and the Chargily dashboard.
