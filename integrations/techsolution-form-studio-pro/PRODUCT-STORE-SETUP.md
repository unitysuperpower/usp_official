# TechSolution Form Studio — distribution and licensing

Two separate plugins are built from this repository:

| Plugin | Slug / folder | Where it is distributed | Licensing |
| --- | --- | --- | --- |
| TechSolution Form Studio | `techsolution-form-studio` | WordPress.org directory | None. Every feature works without a key. |
| TechSolution Form Studio Pro | `techsolution-form-studio-pro` | usp.com.pk store only | License key enables automatic updates and support. Fields work without it. |

The Free plugin never downloads, installs or unlocks Pro code (WordPress.org guidelines 5 and 8). It only links to the Pro product page. Pro is a normal add-on that requires the Free plugin (`Requires Plugins: techsolution-form-studio`) and registers its field types through the Free plugin's hooks.

## Build

```
python3 scripts/build-plugin.py            # both plugins
python3 scripts/build-plugin.py --only techsolution-form-studio
```

The build runs every test in each plugin's `tests/` folder, lints PHP and JavaScript, checks that the Free package contains no licensing or custom-code features, and writes to `dist/plugins/`:

- `techsolution-form-studio-<version>.zip` — upload to WordPress.org ("Add your plugin" page, then SVN after approval).
- `techsolution-form-studio-pro-<version>.zip` — upload to the store.
- `manifest.json` — SHA-256 checksums.

## Store setup (one time)

1. In `/admin/store/products`, create a product with slug **`techsolution-form-studio-pro`**. The slug must match the ZIP's folder name and the `TSFS_PRO_PRODUCT_SLUG` constant.
2. Upload `techsolution-form-studio-pro-<version>.zip` as a **Pro** edition release and publish it.
3. Licenses issued for this product activate in WordPress under **Form Studio → Pro License**.

Licenses for the old `usp-form-builder` product are for a different product slug. Issue those customers a `techsolution-form-studio-pro` license (or extend the store to map one to the other).

## How Pro updates work

- The Pro plugin declares `Update URI: https://usp.com.pk/products/techsolution-form-studio-pro`, so WordPress never looks for it on WordPress.org.
- With an active license, the plugin asks the store's `update` action for the latest Pro release (cached for six hours) and shows it on the Plugins and Updates screens.
- When the site owner clicks Update, the plugin requests a fresh signed download link (they expire after 30 minutes), downloads it, verifies its SHA-256 and lets the WordPress upgrader install it.
- Without a license the plugin keeps working; it just does not receive updates.

## Migrating sites from USP Form Builder

On activation, TechSolution Form Studio copies forms, entries and anti-spam settings from the `usp_forms`, `usp_entries` tables and the `usp_security_settings` option, when its own tables are empty. Uploaded files stay where they are. The Pro add-on also keeps the old `[usp_form id="…"]` shortcode working. The old plugin can be deactivated and deleted after checking the migrated forms.

## Verification

```
python3 scripts/build-plugin.py
php integrations/techsolution-form-studio/tests/compliance.php
php integrations/techsolution-form-studio-pro/tests/license-updater.php
php integrations/techsolution-form-studio-pro/tests/calculator.php
```
