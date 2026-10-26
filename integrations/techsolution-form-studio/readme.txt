=== TechSolution Form Studio ===
Contributors: usptechsolution
Tags: form builder, contact form, multi-step form, file upload, conditional logic
Requires at least: 6.3
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Drag-and-drop form builder with conditional logic, multi-step forms, file uploads, ratings, entries management, exports and spam protection.

== Description ==

TechSolution Form Studio lets you build contact forms, job applications, surveys, registrations and feedback forms in a visual builder, then manage every submission from your dashboard. Every feature described here is included in this plugin and works without a license key.

= Builder =
* Drag-and-drop canvas with live preview at desktop, tablet and mobile widths.
* Undo / redo, a searchable form outline, field duplication and keyboard save (Ctrl/Cmd + S).
* Starter templates: contact, job application with CV upload, feedback & rating, and event RSVP with conditional questions.
* Theme presets plus controls for colors, sizes, spacing, corner radius, shadows and button alignment.
* Import and export forms as JSON.

= Fields =
* Text, email, paragraph, number, phone, date, time, website, hidden and CNIC / Form-B fields.
* Dropdown, radio buttons, checkboxes, and a searchable country / nationality list (249 countries and territories).
* File upload with allowed extensions and size limits. Files get random names, are blocked from direct access and are downloaded by administrators from the entry screen.
* Star rating (3, 5, 7 or 10 stars), rating slider and a terms / privacy consent checkbox.
* Headings, paragraphs and lists for instructions.
* Multi-step forms with a progress bar and per-step validation.

= Logic and submissions =
* Conditional logic: show or hide fields when ALL or ANY rules match. Rules are checked again on the server, so hidden fields never block a submission.
* Server-side validation for every field type, including allowed options, number ranges, dates and uploads.
* Entries screen with search, date filters, read / unread / starred status, notes, bulk actions and an entry viewer.
* Export entries to CSV, Excel or a print-ready PDF report, with the columns you choose.
* Admin notifications with merge tags such as `{all_fields}`, `{form_title}`, `{entry_id}` and `{field:your_field_key}`, plus submitter auto-replies (`{form_title}`, `{entry_id}` and `{date}`; submitted answers are never echoed back).
* Redirect after submission, submission limits, closing dates and login-only forms.
* Webhooks that send each submission as JSON to an address you choose (for example Zapier, Make or n8n), with an optional HMAC signature.

= Spam protection and privacy =
* Honeypot and time-based bot checks, IP rate limiting, an IP block list and blocked keywords. These work without any external service.
* Optional Google reCAPTCHA (v2 or v3) or Cloudflare Turnstile. See "External services" below.
* Choose whether IP addresses and user agents are stored, and automatically delete entries after a number of days.
* Submissions are included in WordPress's Export Personal Data and Erase Personal Data tools, matched by email address.
* Suggested privacy policy text is added under Settings → Privacy.

= Embedding =
* "Form Studio Form" block for the block editor.
* Shortcode: `[tsfs_form id="1"]`. Add `title="false"` to hide the form title.

= Pro add-on =
TechSolution Form Studio Pro is a separate plugin, sold and distributed outside WordPress.org, that adds more field types: complete address with country, province and city lists, international phone with per-country formats, digital signature, calculations, payment details and an admission application block. This plugin does not contain, install or unlock any of that code, and nothing in this plugin is limited without it.

== External services ==

This plugin does not contact any external service unless you turn one on in Form Studio → Settings or in a form's settings.

= Google reCAPTCHA (optional) =
Used to check that a form submission comes from a person, when you select reCAPTCHA v2 or v3 as the anti-spam provider and enter your keys.

