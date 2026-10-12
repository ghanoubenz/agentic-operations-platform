# 2. Hosting decision and operating costs

> **Price note.** The hosting providers' own pricing pages could not be opened from the build environment. The figures below come from search results and third-party reviews dated 2026, plus Hetzner's official price-adjustment notice. **Check the price at checkout before paying.** Look at the *renewal* price, not only the introductory one.

## Requirements that drive the choice

* WordPress + WooCommerce on PHP 8.1+ with MySQL/MariaDB, free SSL, daily backups, SSH + WP-CLI. WP-CLI matters because `scripts/setup.sh` automates the configuration.
* Visitors are in Algeria. The closest mainstream data centres are in **France** (Paris/Marseille) and southern Europe.
* The person running the shop day-to-day is not technical. Server maintenance should be as close to zero as possible.
* Later on, more client stores will be hosted.

## Option A: managed WordPress hosting (recommended for this first store)

Example: Hostinger "Business" WordPress plan.

| Item | Finding | Source quality |
|---|---|---|
| Intro price | about US$3.59–3.99/month on a 48-month prepaid term | third-party reviews 2026 |
| Renewal price | about US$16.99/month (one source says $18.99) | third-party reviews 2026 |
| Data centres | Europe: France, Germany, Lithuania, Netherlands, UK | Hostinger support article (search snippet) |
| WooCommerce | supported; LiteSpeed web server + free LiteSpeed Cache plugin | not verified here: confirm on the plan page |
| SSL | free, automatic | not verified here: confirm on the plan page |
| Backups | daily automatic on Business-tier plans | not verified here: confirm on the plan page |
| Staging | built-in staging tool on Business-tier plans | not verified here: confirm on the plan page |
| SSH / WP-CLI | available on Business-tier plans | not verified here: confirm on the plan page |
| Email | mailboxes are usually included; use them for order e-mails (SMTP) | not verified here: confirm on the plan page |

Pros: no server administration, backups and SSL included, a French-speaking panel, staging included.
Cons: high renewal price and limited control. Several client sites on one account is possible, but they share resources.

## Option B: self-managed VPS

Example: Hetzner Cloud CX23 (2 vCPU, 4 GB RAM, 40 GB).

| Item | Finding | Source quality |
|---|---|---|
| Price | **€5.49/month** excluding VAT and the IPv4 charge, after the 1 April and 15 June 2026 adjustments (was €3.99, and €2.99 before April) | Hetzner official notice (docs.hetzner.com, price-adjustment) |
| Availability | one tracker reported CX23 "not available" to order in Sept 2026; check | third-party |
| Locations | Germany (Falkenstein, Nuremberg) and Finland (Helsinki). **No France location.** | Hetzner |
| Backups | +20% of the server price for automatic snapshots, or use `scripts/backup.sh` + off-site copies | Hetzner (confirm) |
| Everything else | You install and maintain the OS, web server, PHP, database, firewall, TLS, updates and monitoring yourself | n/a |

Pros: cheap, full control, and one server can host many small client stores later.
Cons: someone must patch and monitor the server, and a mistake can take the shop offline. Latency from Algeria to Germany or Finland is higher than to France.

## Recommendation

**Start with Option A** (a managed WordPress plan in a **France** data centre, on a 12–48-month term the business is happy to renew).

* It is the simplest reliable setup for a non-technical operator. Backups, SSL and the web server are handled for you.
* The store code is plain WordPress (one theme + one plugin + WooCommerce). Moving it to a VPS later takes about an hour with `scripts/backup.sh` and `scripts/setup.sh`.
* Move to Option B (or a VPS in France) once there are 3–5 client stores. At that point one well-managed server becomes cheaper than several managed plans.

## Costs

### One-time

| Item | Cost |
|---|---|
| WordPress, WooCommerce, theme, plugin, fonts (open source) | 0 |
| Domain | 0: keep it-benz.dz (no new domain needed) |
| Product photography | only if the business wants its own photos (optional) |
| Chargily Pay onboarding | no activation fee advertised (check, see `06-chargily.md`) |

### Recurring

| Item | Cost | Notes |
|---|---|---|
| Hosting (Option A) | ~US$3.6–4/mo intro, then ~US$17/mo on renewal | check at checkout |
| Hosting (Option B, alternative) | €5.49/mo + IPv4 + VAT (+20% if Hetzner backups) | official, June 2026 |
| .dz domain renewal | as billed by your registrar | unknown, depends on the registrar |
| Paid plugins | **0**: none are required | |
| Delivery company | per parcel, under your contract with the carrier (Yalidine, ZR Express, etc.) | enter the real prices in "Livraison IT-BENZ" |
| Chargily Pay (later) | per-transaction fee; the pricing page lists plans at 1.5% / 3% / 4.5% with min/max caps | check before activating |
| E-mail (SMTP) | 0 if the host's mailbox is used | |
