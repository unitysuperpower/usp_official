# USP Tech Solution React interface

The public website, customer workspace, admin screens, authentication, account settings, and support chat now render in React. Laravel retains routing, sessions, CSRF protection, validation, uploads, database access, authorization, and Fortify authentication.

## Run locally

```bash
npm install
npm run dev
```

Run `php artisan serve` in another terminal. Open the Laravel URL, not the Vite URL. For compiled assets, use `npm run build`.

## Where to edit

- `resources/js/react/app.jsx`: navigation, layouts, and page selection.
- `resources/js/react/public.jsx`: homepage, services, journal, contact, and customer dashboard.
- `resources/js/react/admin.jsx`: content management, requests, contact inbox, and analytics.
- `resources/js/react/account.jsx`: authentication, profile, appearance, security, and chat.
- `resources/js/react/ui.jsx`: shared forms, tables, cards, formatting, and API helper.
- `resources/css/react.css`: visual styles and responsive layouts.
- `resources/views/react.blade.php`: document shell and safely encoded page data.
- `app/Support/ReactPage.php`: page renderer and relation serialization.

Existing Blade pages are retained for reference; editing `home.blade.php`, `dashboard.blade.php`, or `welcome.blade.php` will not change the active React homepage or dashboard.

## Behavior and integration notes

- Page navigation uses Laravel document requests. React renders each page; this is not a client-side router or an Inertia application.
- Existing records populate services, journal entries, requests, and analytics. Empty states appear when there is no data. No demonstration records were added to the application database.
- Forms submit to existing Laravel endpoints and display flashed validation errors and success messages. Rich content is sanitized with DOMPurify.
- Chat uses authenticated JSON endpoints and refreshes every five seconds. Existing broadcasting support remains available in the backend.
- Email replies and password-reset emails still depend on the application's mail configuration. Browser tests used an isolated database and an in-memory mailer, so external delivery was not tested.
- Two-factor setup and recovery codes use Fortify endpoints. Visiting security settings never changes two-factor credentials.
- Server-generated SEO metadata is retained. Page body content is rendered client-side and requires JavaScript; server-side React rendering is not configured.
- Uploaded images continue using Laravel public storage. Existing upload, storage-link, mail, and broadcasting configuration applies.

## Validation

```bash
npm run build
php artisan test
```

The migration adds integration coverage in `tests/Feature/ReactMigrationTest.php`, including admin authorization, customer request isolation, author privacy, profile updates, contact submission, request status changes, chat relation serialization, and clearing optional editor fields. The existing PHP configuration produces a `PDO::MYSQL_ATTR_SSL_CA` deprecation warning on this machine.
