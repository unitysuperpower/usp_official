# USP Form Builder distributions and licensing

The Laravel website is the source of truth for orders, subscriptions, license expiry, activations and downloads. The immutable product identifier is `usp-form-builder`.

## Build and publish

Run `python3 scripts/build-plugin.py` from the Laravel repository. PHP, Python, Node and the installed frontend development dependencies are required. The build lints PHP and JavaScript and checks that premium handlers are absent from Free.

Output under `dist/plugins/`:

- `usp-form-builder-1.1.1-free.zip`: Free implementation, standard fields, shared builder UI, security and license/upgrade client. Premium field renderers and submission handlers, address/country datasets, and Pro frontend engines are excluded. The shared admin UI can still show upgrade prompts and recognize existing Pro field types.
- `usp-form-builder-1.1.1-pro.zip`: Full plugin with Pro implementations and server-verified licensing. Keep this package private.
- `manifest.json`: SHA-256 checksums and edition/version metadata.

Both ZIPs contain `usp-form-builder/usp-form-builder.php`. The same folder is intentional: the Pro upgrade replaces Free in place, preserves the WordPress database and settings, and avoids duplicate plugin classes. Do not install them under different folder names.

In `/admin/store/products`, upload version `1.1.1` with the matching **Free** or **Pro** edition. The uploader checks folder structure, PHP version header, edition header, symbolic links, unsafe paths, duplicate entries, and archive size. It now reports the specific reason for rejection. The original `usp-form-builder-1.0.0.zip` is internally version `1.0.7` and contains premium implementations; do not publish it as the new Free distribution.

## Customer flow

1. Install the Free ZIP in WordPress.
2. Register on usp.com.pk, choose a plan, and pay. Verified payment issues a license in **My products & licenses**. Transfers await admin review; configured JazzCash payments require a valid signed gateway response.
3. In WordPress → USP Form Builder → Pro & Security Hub, paste the key and activate it.
4. Click **Install Pro files**. The plugin requests a fresh server authorization, downloads only from the fixed HTTPS store host, verifies the SHA-256 checksum, and uses the WordPress plugin upgrader. It restores the active state after a successful upgrade.
5. If the host requires FTP credentials or the site uses multisite, download the Pro ZIP from the authenticated account and use WordPress's normal upload/replace flow. Every licensed multisite installation must activate separately.
6. After renewal, choose **Verify** in the plugin. Use **Install latest Pro release** to obtain a newer private release. There is no background automatic updater or automatic recurring charge in this release.

Use WordPress 6.3+ for the core upgrader's temporary backup/rollback support. The updater refuses older releases, wrong products/editions, unexpected hosts and unverified package downloads. Neither license keys nor activation tokens are included in package URLs. Signed URLs expire after 30 minutes and recheck activation and entitlement when used.

## Expiry and limits

Pro field forms are unavailable once the subscription expires; ordinary Free forms continue working. No form data is deleted. A same-site, unexpired cached license can tolerate a temporary network outage for up to three days from the last successful check. Expiry and site identity are checked even during that grace period. Remote revocation is normally observed at the next daily check, or after the bounded grace period during an outage. Package downloads always require a live server check and never use offline grace.

One-site, three-site and unlimited plans enforce their limits on the server. HTTP-to-HTTPS moves retain a seat; different subdomains and subdirectory installations consume separate seats. Customers can release sites from their account. A lost activation token requires releasing the old activation in the account before reactivating the same site.

Paid PHP source cannot be made uncrackable on a customer-controlled server. This implementation keeps Pro code out of the Free distribution and protects the vendor-controlled downloads, updates, subscription records and activation limits. It does not promise DRM that a site owner cannot modify. GPL notices and third-party dataset licenses are retained.

## Verification

```
php artisan test --compact
python3 scripts/build-plugin.py
php scripts/test-plugin-packages.php free
php scripts/test-plugin-packages.php pro
php integrations/usp-form-builder/tests/store-license-client.php
USP_TEST_EDITION=free php integrations/usp-form-builder/tests/store-license-client.php
php integrations/usp-form-builder/tests/installer-policy.php
```

The approved prices are USD $5 / $10 / $150 per month for one / three / unlimited websites, and USD $48 / $96 / $1,440 for annual payment. Live checkout also needs the owner's USD-to-PKR conversion rate and receiving accounts or merchant configuration; do not invent these values.
