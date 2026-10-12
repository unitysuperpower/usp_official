# usp.com.pk deployment — 30 September 2026

The website is deployed at https://usp.com.pk. The separate ERP directory and ERP database were not modified.

## Installed layout

- Application root: /home/u558441138/apps/usp_official
- Active release: releases/20261001-044928-dashboard, selected by the current symlink
- Private environment: shared/.env (mode 600)
- Persistent files: shared/storage
- Website document root: /home/u558441138/domains/usp.com.pk/public_html
- Existing separate project: public_html/erp — do not modify, delete, replace, or include in website deployment commands
- Website database: u558441138_usp_db
- Backup: backups/20260930-045450/website-before.sql, outside the web root in a private directory
- Source: repository commit 362a42fceb2b1e5317d86b6e2ded595349b9a1b4 plus the tested local education changes and compiled assets. Those local changes have not been pushed to GitHub.

Only these website public entries were created: index.php, .htaccess, build, storage, favicon.png, apple-touch-icon.png, and chat-worker.js. The entry point loads the private application release; assets and public storage use symlinks. Existing unrelated files and the ERP directory were retained. Root rewrite rules leave the ERP directory to its existing configuration.

The partial pre-existing education migration lacked its lesson-progress unique index and migration ledger entry. The index was added, the completed migration recorded, and the remaining gateway/learning migrations applied. No tables were deleted and no demo data was seeded.

## Verified

- Production dependency installation and platform checks passed using PHP 8.4 CLI; the web server runs PHP 8.5.
- Application configuration, routes, and views are cached; debug output is disabled.
- Homepage, courses, login, registration, health, robots, and sitemap endpoints respond.
- A real browser passed the hosting browser-verification page and rendered the site and assets. Mobile course layout passed overflow and JavaScript checks.
- Public storage was checked using a temporary non-sensitive file and the file removed afterward.
- Anonymous admin requests redirect to login. Private environment and existing root key filenames return 403.
- ERP at https://erp.usp.com.pk still returns HTTP 200. Its public entry file hash remains eba77cba39695b6bd091fe5211d481f7ebb2ce2d8d26230b5a609465d0a4aff9.

The hosting browser-verification layer can return an initial 403 to automated clients. Direct origin tests and normal browser tests succeeded; no hosting security settings were changed.

## Remaining owner configuration

- Administrator admin@usp.com.pk was provisioned on 30 September 2026. The original credential file remains in shared/admin-initial-credentials.txt (mode 600), outside the web root. On 1 October, verification found that this initial password no longer matches the account; use the current administrator password. The deployment did not reset it. The command below can provision additional administrators privately from the current directory.

    /opt/alt/php84/usr/bin/php artisan admin:create

- Maintain actual course and service content through the administrator interface. No demo content was seeded into production.
- Manual receiving instructions and JazzCash merchant credentials remain unset. Configure them at /admin/education/settings. The HTTPS callback is https://usp.com.pk/education/jazzcash/return; merchant sandbox/live transaction delivery has not been verified.
- Email currently uses the log driver. Configure a mail provider before relying on password-reset emails or outgoing replies.
- Broadcasting uses the log driver; live WebSocket delivery still needs a provider. Jobs use the synchronous driver, so no queue-worker cron was installed or changed.

## Future updates and rollback

Prepare a new release under apps/usp_official/releases, install dependencies from the lock file, upload built assets, and link the same private environment and persistent storage. Back up only the website database before applying additive migrations. Verify the release before switching current.

Do not delete public_html, replace the document root, recursively synchronize with deletion into public_html, or run website migrations against the ERP database.

The backup directory records the exact newly-created website entries in deployment.json. Reversing this initial website deployment should affect only those entries and the website database if explicitly required. Preserve ERP and all unrelated files. For subsequent releases, switch current back only after confirming database compatibility.

## Dashboard update — 1 October 2026

