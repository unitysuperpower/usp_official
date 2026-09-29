# USP Education

The education module is separate from service leads and project delivery. It uses the existing account system: ordinary users are students; administrators manage courses and enrollment.

## Pages

- `/courses`: public searchable course catalog, filtered by format and level.
- `/courses/{slug}`: course details, curriculum outline, fees, open batches, and application forms.
- `/learn`: the signed-in student's applications and courses.
- `/learn/{id}`: application status, payments, scheduled classroom, lessons, and progress.
- `/admin/education/courses`: create/edit courses, publish/unpublish, manage batches and curriculum.
- `/admin/education/enrollments`: applications, students, acceptance, and certification.
- `/admin/education/payments`: manual verification and JazzCash reconciliation.

## Start a course

1. Create a course in **Education → Courses & curriculum**. Choose online, in-person, or hybrid delivery. Enter the fee in PKR; zero means free.
2. Add a batch with dates, an application deadline, seat capacity, and a schedule including the time zone. In-person and hybrid batches require a venue. Meeting links are visible only to enrolled students.
3. Add lessons and publish them when ready. Lesson text and external video/resource links are accessible through the authenticated learning workspace. External hosting services must enforce their own sharing permissions.
4. Publish the course and open its batch for applications. Draft courses stay out of the public catalog and sitemap. Unpublishing does not revoke existing students' access.
5. Review each application. Acceptance reserves a seat; capacity checks run inside a database transaction. Free-course acceptance activates enrollment immediately. Paid-course acceptance requests payment.
6. Verify payment against the actual receiving account or process a signed JazzCash response. Only then does the learning workspace unlock.
7. Students mark lessons complete. An administrator confirms attendance/assessment requirements before issuing a certificate. Online/hybrid certification also requires all published lessons to be marked complete. The certificate preserves the student's name and course title at issuance and can be printed or saved as PDF.

Each application captures its fee and currency. Later course price edits do not change existing applications. A student has one application per batch. Withdrawals are available only before a payment is pending or approved. Account deletion with education records directs the user to support instead of deleting payment/enrollment history.

## Database and assets

Run the two additive migrations and build the frontend:

```bash
php artisan migrate --path=database/migrations/2026_09_28_120000_create_education_tables.php
php artisan migrate --path=database/migrations/2026_09_28_130000_add_education_gateway_fields.php
npm run build
```

No demo courses or payment credentials are automatically created. Add your own course content in the admin area. The active frontend is `resources/js/react/education.jsx`; the legacy `welcome.blade.php` does not render these pages.

## Manual payments

Configure verified receiving instructions in `.env`. Empty values disable the corresponding method:

```dotenv
EDUCATION_BANK_INSTRUCTIONS="Bank name, account title, IBAN, and payment instructions"
EDUCATION_JAZZCASH_INSTRUCTIONS="Verified receiving account and payment instructions"
EDUCATION_EASYPAISA_INSTRUCTIONS="Verified receiving account and payment instructions"
```

These are public instructions shown to accepted applicants, not API secrets. The student transfers the full fee, then uploads a PNG, JPG, or PDF receipt (maximum 5 MB). Proof files stay on the private storage disk and require student ownership or administrator access to download. A rejected proof can be corrected under the same reference; the prior review is retained. Transaction references cannot be reused across different manual payments of the same method.

## JazzCash online checkout

Implemented using JazzCash's hosted HTTP POST mobile-wallet flow. Wallet credentials are entered on JazzCash's page, not in this application. Configure the merchant-provided settings privately in `.env`:

```dotenv
JAZZCASH_ENABLED=false
JAZZCASH_ENVIRONMENT=sandbox
JAZZCASH_MERCHANT_ID=
JAZZCASH_PASSWORD=
JAZZCASH_INTEGRITY_SALT=
JAZZCASH_CHECKOUT_URL=
JAZZCASH_RETURN_URL=https://your-public-domain/education/jazzcash/return
```

Use the checkout URL supplied by your JazzCash merchant dashboard. Sandbox URLs must be HTTPS on `sandbox.jazzcash.com.pk`; live URLs must be HTTPS on `payments.jazzcash.com.pk`. The return URL must be public HTTPS with the exact path `/education/jazzcash/return`, registered in your merchant account. `127.0.0.1` is not a public callback address.

After entering the matching credentials and URLs, clear/rebuild Laravel configuration as appropriate for the environment and set `JAZZCASH_ENABLED=true`. Test with your sandbox merchant account first. Production mode refuses sandbox checkout and sandbox callbacks. Live operation requires live merchant approval, matching credentials, and `JAZZCASH_ENVIRONMENT=live`.

The payment amount is taken from the stored enrollment, not browser input. Requests are signed with HMAC-SHA256. Callbacks verify the signature, merchant, reference, amount, currency, bill reference, and environment. Duplicate callbacks cannot grant access twice or reverse an approved payment. The hosted form contains the merchant password required by JazzCash's HTTP POST protocol; the signing salt stays on the server and must never be exposed.

A pending online attempt can be resumed for 30 minutes using the same transaction reference. A timeout or non-success response is **not** automatically treated as a failed charge. Administrators reconcile the final result in their merchant portal and record a note; rejection is allowed only after checkout expiry. A late success following another approved payment is flagged `review_required` for investigation. After resolving it in the merchant portal, the administrator can record the outcome and merchant reference in the app; repeated callbacks cannot reopen a resolved entry. Refunds/chargebacks and duplicate-charge resolution are handled in the merchant portal; this module does not send refund API calls or implement voucher IPN/recurring/card payments.

The return endpoint alone is CSRF-exempt because JazzCash posts from outside the site; its signed payload is mandatory. It does not rely on a student's browser session and returns a generic result page without exposing student details.

Official integration references:

- [JazzCash HTTP POST integration](https://sandbox.jazzcash.com.pk/SandboxDocumentation/v4.2/index.html)
- [JazzCash hashing scheme](https://sandbox.jazzcash.com.pk/SandboxDocumentation/v4.2/features.html)
- [JazzCash transaction parameters](https://sandbox.jazzcash.com.pk/SandboxDocumentation/ApiReferences.html)

## Verification

`tests/Feature/EducationTest.php` covers public/draft visibility, discovery, admin authoring, application idempotency, capacity, fee snapshots, student authorization, private receipts, proof correction, activation, progress, certificates, gateway configuration, signed callbacks, replay protection, and reconciliation.

The local browser check used a separate SQLite database with non-delivering mail/broadcast settings. It completed the admin-to-student manual payment and learning journey at desktop, tablet, and mobile sizes. JazzCash responses were tested locally with signatures; no live charge or merchant sandbox transaction was executed. Merchant credentials and public callback deployment are required to verify external delivery.

Final local verification: education migrations applied successfully to MySQL after explicitly naming the lesson-progress unique index to fit MySQL identifier limits. The public `/courses` endpoint returns HTTP 200. No real courses, receiving accounts, or merchant secrets were invented or seeded.

Final automated result: **107 tests passed, 603 assertions**. Production asset build passed. The existing stale Browserslist-data advisory remains non-blocking.
