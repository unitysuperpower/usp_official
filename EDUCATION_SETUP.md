# USP Education

The education module is separate from service leads and project delivery. It uses the existing account system: ordinary users are students; administrators manage courses and enrollment.

## Pages

- `/courses`: public searchable course catalog, filtered by format and level.
- `/courses/{slug}`: course details, curriculum outline, fees, open batches, and application forms.
- `/learn`: student overview with enrollment totals, lesson progress, upcoming sessions, and links to the next action.
- `/learn/{id}`: application status, payments, scheduled classroom, lessons, and progress.
- `/admin/education/courses`: create/edit courses, publish/unpublish, manage batches and curriculum.
- `/admin/education/enrollments`: applications, students, acceptance, and certification.
- `/admin/education/payments`: manual verification and JazzCash reconciliation.
- `/admin/education/batches/{id}/classroom`: sessions, attendance, assignments, grading, and quizzes. Open it from a batch in the course editor.
- `/admin/education/settings`: receiving instructions and encrypted JazzCash configuration, protected by administrator password confirmation.

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

Apply the additive education migrations and build the frontend:

```bash
php artisan migrate --path=database/migrations/2026_09_28_120000_create_education_tables.php
php artisan migrate --path=database/migrations/2026_09_28_130000_add_education_gateway_fields.php
php artisan migrate --path=database/migrations/2026_09_29_100000_add_education_learning_tools.php
npm run build
```

No demo courses or payment credentials are automatically created. Add your own course content in the admin area. The active frontend is `resources/js/react/education.jsx`; the legacy `welcome.blade.php` does not render these pages.

## Manual payments

Enter verified receiving instructions in **Education → Payment settings**. Empty instructions disable the corresponding method. Before settings are saved in the admin interface, these `.env` values act as defaults:

```dotenv
EDUCATION_BANK_INSTRUCTIONS="Bank name, account title, IBAN, and payment instructions"
EDUCATION_JAZZCASH_INSTRUCTIONS="Verified receiving account and payment instructions"
EDUCATION_EASYPAISA_INSTRUCTIONS="Verified receiving account and payment instructions"
```

These are public instructions shown to accepted applicants, not API secrets. The student transfers the full fee, then uploads a PNG, JPG, or PDF receipt (maximum 5 MB). Proof files stay on the private storage disk and require student ownership or administrator access to download. A rejected proof can be corrected under the same reference; the prior review is retained. Transaction references cannot be reused across different manual payments of the same method.

## JazzCash online checkout

Implemented using JazzCash's hosted HTTP POST mobile-wallet flow. Wallet credentials are entered on JazzCash's page, not in this application. Enter merchant-provided credentials privately in **Education → Payment settings**. Saved secrets are encrypted using Laravel’s application key, never returned to the settings form, and excluded from validation flashes. Blank secret fields keep existing values. Back up the application key securely alongside the database; changing or losing it affects decryption. Database settings take precedence over these optional `.env` defaults:

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

After entering matching credentials and URLs, enable checkout in the settings form. If using only environment configuration, clear/rebuild Laravel configuration as appropriate and set `JAZZCASH_ENABLED=true`. Changes saved in the admin interface take effect immediately. Merchant details and environment cannot change while online payments are pending or awaiting reconciliation; receiving instructions and the enable/disable switch can still be updated. Test with your sandbox merchant account first. Production mode refuses sandbox checkout and sandbox callbacks. Live operation requires live merchant approval, matching credentials, and `JAZZCASH_ENVIRONMENT=live`.

The payment amount is taken from the stored enrollment, not browser input. Requests are signed with HMAC-SHA256. Callbacks verify the signature, merchant, reference, amount, currency, bill reference, and environment. Duplicate callbacks cannot grant access twice or reverse an approved payment. The hosted form contains the merchant password required by JazzCash's HTTP POST protocol; the signing salt stays on the server and must never be exposed.