- Added current work queues, lead pipeline, project deadlines, recent-request pagination, and shortcuts to the administrator dashboard.
- Added page-size controls (10, 20, 50, 100), result ranges, preserved pagination filters, and explicit table actions across administrative lists.
- Added accessible category create/edit/delete dialogs, lead follow-up dialogs, and contact status/note dialogs. Validation errors reopen the relevant form. Categories containing content cannot be deleted.
- Verification: 133 tests passed (750 assertions), production build passed, and isolated browser CRUD, validation, keyboard, pagination, desktop/mobile, and dark-mode checks passed.
- Deployed as 20261001-044928-dashboard; the prior release is retained. Website database backup and previous release path are in backups/20261001-044928-dashboard. No new database migration was required. ERP was not modified.

Live release checks: the website and ERP return HTTP 200; authenticated server-side checks pass for dashboard, categories, contact inbox, leads, and education courses. The public login form loads, but a live browser sign-in could not be completed with the obsolete initial password. Current credentials were preserved.

Course presentation release: 20261001-103753-course-visuals (1 October 2026).
Added dashboard-managed cover uploads, four fallback graphic themes, and a featured course carousel. Applied only the additive course presentation migration after verifying the website database and taking a backup. Public and authenticated admin routes passed before the atomic release switch. ERP entry integrity was unchanged and ERP returned HTTP 200. The sole existing published course was featured to make the carousel visible; additional published courses can be featured from the editor.

Validation: 136 tests, 770 assertions; production build; isolated browser upload/navigation/mobile checks; production route and asset checks.

Course page polish release: 20261002-043842-course-polish (2 October 2026).
Refined catalog introduction, search controls, course cards, and course-detail hero with responsive learning facts. Built production assets and checked catalog/detail layouts at 1440px and 390px with no overflow or JavaScript errors. Website preflight public/admin routes passed before the atomic release switch. No migrations were run for this presentation update; previous release and website database backup retained. ERP entry-file integrity unchanged.

Education experience release: 20261003-070638-education-experience (3 October 2026).
Added subject/price discovery, next batch dates, course preview/instructor/FAQ fields, launch checklist, lesson workspace and private notes, student deadlines, batch announcements, approved-payment receipts, and education operations overview. Applied only 2026_10_03_120000_add_education_experience.php after verifying the website database and retaining a backup. Public/admin preflight routes passed before the release switch. ERP entry-file hash unchanged. Validation: 142 tests / 804 assertions, production build, isolated admin/student browser workflows, responsive lesson workspace, and live catalog/filter/detail checks. Live JazzCash merchant configuration remains an external requirement.

Payment cards release: 20261003-110953-payment-cards (3 October 2026).
Added selectable manual payment cards, total-due summary, transfer steps, QR display/full-size link, and dashboard QR uploads per method. Original QR image pixels are preserved; replacement/removal cleanup follows successful settings save. No migration needed; QR paths use existing manual_methods JSON. No live receiving details or gateway credentials were changed. Validation: 146 tests / 826 assertions; isolated browser checks for admin upload, student image delivery, method switching, and mobile layout. Website backup retained; production settings/payment routes passed before switching; ERP integrity unchanged.

LMS journey release: 20261010-052656-lms-journey (10 October 2026).
Published dedicated enrollment review with login return, price reconfirmation, workspace journey tracker, next actions, and completion checklist. Also includes the previously pending student dashboard filters/spotlight and 7/30-day admin trends. No migrations or account/payment configuration changes. Previous release retained under releases; rollback path and ERP entry hash saved in backups/20261010-052656-lms-journey. Production route checks passed before atomic switch. ERP unchanged. Validation: 151 tests / 862 assertions plus isolated enrollment browser workflow and mobile checks.

Learning calendar release: 20261010-115113-learning-calendar (10 October 2026).
Published private student event view and .ics snapshot download for active enrollments. Production calendar/download routes passed before the atomic switch. Previous release retained; no database changes; ERP entry-file integrity unchanged. Validation: 153 tests / 933 assertions and desktop/mobile browser checks covering filters and authenticated download.
