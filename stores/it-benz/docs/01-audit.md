# 1. Audit of the existing site (it-benz.dz)

**Status: not done. The site could not be reached from the build environment.**

| Check | Result |
|---|---|
| Direct HTTPS request to `https://it-benz.dz/` | Refused by the build environment's network policy. This says nothing about the live site. |
| Web search for "it-benz.dz", "IT-BENZ Algérie" | No indexed pages found. The site may not be indexed, or may be new. |
| CMS, hosting, DNS, email provider | **Unknown.** None of this could be checked. |
| Products, prices, policies, contact details | **Unknown.** Nothing was copied or invented. Every such field in the new store starts empty. |

## What the owner needs to provide (10 minutes)

Run these from any computer and paste the output, or send screenshots:

```bash
# Who hosts the DNS and the website?
dig +short NS it-benz.dz
dig +short A it-benz.dz
dig +short A www.it-benz.dz
# Email: these records must be preserved during migration
dig +short MX it-benz.dz
dig +short TXT it-benz.dz          # SPF / domain verification
dig +short TXT _dmarc.it-benz.dz
```

Also needed:

1. The name of the company the domain was bought from (the .dz registrar or reseller), and access to its customer panel.
2. Access to the current hosting panel (cPanel, hPanel, Plesk…), if the site is hosted somewhere.
3. Whether it-benz.dz currently has mailboxes (e.g. `contact@it-benz.dz`) and who provides them.
4. The real business information to publish: phone, WhatsApp, address, opening hours, delivery zones and real delivery prices, warranty and returns policy.

## Decision: prepare a replacement, then switch the domain

The original site's technology could not be checked. So the store was built as a **standalone WordPress + WooCommerce installation**. This is the safest path whatever the current site turns out to be:

* **If the current site is WordPress:** a full backup is taken first. Its pages can be exported (Outils → Exporter) and imported into the new store. The new theme and plugin can also be installed on top of the existing install after testing on a copy. A "WooCommerce on the existing site" upgrade is possible, but only after a staging copy has been tested.
* **If it is static or custom:** nothing needs converting. Keep a backup archive, build on staging, then point the domain at the new hosting.

**Nothing on the live site or the DNS is changed** until these three things are done:

1. A complete backup of the current site exists (files + database, or a full static copy).
2. The staging store has passed the checklist in `07-testing-checklist.md`.
3. The owner has explicitly approved the production switch.
