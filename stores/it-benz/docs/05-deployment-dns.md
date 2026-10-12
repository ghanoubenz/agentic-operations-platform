# 5. Deployment, domain and DNS

The goal is to run the store directly on `https://it-benz.dz` without buying a new domain and **without breaking existing e-mail**.

## 0. Before touching anything (owner)

1. Collect the information listed in `01-audit.md`: current DNS host, current web host, current MX/SPF records.
2. **Back up the current website** from its hosting panel (files + database), or save a full copy if it is static. Keep the backup off the server.
3. Write down **every DNS record** in the current zone. Take a screenshot of the DNS panel and export the zone if possible. MX, SPF (TXT), DKIM, DMARC, CNAMEs and any verification TXT records must survive the switch.

## 1. Create the hosting and a staging site

On the chosen host (Option A, France data centre):

1. Create the WordPress site on a **temporary domain or staging subdomain** provided by the host, or on `staging.it-benz.dz` (an `A`/`CNAME` record only for `staging`, which leaves the main site untouched).
2. Enable SSH access in the hosting panel. Use an SSH key; don't paste passwords anywhere.
3. Upload the code (SFTP, or `git clone` this repository on the server):
   ```bash
   # from the WordPress root on the server
   cp -r <repo>/stores/it-benz/wp-content/themes/itbenz        wp-content/themes/
   cp -r <repo>/stores/it-benz/wp-content/plugins/itbenz-commerce wp-content/plugins/
   ```
4. Add to `wp-config.php` (above "That's all, stop editing"):
   ```php
   define( 'DISALLOW_FILE_EDIT', true );
   define( 'WP_ENVIRONMENT_TYPE', 'staging' );   // 'production' after go-live
   // Order e-mails through your mailbox provider (values from the host's e-mail panel):
   define( 'ITBENZ_SMTP_HOST', 'smtp.example.com' );
   define( 'ITBENZ_SMTP_PORT', 465 );
   define( 'ITBENZ_SMTP_SECURE', 'ssl' );
   define( 'ITBENZ_SMTP_USER', 'boutique@it-benz.dz' );
   define( 'ITBENZ_SMTP_PASS', '…' );           // typed on the server only, never shared in chat
   ```
5. Run the provisioning script. It is idempotent and safe to re-run:
   ```bash
   SITE_URL=https://<staging-address> ADMIN_EMAIL=<owner e-mail> WP_ENVIRONMENT_TYPE=staging \
     WC_ZIP=none bash <repo>/stores/it-benz/scripts/setup.sh          # add --demo for test products
   wp language core install fr_FR --activate && wp language plugin install woocommerce fr_FR
   ```
6. Create your brother's account with the **Gestionnaire de boutique** role (`Comptes → Ajouter`, or `wp user create <login> <email> --role=shop_manager`). WordPress sends him a link to set his own password.
7. On Hostinger: install **LiteSpeed Cache** (free) and check that cart/checkout/my-account are excluded (they are by default for WooCommerce). Enable image optimization → WebP.
8. Run through `07-testing-checklist.md` on staging. From a computer with the repo you can also run `BASE_URL=https://<staging> npx playwright test checkout.spec.mjs` (needs demo content).

## 2. Go-live (only after the owner approves)

1. **Lower the TTL** of the `@` and `www` records to 300 s, **24 h before** the switch.
2. Copy the approved staging site to production: use the host's "push staging to live" tool, or migrate with `scripts/backup.sh` and an import.
3. Set `WP_ENVIRONMENT_TYPE` to `production`. Then run `wp option update blog_public 1` and `wp search-replace 'https://<staging>' 'https://it-benz.dz' --skip-columns=guid`.
4. **DNS change (minimal and safe).** In the panel of whoever hosts the it-benz.dz DNS today, change **only**:

   | Name | Type | Value |
   |---|---|---|
   | `@` | A | new host IPv4 (from the hosting panel) |
   | `@` | AAAA | new host IPv6, if provided (otherwise delete the old AAAA) |
   | `www` | CNAME | `it-benz.dz` (or A = same IPv4) |

   **Do not modify MX, SPF/DKIM/DMARC TXT, or any other record.** Mail keeps working with its current provider.
   *Alternative:* move the nameservers to the new host. Only do this if you **re-create every existing record** there first. It is riskier for e-mail, so prefer the A-record method.
5. **SSL:** once DNS resolves to the new host (check with `dig +short A it-benz.dz`), issue the free Let's Encrypt certificate from the hosting panel. Force HTTPS, then enable HSTS.
6. **E-mail sending:** if order e-mails are sent through a new provider, add that provider to the SPF record. Example: `v=spf1 include:<existing> include:<new-provider> ~all`. Keep a single SPF record.
7. Place one real test order with COD on production, check the merchant e-mail, then cancel it.
8. Submit `https://it-benz.dz/wp-sitemap.xml` in Google Search Console.

## 3. Rollback

If something goes wrong after the switch, change the `@`/`www` A records back to the old IP. Thanks to the 300 s TTL, the old site returns within minutes. Nothing on the old hosting was deleted.

## About `.dz` domains

.dz domains are managed by NIC.DZ (CERIST) through accredited registrars/resellers. The current naming charter, the delegation rules and the registrar list **could not be checked from the build environment**. DNS records (A, MX…) are normally edited in the panel of whoever provides the DNS for the domain. If you only have a registrar contact and no panel, ask them to:

(a) give you DNS panel access, or
(b) update the `@` and `www` A records to the new IP.

Keep every other record unchanged.

## Credentials needed (never paste them into chat)

| Step | Who | What |
|---|---|---|
| Buy hosting | owner | payment in the provider's checkout |
| SSH / SFTP to the server | owner → developer | an SSH public key added in the hosting panel |
| DNS change | owner | login to the DNS/registrar panel |
| SMTP password | owner | typed directly into `wp-config.php` on the server |
| Chargily API keys | owner | entered in WooCommerce → Paiements → Chargily (see `06-chargily.md`) |