A pending online attempt can be resumed for 30 minutes using the same transaction reference. A timeout or non-success response is **not** automatically treated as a failed charge. Administrators reconcile the final result in their merchant portal and record a note; rejection is allowed only after checkout expiry. A late success following another approved payment is flagged `review_required` for investigation. After resolving it in the merchant portal, the administrator can record the outcome and merchant reference in the app; repeated callbacks cannot reopen a resolved entry. Refunds/chargebacks and duplicate-charge resolution are handled in the merchant portal; this module does not send refund API calls or implement voucher IPN/recurring/card payments.

The return endpoint alone is CSRF-exempt because JazzCash posts from outside the site; its signed payload is mandatory. It does not rely on a student's browser session and returns a generic result page without exposing student details.

Official integration references:

- [JazzCash HTTP POST integration](https://sandbox.jazzcash.com.pk/SandboxDocumentation/v4.2/index.html)
- [JazzCash hashing scheme](https://sandbox.jazzcash.com.pk/SandboxDocumentation/v4.2/features.html)
- [JazzCash transaction parameters](https://sandbox.jazzcash.com.pk/SandboxDocumentation/ApiReferences.html)

## Attendance, assignments, and quizzes

Open **Attendance, assignments & quizzes** for a batch in the course editor. Student activities unlock only for active or completed enrollments; completed students have read-only access.

- Create class sessions with dates entered in Pakistan time. Future sessions cannot be marked completed. Mark a held session completed before entering attendance. Present and late count as attended; excused sessions are excluded. Completed sessions without a mark count as missed. Set the batch attendance minimum to 0 to disable the certificate gate. Attendance marks for completed enrollments are locked.
- Publish assignments with instructions, an optional deadline, late-submission policy, and pass mark. Students submit text and an optional private attachment up to 10 MB. Supported types: PDF, TXT, ZIP, DOCX, JPG, JPEG, PNG. Administrators grade from 0–100 with feedback or request revisions. Graded submissions are locked until a revision is requested. Pass marks cannot change after submissions exist.
- Publish single-answer multiple-choice quizzes with 2–6 options per question, a pass mark, and 1–10 attempts. The editor starts new questions with four options. Scoring runs on the server; answer keys are never included in student page data. Duplicate submissions of the same attempt do not consume another attempt. Passing ends further attempts. Questions, pass marks, and attempt limits cannot change after the first attempt; create another quiz if they need revision.
- Mark assignments or quizzes required to gate certification. Administrators can issue certificates only when attendance, required published assessments, and the existing lesson-completion rules are satisfied. Plan requirements before teaching; completed certificates retain their issuance name/course snapshots.

The student workspace groups these activities into keyboard-accessible tabs and keeps the selected tab after submission. The enrollment review shows assessment results alongside attendance before the completion decision.

## Verification

`tests/Feature/EducationLearningTest.php` additionally covers attendance and certificate gates, assignment privacy and revisions, quiz scoring/retries/limits, cross-batch restrictions, dashboard privacy, encrypted secrets, configuration validation, and pending-payment credential locks.

`tests/Feature/EducationTest.php` covers public/draft visibility, discovery, admin authoring, application idempotency, capacity, fee snapshots, student authorization, private receipts, proof correction, activation, progress, certificates, gateway configuration, signed callbacks, replay protection, and reconciliation.

The local browser check used a separate SQLite database with non-delivering mail/broadcast settings. It completed the admin-to-student manual payment and learning journey, classroom authoring, attendance, assignment submission/grading, quiz passing, payment settings, and certification at desktop and mobile sizes. Browser checks found no horizontal overflow or JavaScript errors. JazzCash responses were tested locally with signatures; no live charge or merchant sandbox transaction was executed. Merchant credentials and public callback deployment are required to verify external delivery.

Final local verification: education migrations applied successfully to MySQL after explicitly naming the lesson-progress unique index to fit MySQL identifier limits. The public `/courses` endpoint returns HTTP 200. No real courses, receiving accounts, or merchant secrets were invented or seeded.

Final automated result: **117 tests passed, 697 assertions**. Production asset build passed. The existing stale Browserslist-data advisory remains non-blocking.

### Course graphics and featured carousel
In Dashboard → Education → Courses → Edit, use Course graphics & featured carousel to upload a JPEG, PNG, or WebP cover, add its image description, or select a fallback graphic theme. Covers appear on catalog cards, featured slides, and course detail pages. Uploads are limited to 8 MB and 4096 × 4096 pixels; replaced and removed covers are cleaned up after a successful save.

Feature and publish a course to include it on /courses. Lower positions appear first (ties use course ID). Up to 12 featured courses appear; two or more enable previous/next and direct slide controls. With no featured courses the carousel is hidden. Navigation is manual and keyboard accessible. Featured courses are independent of catalog search filters.

### Connected learning experience (October 2026)
- Public catalog: subject filtering, fee sorting, and the next open batch date. Course editors can add an HTTPS preview video link, instructor biography, and FAQs.
- Student workspace: a focused curriculum sidebar opens the first unfinished lesson by default. Students can switch lessons, mark progress, open instructor video/resource links, and save private notes for each lesson. Notes are enrollment-scoped; clearing a note deletes it.
- My learning: upcoming assignment deadlines and course artwork join the existing progress and class schedule.
- Classrooms: admins can publish and remove batch announcements. Only active/completed students in that batch see them.
- Approved payments: students and admins can open a payment receipt and use the browser print dialog to save a PDF. Pending/rejected payments cannot produce receipts.
- Education overview: verified payments and outstanding accepted fees are grouped by currency. It also shows application counts for the last 30 days, course/batch enrollment counts, and active enrollments older than seven days without lesson completions. Follow-up suggestions do not imply a student has failed; in-person attendance may show progress instead.
- The course launch checklist is advisory. It checks description, uploaded artwork, instructor bio, published lessons, and an open batch.
- Existing attendance, assignment grading, quizzes, certification checks, and payment verification remain in force. Live JazzCash still requires valid merchant credentials; this release does not activate an unconfigured gateway.

### Payment method cards and scan-to-pay QR
Open Education → Payment settings to manage each receiving method as a card. Each method accepts an optional bank- or wallet-issued QR image (PNG, JPG, or WebP; up to 4 MB and 4096 × 4096). Original pixels are preserved. Upload a replacement or select Remove saved QR image, then confirm your administrator password and save. Leaving instructions empty disables the method even if it has a saved QR.

Accepted students can select a payment card, view receiving instructions and its QR, open the QR at full size, then submit a transaction reference and receipt. QR scanning does not automatically approve payment. Existing manual verification and JazzCash checkout rules still apply. Use your provider’s genuine payment QR; the application does not manufacture payment payloads or validate the recipient encoded in an uploaded image. Test the QR in the intended banking/wallet app before sharing it with students.

### Dashboard refinement (9 October 2026)
Students can search their enrolled courses and filter by status without changing their overall learning counts. The continue-learning spotlight links to the first unfinished published lesson in an active enrollment. It excludes courses whose published lessons are all complete.

The education overview includes review shortcuts and a 7/30-day applications chart, with an accessible daily-count table. Dates use Asia/Karachi calendar days; empty days are included. The chart measures application creation, not accepted enrollments or payments.

Validation: 148 tests / 833 assertions; desktop and mobile browser checks for student filters, spotlight, chart controls, and daily table. Production build passes. Published on 10 October 2026 with release 20261010-052656-lms-journey after hosting connectivity was restored.

### Guided LMS journey (10 October 2026)
Course batch buttons now open a dedicated enrollment review page. Login returns students to that page. It shows batch dates, schedule, fee and application details; closed/full batches cannot be submitted. The form sends the displayed fee/currency for server-side comparison, and a changed price requires refreshing before applying. Existing applications redirect to their own workspace.

The student workspace shows Application → Payment/Approval → Learn → Complete, with actions based on actual status. Paid access remains locked until manual or gateway verification. Free courses unlock after acceptance. The completion checklist reflects published lessons, required assignments, quizzes, and attendance; certificates still require administrator confirmation. QR payment continues to require a receipt and verification.

Validation: 151 tests / 862 assertions, including a full paid enrollment through receipt and certificate; browser checks for login return, application, gated access, active checklist, and mobile layout.