* When a page shows a form, the visitor's browser loads the reCAPTCHA script from https://www.google.com/recaptcha/api.js. Google receives the visitor's IP address, browser information and the interaction data Google uses for verification.
* When the form is submitted, your site sends the verification token, your secret key and the visitor's IP address to https://www.google.com/recaptcha/api/siteverify.
* The service is provided by Google LLC. [Terms of Service](https://policies.google.com/terms), [Privacy Policy](https://policies.google.com/privacy).

= Cloudflare Turnstile (optional) =
Used to check that a form submission comes from a person, when you select Turnstile as the anti-spam provider and enter your keys.

* When a page shows a form, the visitor's browser loads the Turnstile script from https://challenges.cloudflare.com/turnstile/v0/api.js. Cloudflare receives the visitor's IP address and browser information used for the challenge.
* When the form is submitted, your site sends the verification token, your secret key and the visitor's IP address to https://challenges.cloudflare.com/turnstile/v0/siteverify.
* The service is provided by Cloudflare, Inc. [Terms of Use](https://www.cloudflare.com/website-terms/), [Privacy Policy](https://www.cloudflare.com/privacypolicy/), [Turnstile Privacy Addendum](https://www.cloudflare.com/turnstile-privacy-policy/).

= Webhooks (optional) =
When you enable a webhook in a form's settings, each new submission (form title, entry ID, submitted values, submission time, and the IP address if you store it) is sent as JSON to the URL you enter. You choose this destination; its terms and privacy policy are those of the service you connect.

== Installation ==

1. In WordPress, go to Plugins → Add New, search for "TechSolution Form Studio", then install and activate it. You can also upload the `techsolution-form-studio` folder to `/wp-content/plugins/`.
2. Open **Form Studio → Add New** to build a form or start from a template.
3. Add the **Form Studio Form** block to a page, or paste the shortcode shown in **All Forms**, for example `[tsfs_form id="1"]`.
4. Optional: configure reCAPTCHA or Turnstile, rate limits and privacy options under **Form Studio → Settings**.

== Frequently Asked Questions ==

= How do I add a form to a page? =
In the block editor, add the "Form Studio Form" block and pick the form. In other editors or widgets, use the shortcode from All Forms, for example `[tsfs_form id="1"]`.

= How does conditional logic work? =
Select a field, turn on Conditional Logic in the Field panel, and add rules based on other fields' answers. Choose whether the field is shown or hidden when all or any rules match.

= How do I build a multi-step form? =
Add a "Multi-Step Forms" page break between fields. Every page break starts a new step. Visitors see a progress bar and each step is validated before moving on.

= Where are uploaded files stored? =
In `wp-content/uploads/techsolution-form-studio/` under random file names. An `.htaccess` file blocks direct access on Apache and LiteSpeed (on Nginx, add an equivalent `deny all` rule for that folder); administrators download files from the entry screen. Executable, HTML and SVG files are always refused, and each upload is checked against the extensions you allow. Files are deleted together with their entry.

= Can visitors request a copy or deletion of their submissions? =
Yes. Use Tools → Export Personal Data or Tools → Erase Personal Data with the visitor's email address. Submissions containing that email address are included.

= Can I add custom CSS? =
Use the form's Style panel for colors, sizes and layout. For anything else, add CSS in Appearance → Customize → Additional CSS (or the Site Editor's Styles panel). Each form's wrapper has the ID `tsfs-form-{ID}`.

= I used "USP Form Builder" before. Will my forms move over? =
Yes. On activation, forms, entries and anti-spam settings from USP Form Builder are copied into TechSolution Form Studio when its tables are still empty. Replace the old `[usp_form id="…"]` shortcodes with `[tsfs_form id="…"]`.

== Screenshots ==

1. Visual builder with field palette, canvas and field settings.
2. Entries screen with filters, bulk actions and the entry viewer.
3. Settings: spam protection, rate limiting, uploads and privacy.

== Changelog ==

= 2.1.0 =
* Plugin Check: all database queries are fully prepared (table names use %i placeholders), translator comments and ordered placeholders added, no direct file writes in exports, and external CAPTCHA scripts are documented as optional services.
* Fixed "Security verification failed" on sites with page caching (LiteSpeed Cache, WP Rocket, Cloudflare and others). Forms now fetch a fresh security token just before submitting.
* Admin screens now use the full width of the dashboard (up to 1640px), so tables show every column, including actions.
* Redesigned Settings: a section menu, clear setting rows with plain-language descriptions, a status summary and a sticky Save bar.
* Consistent page headers on Forms, Submissions and Settings.
* Builder: tablet and mobile previews now narrow the canvas correctly, even when a custom form width is set; the desktop canvas follows the form's real width.
* Settings and submission counts use correct singular and plural wording.
* The view counter only counts forms that exist.

= 2.0.1 =
* Uploaded files get random names, are blocked from direct access and are deleted with their entry.
* HTML, SVG and XML uploads are always refused.
* Formatting of add-on content blocks is kept when the add-on is inactive.

= 2.0.0 =
* Renamed from USP Form Builder to TechSolution Form Studio, with a new slug, text domain and code prefix. Existing forms, entries and settings are migrated automatically.
* File upload, star rating, rating slider, consent, country / nationality and multi-step forms are now included for everyone.
* New "Form Studio Form" block.
* New privacy controls: optional IP and user agent storage, automatic entry deletion, and WordPress personal data export / erasure support.
* Conditional logic and choice answers are now validated on the server.
* reCAPTCHA v3 tokens are requested at submission time.
* Removed the custom CSS box. Use Additional CSS in the Customizer or Site Editor instead.
* All scripts and styles are loaded with the WordPress enqueue functions and only on pages that show a form.

== Upgrade Notice ==

= 2.1.0 =
Fixes "Security verification failed" on cached sites, plus wider admin screens and a redesigned Settings page. No changes to your forms or entries.

= 2.0.1 =
New name and shortcode: replace `[usp_form id="…"]` with `[tsfs_form id="…"]`. Forms, entries and settings are migrated automatically.
